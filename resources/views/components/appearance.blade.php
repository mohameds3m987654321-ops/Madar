<script>
    const hasThemeAccount = @json(auth()->check());
    window.madarThemePreference = {
        save(theme, pendingAccountSync = false) {
            try {
                localStorage.setItem('madar-theme', theme);
                if (pendingAccountSync) {
                    localStorage.setItem('madar-theme-pending', 'true');
                } else {
                    localStorage.removeItem('madar-theme-pending');
                }

                return true;
            } catch (error) {
                console.warn('Could not save the theme preference locally.', error);
                return false;
            }
        },
        markSynced() {
            try {
                localStorage.removeItem('madar-theme-pending');
            } catch (error) {
                console.warn('Could not clear the local theme sync marker.', error);
            }
        },
    };

    try {
        const savedTheme = localStorage.getItem('madar-theme');
        const hasPendingAccountSync = localStorage.getItem('madar-theme-pending') === 'true';
        if ((!hasThemeAccount || hasPendingAccountSync) && (savedTheme === 'light' || savedTheme === 'dark')) {
            document.documentElement.dataset.theme = savedTheme;
        }
    } catch (error) {
        console.warn('Could not read the saved theme preference.', error);
    }
</script>
@include('components.design-system')
@include('components.assistant-assets')
<script>
    window.madarToast = (message, kind = 'success') => {
        const previousToast = document.querySelector('.md-toast');
        previousToast?.remove();

        const toast = document.createElement('div');
        toast.className = 'md-toast';
        toast.dataset.kind = kind;
        toast.setAttribute('role', kind === 'error' ? 'alert' : 'status');
        toast.setAttribute('aria-live', kind === 'error' ? 'assertive' : 'polite');

        const indicator = document.createElement('span');
        indicator.className = 'md-toast-indicator';
        indicator.setAttribute('aria-hidden', 'true');

        const content = document.createElement('span');
        content.textContent = message;
        toast.append(indicator, content);
        document.body.append(toast);
        requestAnimationFrame(() => toast.classList.add('is-visible'));

        window.setTimeout(() => {
            toast.classList.remove('is-visible');
            window.setTimeout(() => toast.remove(), 220);
        }, 4000);
    };
</script>
