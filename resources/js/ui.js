/*
 * Small Alpine components shared across pages. Livewire ships Alpine, so
 * these register on `alpine:init` (or immediately if Alpine is already up).
 */
const register = (Alpine) => {
    // Top navigation: transparent over the hero, solid once scrolled.
    Alpine.data('navbar', () => ({
        scrolled: false,
        mobileOpen: false,
        init() {
            const onScroll = () => { this.scrolled = window.scrollY > 24; };
            onScroll();
            window.addEventListener('scroll', onScroll, { passive: true });
        },
    }));

    // Horizontal car rows with paging arrows.
    Alpine.data('carousel', () => ({
        canPrev: false,
        canNext: false,
        init() {
            const track = this.$refs.track;
            const update = () => {
                this.canPrev = track.scrollLeft > 4;
                this.canNext = track.scrollLeft + track.clientWidth < track.scrollWidth - 4;
            };
            update();
            track.addEventListener('scroll', update, { passive: true });
            new ResizeObserver(update).observe(track);
        },
        page(direction) {
            const track = this.$refs.track;
            track.scrollBy({ left: direction * track.clientWidth * 0.85, behavior: 'smooth' });
        },
    }));

    // Flash toasts that dismiss themselves.
    Alpine.data('toast', (timeout = 6000) => ({
        open: true,
        init() {
            if (timeout > 0) setTimeout(() => { this.open = false; }, timeout);
        },
    }));

    // Finger/stylus signature capture. Writes a PNG data URL to the bound
    // Livewire property when each stroke ends.
    Alpine.data('signaturePad', (property) => ({
        empty: true,
        drawing: false,
        init() {
            const canvas = this.$refs.canvas;
            const ctx = canvas.getContext('2d');
            const resize = () => {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                const { width, height } = canvas.getBoundingClientRect();
                canvas.width = width * ratio;
                canvas.height = height * ratio;
                ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
                ctx.lineWidth = 2.4;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                ctx.strokeStyle = '#111827';
                this.clear(false);
            };
            resize();
            new ResizeObserver(() => { if (this.empty) resize(); }).observe(canvas);

            const point = (e) => {
                const r = canvas.getBoundingClientRect();
                return [e.clientX - r.left, e.clientY - r.top];
            };
            canvas.addEventListener('pointerdown', (e) => {
                e.preventDefault();
                canvas.setPointerCapture(e.pointerId);
                this.drawing = true;
                const [x, y] = point(e);
                ctx.beginPath();
                ctx.moveTo(x, y);
                ctx.lineTo(x + 0.1, y + 0.1);
                ctx.stroke();
            });
            canvas.addEventListener('pointermove', (e) => {
                if (!this.drawing) return;
                const [x, y] = point(e);
                ctx.lineTo(x, y);
                ctx.stroke();
            });
            const end = () => {
                if (!this.drawing) return;
                this.drawing = false;
                this.empty = false;
                this.$wire.set(property, canvas.toDataURL('image/png'));
            };
            canvas.addEventListener('pointerup', end);
            canvas.addEventListener('pointerleave', end);
        },
        clear(sync = true) {
            const canvas = this.$refs.canvas;
            const ctx = canvas.getContext('2d');
            ctx.save();
            ctx.setTransform(1, 0, 0, 1, 0, 0);
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.restore();
            this.empty = true;
            if (sync) this.$wire.set(property, null);
        },
    }));

    // Polls a payment's status until it settles, then reloads.
    Alpine.data('paymentPoller', (url) => ({
        tries: 0,
        init() {
            const tick = async () => {
                this.tries++;
                try {
                    const { data } = await window.axios.get(url);
                    if (!data.pending) { window.location.reload(); return; }
                } catch (_) { /* keep polling */ }
                if (this.tries < 40) setTimeout(tick, this.tries < 10 ? 2000 : 5000);
            };
            setTimeout(tick, 2000);
        },
    }));
};

if (window.Alpine) {
    register(window.Alpine);
} else {
    document.addEventListener('alpine:init', () => register(window.Alpine));
}

// <form data-confirm="Are you sure?"> — native confirm before submitting.
document.addEventListener('submit', (event) => {
    const message = event.target?.dataset?.confirm;
    if (message && !window.confirm(message)) {
        event.preventDefault();
        event.stopImmediatePropagation();
    }
}, true);
