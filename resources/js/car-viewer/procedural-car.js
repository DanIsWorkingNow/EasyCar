import * as THREE from 'three';

/*
 * Builds a stylised but convincing car from extruded side profiles, so
 * every car in the fleet has a 3D model without needing a licensed asset.
 * Admins can upload a real .glb per car, which replaces this entirely.
 *
 * Coordinates: x = length (front is +x), y = up, z = width. Units ≈ metres.
 */

const PRESETS = {
    sedan: {
        width: 1.78,
        wheelR: 0.33,
        axles: [1.36, -1.32],
        bottom: 0.3,
        lower: [[-2.24, 0.3], [-2.28, 0.5], [-2.24, 0.8], [-1.66, 0.92], [1.1, 0.95], [2.0, 0.8], [2.27, 0.6], [2.24, 0.3]],
        cabin: [[-1.62, 0.92], [-0.98, 1.38], [0.3, 1.42], [1.08, 0.95]],
        head: [2.13, 0.71], tail: [-2.25, 0.74], grilleY: 0.5, mirrorX: 0.98,
    },
    hatchback: {
        width: 1.72,
        wheelR: 0.31,
        axles: [1.22, -1.22],
        bottom: 0.29,
        lower: [[-1.92, 0.29], [-1.96, 0.52], [-1.92, 0.92], [1.0, 0.95], [1.78, 0.82], [2.0, 0.62], [1.97, 0.29]],
        cabin: [[-1.9, 0.92], [-1.66, 1.44], [0.28, 1.48], [0.98, 0.95]],
        head: [1.88, 0.73], tail: [-1.95, 0.8], grilleY: 0.5, mirrorX: 0.9,
    },
    suv: {
        width: 1.86,
        wheelR: 0.38,
        axles: [1.4, -1.38],
        bottom: 0.4,
        lower: [[-2.3, 0.4], [-2.34, 0.66], [-2.28, 1.1], [1.2, 1.12], [2.08, 1.0], [2.34, 0.78], [2.3, 0.4]],
        cabin: [[-2.24, 1.1], [-2.08, 1.72], [0.52, 1.76], [1.18, 1.12]],
        head: [2.24, 0.92], tail: [-2.3, 0.98], grilleY: 0.66, mirrorX: 1.1, roofRails: true,
    },
    mpv: {
        width: 1.8,
        wheelR: 0.34,
        axles: [1.44, -1.36],
        bottom: 0.32,
        lower: [[-2.28, 0.32], [-2.32, 0.56], [-2.28, 1.02], [1.32, 1.04], [2.08, 0.9], [2.3, 0.66], [2.26, 0.32]],
        cabin: [[-2.24, 1.02], [-2.12, 1.68], [0.62, 1.72], [1.3, 1.04]],
        head: [2.2, 0.82], tail: [-2.29, 0.9], grilleY: 0.55, mirrorX: 1.22,
    },
};

export function buildProceduralCar({ body = 'sedan', color = '#b91c1c' } = {}) {
    const p = PRESETS[body] ?? PRESETS.sedan;
    const group = new THREE.Group();
    group.name = 'procedural-car';

    const materials = createMaterials(color);

    // Lower body with wheel arches cut out.
    const lowerShape = new THREE.Shape();
    lowerShape.moveTo(...p.lower[0]);
    p.lower.slice(1).forEach(([x, y]) => lowerShape.lineTo(x, y));
    const archR = p.wheelR + 0.12;
    [...p.axles].sort((a, b) => b - a).forEach((ax) => {
        const a0 = Math.asin(THREE.MathUtils.clamp((p.bottom - p.wheelR) / archR, -0.95, 0.95));
        lowerShape.lineTo(ax + archR * Math.cos(a0), p.bottom);
        lowerShape.absarc(ax, p.wheelR, archR, a0, Math.PI - a0, false);
    });
    lowerShape.lineTo(...p.lower[0]);

    group.add(extrude(lowerShape, p.width, { bevelThickness: 0.08, bevelSize: 0.05, bevelSegments: 6 }, materials.paint));

    // Glasshouse, slightly narrower and softly bevelled so the sides taper.
    const cabinShape = new THREE.Shape();
    cabinShape.moveTo(...p.cabin[0]);
    p.cabin.slice(1).forEach(([x, y]) => cabinShape.lineTo(x, y));
    cabinShape.closePath();
    group.add(extrude(cabinShape, p.width * 0.8, { bevelThickness: 0.07, bevelSize: 0.035, bevelSegments: 4 }, materials.glass));

    // Painted roof skin following the top of the glasshouse.
    const [rear, roofRear, roofFront] = [p.cabin[0], p.cabin[1], p.cabin[2]];
    const roofShape = new THREE.Shape();
    roofShape.moveTo(roofRear[0] + 0.04, roofRear[1] - 0.02);
    roofShape.lineTo(roofFront[0] - 0.04, roofFront[1] - 0.02);
    roofShape.lineTo(roofFront[0] - 0.06, roofFront[1] + 0.035);
    roofShape.lineTo(roofRear[0] + 0.06, roofRear[1] + 0.035);
    roofShape.closePath();
    group.add(extrude(roofShape, p.width * 0.8 + 0.02, { bevelThickness: 0.04, bevelSize: 0.02, bevelSegments: 3 }, materials.paint));

    // Pillars (A, B, C) as painted strips on both sides.
    const cabinZ = (p.width * 0.8) / 2 + 0.07;
    const pillar = (from, to, w = 0.07) => {
        const len = Math.hypot(to[0] - from[0], to[1] - from[1]);
        const geom = new THREE.BoxGeometry(len, w, 0.03);
        [1, -1].forEach((side) => {
            const mesh = new THREE.Mesh(geom, materials.paint);
            mesh.position.set((from[0] + to[0]) / 2, (from[1] + to[1]) / 2, side * (cabinZ - 0.012));
            mesh.rotation.z = Math.atan2(to[1] - from[1], to[0] - from[0]);
            group.add(mesh);
        });
    };
    const front = p.cabin[3];
    pillar(rear, roofRear);
    pillar(front, roofFront);
    const bx = (roofRear[0] + roofFront[0]) / 2 - 0.15;
    pillar([bx, rear[1] + 0.02], [bx, roofRear[1] + 0.01], 0.06);

    // Wheels.
    const wheelZ = p.width / 2 - 0.08;
    p.axles.forEach((ax) => [1, -1].forEach((side) => {
        const wheel = buildWheel(p.wheelR, materials);
        wheel.position.set(ax, p.wheelR, side * wheelZ);
        if (side < 0) wheel.rotation.y = Math.PI;
        group.add(wheel);
    }));

    // Lights, grille, mirrors, sills.
    const lights = { head: [], tail: [] };
    const halfW = p.width / 2;
    [1, -1].forEach((side) => {
        const head = new THREE.Mesh(new THREE.BoxGeometry(0.1, 0.09, 0.36), materials.headlight);
        head.position.set(p.head[0], p.head[1], side * (halfW - 0.3));
        group.add(head);
        lights.head.push(head);

        const tail = new THREE.Mesh(new THREE.BoxGeometry(0.08, 0.1, 0.42), materials.taillight);
        tail.position.set(p.tail[0], p.tail[1], side * (halfW - 0.3));
        group.add(tail);
        lights.tail.push(tail);

        const mirror = new THREE.Mesh(new THREE.BoxGeometry(0.16, 0.1, 0.12), materials.paint);
        mirror.position.set(p.mirrorX, p.cabin[3][1] + 0.08, side * (halfW + 0.06));
        group.add(mirror);
    });

    const grille = new THREE.Mesh(new THREE.BoxGeometry(0.06, 0.18, p.width * 0.5), materials.trim);
    grille.position.set(p.lower[p.lower.length - 2][0] - 0.02, p.grilleY, 0);
    group.add(grille);

    const sill = new THREE.Mesh(new THREE.BoxGeometry(p.axles[0] - p.axles[1] - archR * 2, 0.07, p.width + 0.1), materials.trim);
    sill.position.set((p.axles[0] + p.axles[1]) / 2, p.bottom + 0.03, 0);
    group.add(sill);

    if (p.roofRails) {
        const railLen = roofFront[0] - roofRear[0] - 0.2;
        [1, -1].forEach((side) => {
            const rail = new THREE.Mesh(new THREE.BoxGeometry(railLen, 0.04, 0.05), materials.chrome);
            rail.position.set((roofFront[0] + roofRear[0]) / 2, roofFront[1] + 0.07, side * (p.width * 0.36));
            group.add(rail);
        });
    }

    group.traverse((o) => { if (o.isMesh) { o.castShadow = true; } });

    return {
        group,
        paintMaterials: [materials.paint],
        lights,
        materials,
        length: Math.max(...p.lower.map(([x]) => x)) - Math.min(...p.lower.map(([x]) => x)),
        height: p.cabin[2][1],
        width: p.width,
    };
}

function extrude(shape, width, bevel, material) {
    const depth = Math.max(0.01, width - bevel.bevelThickness * 2);
    const geometry = new THREE.ExtrudeGeometry(shape, {
        depth,
        bevelEnabled: true,
        curveSegments: 36,
        steps: 1,
        ...bevel,
    });
    geometry.translate(0, 0, -depth / 2);
    geometry.computeVertexNormals();
    return new THREE.Mesh(geometry, material);
}

function buildWheel(r, m) {
    const wheel = new THREE.Group();
    const width = 0.24;

    const tire = new THREE.Mesh(new THREE.CylinderGeometry(r, r, width, 48, 1), m.tire);
    tire.rotation.x = Math.PI / 2;
    wheel.add(tire);

    const sidewall = new THREE.Mesh(new THREE.TorusGeometry(r - 0.035, 0.035, 12, 48), m.tire);
    sidewall.position.z = width / 2;
    wheel.add(sidewall);

    const rim = new THREE.Mesh(new THREE.CylinderGeometry(r * 0.66, r * 0.66, width + 0.01, 40), m.rimDark);
    rim.rotation.x = Math.PI / 2;
    wheel.add(rim);

    const lip = new THREE.Mesh(new THREE.TorusGeometry(r * 0.66, 0.012, 8, 48), m.chrome);
    lip.position.z = width / 2 + 0.006;
    wheel.add(lip);

    for (let i = 0; i < 5; i++) {
        const spoke = new THREE.Mesh(new THREE.BoxGeometry(r * 1.22, 0.055, 0.03), m.chrome);
        spoke.rotation.z = (i / 5) * Math.PI;
        spoke.position.z = width / 2 + 0.01;
        wheel.add(spoke);
    }

    const hub = new THREE.Mesh(new THREE.CylinderGeometry(0.06, 0.06, 0.04, 20), m.chrome);
    hub.rotation.x = Math.PI / 2;
    hub.position.z = width / 2 + 0.02;
    wheel.add(hub);

    return wheel;
}

function createMaterials(color) {
    return {
        paint: new THREE.MeshPhysicalMaterial({
            color: new THREE.Color(color),
            metalness: 0.55,
            roughness: 0.3,
            clearcoat: 1,
            clearcoatRoughness: 0.05,
            envMapIntensity: 1.2,
        }),
        glass: new THREE.MeshPhysicalMaterial({
            color: 0x020305,
            metalness: 0,
            roughness: 0.03,
            clearcoat: 1,
            clearcoatRoughness: 0.02,
            transparent: true,
            opacity: 0.96,
            envMapIntensity: 0.55,
        }),
        trim: new THREE.MeshStandardMaterial({ color: 0x0e0e0e, roughness: 0.55, metalness: 0.2 }),
        chrome: new THREE.MeshStandardMaterial({ color: 0xe6e6e6, roughness: 0.16, metalness: 1 }),
        rimDark: new THREE.MeshStandardMaterial({ color: 0x1a1a1a, roughness: 0.35, metalness: 0.8 }),
        tire: new THREE.MeshStandardMaterial({ color: 0x0c0c0c, roughness: 0.9, metalness: 0 }),
        headlight: new THREE.MeshStandardMaterial({ color: 0xffffff, emissive: 0xfff3dc, emissiveIntensity: 0.35, roughness: 0.1 }),
        taillight: new THREE.MeshStandardMaterial({ color: 0x3a0000, emissive: 0xff1a1a, emissiveIntensity: 0.5, roughness: 0.2 }),
    };
}
