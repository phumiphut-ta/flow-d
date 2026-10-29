import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Anti-Double Click Helper Component & Directive
document.addEventListener('alpine:init', () => {
    // Form submission debounce handler
    Alpine.data('antiDoubleClick', () => ({
        submitting: false,
        submit(e) {
            if (this.submitting) {
                e.preventDefault();
                return false;
            }
            this.submitting = true;
            return true;
        }
    }));
});

Alpine.start();
