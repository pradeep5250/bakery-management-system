document.querySelectorAll('form[data-auth]').forEach(form => {
    form.addEventListener('submit', () => {
        const btn = form.querySelector('button[type=submit]');
        if (btn) { btn.disabled = true; btn.textContent = 'Signing in...'; }
    });
});
