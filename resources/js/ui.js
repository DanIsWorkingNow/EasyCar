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
