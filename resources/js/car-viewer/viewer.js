import * as THREE from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
import { buildProceduralCar } from './procedural-car.js';

const VIEWS = {
    hero: [5.6, 2.1, 6.4],
    front: [7.6, 1.4, 0.01],
    side: [0.01, 1.5, 8.4],
    rear: [-7.6, 1.6, 0.01],
    top: [0.01, 9.5, 0.6],
};

const TARGET = new THREE.Vector3(0, 0.7, 0);

export class CarViewer {
    constructor(container, config = {}) {
        this.el = container;
        this.config = config;
        this.host = container.querySelector('[data-viewer-canvas]') || container;
        this.paintMaterials = [];
        this.lights = { head: [], tail: [] };
        this.headlightsOn = false;
        this.visible = true;
        this.cameraTween = null;
        this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    async init() {
        this.createRenderer();
        this.createScene();
        this.createControls();
        await this.loadCar();
        this.bindUi();
        this.observe();
        this.renderer.setAnimationLoop((t) => this.tick(t));
        this.el.setAttribute('data-viewer-state', 'ready');
        this.el.dispatchEvent(new CustomEvent('viewer:ready', { bubbles: true, detail: { paintable: this.paintMaterials.length > 0 } }));
    }

    createRenderer() {
        const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        renderer.toneMapping = THREE.ACESFilmicToneMapping;
        renderer.toneMappingExposure = 1.05;
        renderer.outputColorSpace = THREE.SRGBColorSpace;
        renderer.domElement.className = 'block h-full w-full touch-none outline-none';
        renderer.domElement.setAttribute('aria-label', `Interactive 3D view of the ${this.config.name || 'car'}`);
        renderer.domElement.setAttribute('role', 'img');
        this.host.appendChild(renderer.domElement);
        this.renderer = renderer;
        this.resize();
    }

    createScene() {
        const scene = new THREE.Scene();
        const pmrem = new THREE.PMREMGenerator(this.renderer);
        scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
        pmrem.dispose();

        const { clientWidth: w, clientHeight: h } = this.host;
        this.camera = new THREE.PerspectiveCamera(30, w / Math.max(h, 1), 0.1, 100);
        this.camera.position.set(...VIEWS.hero);

        scene.add(new THREE.HemisphereLight(0xffffff, 0x1a1a1a, 0.55));

        const key = new THREE.DirectionalLight(0xffffff, 1.7);
        key.position.set(4, 8, 5);
        scene.add(key);

        // Signature red rim light behind the car.
        const rim = new THREE.PointLight(0xe50914, 28, 14, 1.6);
        rim.position.set(-4.5, 1.6, -4.2);
        scene.add(rim);

        const fill = new THREE.PointLight(0x7fa7ff, 6, 12, 2);
        fill.position.set(4.5, 1.2, -3);
        scene.add(fill);

        // Showroom floor: a soft spotlight pool that fades into the page.
        const floor = new THREE.Mesh(
            new THREE.CircleGeometry(7.5, 96),
            new THREE.MeshStandardMaterial({ map: radialTexture('#262626', 'rgba(11,11,11,0)'), transparent: true, roughness: 0.6, metalness: 0.2, depthWrite: false })
        );
        floor.rotation.x = -Math.PI / 2;
        floor.position.y = -0.002;
        scene.add(floor);

        const ring = new THREE.Mesh(
            new THREE.RingGeometry(3.35, 3.38, 128),
            new THREE.MeshBasicMaterial({ color: 0xe50914, transparent: true, opacity: 0.35 })
        );
        ring.rotation.x = -Math.PI / 2;
        ring.position.y = 0.001;
        scene.add(ring);

        this.scene = scene;
    }

    createControls() {
        const controls = new OrbitControls(this.camera, this.renderer.domElement);
        controls.target.copy(TARGET);
        controls.enableDamping = true;
        controls.dampingFactor = 0.07;
        controls.enablePan = false;
        controls.minDistance = 4.2;
        controls.maxDistance = 12;
        controls.minPolarAngle = 0.15;
        controls.maxPolarAngle = Math.PI * 0.485;
        controls.autoRotate = !this.reducedMotion && this.config.autoRotate !== false;
        controls.autoRotateSpeed = 0.9;
        controls.addEventListener('start', () => { this.cameraTween = null; });
        this.controls = controls;
        this.syncToggle('autorotate', controls.autoRotate);
    }

    async loadCar() {
        let car = null;

        if (this.config.model) {
            try {
                car = await this.loadGltf(this.config.model);
            } catch (error) {
                console.warn('[car-viewer] Falling back to procedural model:', error);
            }
        }

        if (!car) {
            const built = buildProceduralCar({ body: this.config.body, color: this.config.color });
            this.paintMaterials = built.paintMaterials;
            this.lights = built.lights;
            car = built.group;
        }

        // Contact shadow keeps the car grounded without a shadow map.
        const box = new THREE.Box3().setFromObject(car);
        const size = box.getSize(new THREE.Vector3());
        const shadow = new THREE.Mesh(
            new THREE.PlaneGeometry(size.x * 1.25, size.z * 1.9),
            new THREE.MeshBasicMaterial({ map: radialTexture('rgba(0,0,0,0.85)', 'rgba(0,0,0,0)'), transparent: true, depthWrite: false })
        );
        shadow.rotation.x = -Math.PI / 2;
        shadow.position.y = 0.003;
        this.scene.add(shadow);

        this.car = car;
        this.scene.add(car);
    }

    async loadGltf(url) {
        const { GLTFLoader } = await import('three/addons/loaders/GLTFLoader.js');
        const gltf = await new GLTFLoader().loadAsync(url);
        const model = gltf.scene;

        // Normalise: ~4.5 m long, centred, wheels on the floor.
        const box = new THREE.Box3().setFromObject(model);
        const size = box.getSize(new THREE.Vector3());
        const scale = 4.5 / Math.max(size.x, size.z, 0.001);
        model.scale.setScalar(scale);
        box.setFromObject(model);
        const center = box.getCenter(new THREE.Vector3());
        model.position.x -= center.x;
        model.position.z -= center.z;
        model.position.y -= box.min.y;

        if (size.z > size.x) model.rotation.y = Math.PI / 2;

        model.traverse((o) => {
            if (!o.isMesh) return;
            const materials = Array.isArray(o.material) ? o.material : [o.material];
            materials.forEach((m) => {
                if (/paint|body|carpaint|exterior/i.test(m.name || '') && !this.paintMaterials.includes(m)) {
                    this.paintMaterials.push(m);
                }
            });
        });

        const wrapper = new THREE.Group();
        wrapper.add(model);
        return wrapper;
    }

    bindUi() {
        this.el.addEventListener('click', (event) => {
            const action = event.target.closest('[data-viewer-action]')?.dataset.viewerAction;
            const color = event.target.closest('[data-viewer-color]')?.dataset.viewerColor;

            if (color) {
                this.setColor(color);
                this.el.querySelectorAll('[data-viewer-color]').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.viewerColor === color)));
            }
            if (!action) return;

            if (action.startsWith('view:')) this.setView(action.slice(5));
            else if (action === 'autorotate') this.toggleAutoRotate();
            else if (action === 'lights') this.toggleHeadlights();
            else if (action === 'reset') this.setView('hero');
            else if (action === 'fullscreen') this.toggleFullscreen();
        });

        this.el.querySelectorAll('[data-viewer-color]').forEach((b) => {
            b.setAttribute('aria-pressed', String(b.dataset.viewerColor?.toLowerCase() === String(this.config.color).toLowerCase()));
        });
    }

    observe() {
        this.resizeObserver = new ResizeObserver(() => this.resize());
        this.resizeObserver.observe(this.host);

        // Stop rendering when off-screen or the tab is hidden (battery!).
        this.visibilityObserver = new IntersectionObserver(([entry]) => { this.visible = entry.isIntersecting; });
        this.visibilityObserver.observe(this.el);
        document.addEventListener('visibilitychange', () => { this.visible = !document.hidden; });

        // Tear down if the element leaves the DOM (Livewire morphs).
        this.mutationObserver = new MutationObserver(() => {
            if (!document.body.contains(this.el)) this.dispose();
        });
        this.mutationObserver.observe(document.body, { childList: true, subtree: true });
    }

    tick() {
        if (!this.visible) return;

        if (this.cameraTween) {
            const t = Math.min(1, (performance.now() - this.cameraTween.start) / this.cameraTween.duration);
            const e = 1 - Math.pow(1 - t, 3);
            this.camera.position.lerpVectors(this.cameraTween.from, this.cameraTween.to, e);
            if (t >= 1) this.cameraTween = null;
        }

        this.controls.update();
        this.renderer.render(this.scene, this.camera);
    }

    resize() {
        if (!this.renderer) return;
        const { clientWidth: w, clientHeight: h } = this.host;
        if (!w || !h) return;
        this.renderer.setSize(w, h, false);
        if (this.camera) {
            this.camera.aspect = w / h;
            // Pull back on narrow screens so the whole car stays in frame.
            this.camera.fov = w / h < 1 ? 42 : 30;
            this.camera.updateProjectionMatrix();
        }
    }

    setColor(hex) {
        const target = new THREE.Color(hex);
        this.paintMaterials.forEach((m) => {
            const from = m.color.clone();
            const start = performance.now();
            const step = () => {
                const t = Math.min(1, (performance.now() - start) / 450);
                m.color.copy(from).lerp(target, t);
                if (t < 1) requestAnimationFrame(step);
            };
            step();
        });
    }

    setView(name) {
        const to = VIEWS[name];
        if (!to) return;
        this.controls.autoRotate = false;
        this.syncToggle('autorotate', false);
        this.cameraTween = {
            from: this.camera.position.clone(),
            to: new THREE.Vector3(...to),
            start: performance.now(),
            duration: this.reducedMotion ? 1 : 900,
        };
    }

    toggleAutoRotate() {
        this.controls.autoRotate = !this.controls.autoRotate;
        this.syncToggle('autorotate', this.controls.autoRotate);
    }

    toggleHeadlights() {
        this.headlightsOn = !this.headlightsOn;
        this.lights.head.forEach((m) => { m.material.emissiveIntensity = this.headlightsOn ? 4 : 0.35; });
        this.lights.tail.forEach((m) => { m.material.emissiveIntensity = this.headlightsOn ? 2.5 : 0.5; });

        if (!this.beams) {
            this.beams = new THREE.SpotLight(0xfff1d6, 0, 14, 0.5, 0.6, 1.4);
            this.beams.position.set(2.4, 0.8, 0);
            this.beams.target.position.set(8, 0, 0);
            this.scene.add(this.beams, this.beams.target);
        }
        this.beams.intensity = this.headlightsOn ? 40 : 0;
        this.syncToggle('lights', this.headlightsOn);
    }

    toggleFullscreen() {
        if (document.fullscreenElement) document.exitFullscreen?.();
        else this.el.requestFullscreen?.();
    }

    syncToggle(action, on) {
        this.el.querySelectorAll(`[data-viewer-action="${action}"]`).forEach((b) => b.setAttribute('aria-pressed', String(on)));
    }

    dispose() {
        this.renderer?.setAnimationLoop(null);
        this.resizeObserver?.disconnect();
        this.visibilityObserver?.disconnect();
        this.mutationObserver?.disconnect();
        this.controls?.dispose();
        this.scene?.traverse((o) => {
            o.geometry?.dispose?.();
            (Array.isArray(o.material) ? o.material : [o.material]).forEach((m) => { m?.map?.dispose?.(); m?.dispose?.(); });
        });
        this.renderer?.dispose();
        this.renderer?.domElement?.remove();
    }
}

function radialTexture(inner, outer) {
    const size = 256;
    const canvas = document.createElement('canvas');
    canvas.width = canvas.height = size;
    const ctx = canvas.getContext('2d');
    const gradient = ctx.createRadialGradient(size / 2, size / 2, 0, size / 2, size / 2, size / 2);
    gradient.addColorStop(0, inner);
    gradient.addColorStop(1, outer);
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, size, size);
    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    return texture;
}
