        </main>
        <footer class="mt-4 text-center text-muted small">
            <div>Grand Oca Maragogi Resort</div>
            <div style="font-size:.72rem; color:#94a3b8; opacity:.75;">Desenvolvido por Gilson Matias</div>
        </footer>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Alternancia de tema claro/escuro com persistencia local.
(function () {
    const html = document.documentElement;
    const buttons = document.querySelectorAll('.js-theme-toggle');
    const iconClass = (theme) => theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';

    function applyTheme(theme) {
        html.setAttribute('data-theme', theme);
        buttons.forEach((btn) => {
            const icon = btn.querySelector('i');
            if (icon) icon.className = iconClass(theme);
            btn.setAttribute('aria-label', theme === 'dark' ? 'Ativar tema claro' : 'Ativar tema escuro');
            btn.setAttribute('title', theme === 'dark' ? 'Ativar tema claro' : 'Ativar tema escuro');
        });
    }

    try {
        const saved = localStorage.getItem('oca_theme');
        applyTheme(saved === 'dark' ? 'dark' : 'light');
    } catch (e) {
        applyTheme('light');
    }

    buttons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            applyTheme(next);
            try { localStorage.setItem('oca_theme', next); } catch (e) {}
        });
    });
})();

// Evita duplo envio acidental de formularios POST.
document.querySelectorAll('form[method="post"]:not([data-no-lock])').forEach((form) => {
    form.addEventListener('submit', () => {
        const submitBtn = form.querySelector('button[type="submit"], button:not([type])');
        if (!submitBtn) return;
        submitBtn.setAttribute('disabled', 'disabled');
        setTimeout(() => submitBtn.removeAttribute('disabled'), 5000);
    });
});
</script>
</body>
</html>
