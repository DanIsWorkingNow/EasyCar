/*
 * Finds [data-car-viewer] containers and boots a 3D viewer in each one the
 * first time it scrolls near the viewport. three.js lives in a separate
 * chunk, so pages without a viewer never download it.
 *
 * Markup contract (see resources/views/components/car-viewer.blade.php):
 *   <div data-car-viewer='{"body":"suv","color":"#b91c1c","model":null}'>
 *     <div data-viewer-canvas></div>
 *     <button data-viewer-action="autorotate|lights|reset|fullscreen|view:front|view:side|view:rear|view:top">
 *     <button data-viewer-color="#hex">
 *   </div>
 */
function supportsWebGL() {
    try {
        const canvas = document.createElement('canvas');
        return !!(window.WebGLRenderingContext && (canvas.getContext('webgl2') || canvas.getContext('webgl')));
    } catch (_) {
        return false;
    }
}

export function mountCarViewers(root) {
    const containers = [...root.querySelectorAll('[data-car-viewer]:not([data-viewer-mounted])')];
    if (!containers.length) return;

    if (!supportsWebGL()) {
        containers.forEach((el) => el.setAttribute('data-viewer-state', 'unsupported'));
        return;
    }

    const boot = async (el) => {
        el.setAttribute('data-viewer-mounted', '');
        el.setAttribute('data-viewer-state', 'loading');
        try {
            const config = JSON.parse(el.dataset.carViewer || '{}');
            const { CarViewer } = await import('./viewer.js');
            const viewer = new CarViewer(el, config);
            await viewer.init();
            el.carViewer = viewer;
        } catch (error) {
            console.error('[car-viewer]', error);
            el.setAttribute('data-viewer-state', 'error');
        }
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                observer.unobserve(entry.target);
                boot(entry.target);
            }
        });
    }, { rootMargin: '200px' });

    containers.forEach((el) => observer.observe(el));
}
