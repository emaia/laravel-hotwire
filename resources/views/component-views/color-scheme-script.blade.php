<script>
    (() => {
        const storageKey = @json($storageKey);
        const defaultMode = @json($default);
        const attribute = @json($attribute);
        let mode = defaultMode;

        try {
            mode = localStorage.getItem(storageKey) || defaultMode;
        } catch (error) {}

        if (!['light', 'dark', 'system'].includes(mode)) {
            mode = defaultMode;
        }

        const scheme = mode === 'system'
            ? (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
            : mode;

        document.documentElement.setAttribute(attribute, scheme);
        document.documentElement.setAttribute('data-color-scheme-mode', mode);
        document.documentElement.style.colorScheme = scheme;
    })();
</script>
