        </main>
        <footer class="mt-4 text-center text-muted small">
            <div>Grand Oca Maragogi Resort</div>
            <div style="font-size:.72rem; color:#94a3b8; opacity:.75;">Desenvolvido por Gilson Matias</div>
        </footer>
    </div>
</div>
<div id="exportToastWrap" class="export-toast-wrap" aria-live="polite" aria-atomic="true"></div>
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

// Mantém o scroll do menu lateral entre mudanças de página (desktop).
(function () {
    const menu = document.querySelector('.sidebar-menu');
    if (!menu) return;
    const key = 'oca_sidebar_scroll_v2';

    try {
        const saved = sessionStorage.getItem(key);
        if (saved !== null) {
            menu.scrollTop = parseInt(saved, 10) || 0;
        }
    } catch (e) {}

    let timer = null;
    const persist = () => {
        try { sessionStorage.setItem(key, String(menu.scrollTop || 0)); } catch (e) {}
    };

    menu.addEventListener('scroll', () => {
        if (timer) window.clearTimeout(timer);
        timer = window.setTimeout(persist, 60);
    }, { passive: true });

    window.addEventListener('beforeunload', persist);
    document.querySelectorAll('.sidebar .nav-link').forEach((lnk) => {
        lnk.addEventListener('click', persist);
    });
})();

// Popup positivo para ações de exportação.
(function () {
    const wrap = document.getElementById('exportToastWrap');
    if (!wrap) return;

    function showExportToast(message) {
        const toast = document.createElement('div');
        toast.className = 'export-toast';
        toast.innerHTML =
            '<span class="ok-icon"><i class="bi bi-check-lg"></i></span>' +
            '<div class="txt">' + message + '</div>';
        wrap.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(6px) scale(.98)';
            toast.style.transition = 'all .2s ease';
            setTimeout(() => toast.remove(), 220);
        }, 2500);
    }

    document.querySelectorAll('a.js-export-btn').forEach((link) => {
        link.addEventListener('click', () => {
            const msg = link.getAttribute('data-toast') || 'Exportado com sucesso. O download foi iniciado.';
            showExportToast(msg);
        });
    });

    document.querySelectorAll('.alert.alert-success').forEach((el) => {
        const txt = (el.textContent || '').trim().toLowerCase();
        if (txt.includes('export')) {
            showExportToast('Exportado com sucesso.');
        }
    });
})();

// Confirmações visuais padrão (substitui confirm() nativo).
(function () {
    function getPayload(target) {
        if (!target) return null;
        const hasConfirmAttrs =
            target.hasAttribute('data-confirm') ||
            target.hasAttribute('data-confirm-title') ||
            target.hasAttribute('data-confirm-yes') ||
            target.hasAttribute('data-confirm-no') ||
            target.hasAttribute('data-confirm-type');
        if (!hasConfirmAttrs) return null;
        return {
            title: target.getAttribute('data-confirm-title') || 'Confirmar ação',
            message: target.getAttribute('data-confirm') || 'Deseja continuar?',
            confirmText: target.getAttribute('data-confirm-yes') || 'Sim',
            cancelText: target.getAttribute('data-confirm-no') || 'Não',
            type: target.getAttribute('data-confirm-type') || 'default'
        };
    }

    window.ocafConfirm = function (payload) {
        const cfg = Object.assign({
            title: 'Confirmar ação',
            message: 'Deseja continuar?',
            confirmText: 'Sim',
            cancelText: 'Não',
            type: 'default'
        }, payload || {});

        return new Promise((resolve) => {
            const wrap = document.createElement('div');
            wrap.className = 'app-confirm-wrap' + (cfg.type === 'danger' ? ' danger' : '');
            wrap.innerHTML =
                '<div class="app-confirm-card" role="dialog" aria-modal="true" aria-label="' + cfg.title + '">' +
                    '<div class="app-confirm-head">' +
                        '<span class="app-confirm-icon"><i class="bi ' + (cfg.type === 'danger' ? 'bi-exclamation-triangle-fill' : 'bi-question-lg') + '"></i></span>' +
                        '<div class="app-confirm-title">' + cfg.title + '</div>' +
                    '</div>' +
                    '<div class="app-confirm-message">' + cfg.message + '</div>' +
                    '<div class="app-confirm-actions">' +
                        '<button type="button" class="btn btn-outline-secondary btn-sm js-cancel">' + cfg.cancelText + '</button>' +
                        '<button type="button" class="btn ' + (cfg.type === 'danger' ? 'btn-danger' : 'btn-primary') + ' btn-sm js-ok">' + cfg.confirmText + '</button>' +
                    '</div>' +
                '</div>';

            let finished = false;
            const finish = (ok) => {
                if (finished) return;
                finished = true;
                document.removeEventListener('keydown', onKey, true);
                document.body.classList.remove('confirm-modal-open');
                wrap.remove();
                resolve(ok);
            };

            document.body.classList.add('confirm-modal-open');
            document.body.appendChild(wrap);
            requestAnimationFrame(() => wrap.classList.add('is-open'));

            const btnOk = wrap.querySelector('.js-ok');
            const btnCancel = wrap.querySelector('.js-cancel');
            if (btnOk) btnOk.focus();

            btnOk?.addEventListener('click', () => finish(true));
            btnCancel?.addEventListener('click', () => finish(false));
            wrap.addEventListener('click', (e) => {
                if (e.target === wrap) finish(false);
            });

            const onKey = (e) => {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    e.stopPropagation();
                    finish(false);
                    return;
                }
                if (e.key === 'Enter' && wrap.contains(document.activeElement)) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (document.activeElement === btnCancel) {
                        finish(false);
                    } else {
                        finish(true);
                    }
                }
            };
            document.addEventListener('keydown', onKey, true);
        });
    };

    document.addEventListener('submit', async (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;

        if (form.dataset.confirmPending === '1' || document.body.classList.contains('confirm-modal-open')) {
            e.preventDefault();
            return;
        }

        if (form.dataset.confirmed === '1') {
            form.dataset.confirmed = '0';
            return;
        }

        const submitter = e.submitter || document.activeElement;
        const source = (submitter && submitter.form === form) ? submitter : null;
        const payload = getPayload(source) || getPayload(form);
        if (!payload) return;

        e.preventDefault();
        form.dataset.confirmPending = '1';
        const ok = await window.ocafConfirm(payload);
        form.dataset.confirmPending = '0';
        if (!ok) return;

        form.dataset.confirmed = '1';
        if (typeof form.requestSubmit === 'function' && source) {
            form.requestSubmit(source);
        } else {
            form.submit();
        }
    }, true);
})();

// Evita duplo envio acidental de formularios POST.
document.querySelectorAll('form[method="post"]:not([data-no-lock])').forEach((form) => {
    form.addEventListener('submit', () => {
        if (form.dataset.confirmPending === '1') return;
        const submitBtn = form.querySelector('button[type="submit"], button:not([type])');
        if (!submitBtn) return;
        submitBtn.setAttribute('disabled', 'disabled');
        setTimeout(() => submitBtn.removeAttribute('disabled'), 5000);
    });
});

// Modo compacto para mobile.
(function () {
    const root = document.body;
    function syncCompact() {
        if (!root) return;
        root.classList.toggle('compact-mobile', window.matchMedia('(max-width: 576px)').matches);
    }
    syncCompact();
    window.addEventListener('resize', syncCompact, { passive: true });
})();

// Indicacao visual de rolagem lateral em tabelas responsivas.
(function () {
    const wrappers = Array.from(document.querySelectorAll('.table-responsive'));
    if (!wrappers.length) return;

    const update = (el) => {
        const max = Math.max(0, el.scrollWidth - el.clientWidth);
        const left = Math.max(0, el.scrollLeft || 0);
        const scrollable = max > 6;
        el.classList.toggle('is-scrollable', scrollable);
        el.classList.toggle('at-start', !scrollable || left <= 2);
        el.classList.toggle('at-end', !scrollable || left >= (max - 2));
    };

    wrappers.forEach((el) => {
        update(el);
        el.addEventListener('scroll', () => update(el), { passive: true });
    });

    window.addEventListener('resize', () => {
        wrappers.forEach((el) => update(el));
    }, { passive: true });

    if (typeof ResizeObserver !== 'undefined') {
        const ro = new ResizeObserver(() => {
            wrappers.forEach((el) => update(el));
        });
        wrappers.forEach((el) => ro.observe(el));
    }
})();

// Ícones sem repetição visual nos cabeçalhos de tabelas.
(function () {
    const iconMap = [
        [/status/i, 'bi-shield-check'],
        [/restaurante|área|area/i, 'bi-shop-window'],
        [/operaç|operac/i, 'bi-collection'],
        [/uh/i, 'bi-house-door'],
        [/pax|total|valor/i, 'bi-people'],
        [/usuário|usuario|responsável|responsavel/i, 'bi-person-badge'],
        [/data|hor[aá]rio|hora/i, 'bi-clock-history'],
        [/reserva/i, 'bi-bookmark-check'],
        [/anexo|voucher/i, 'bi-paperclip'],
    ];

    document.querySelectorAll('.table thead th').forEach((th) => {
        if (th.dataset.iconized === '1') return;
        const label = (th.textContent || '').trim();
        let iconClass = 'bi-dot';
        for (const [regex, foundClass] of iconMap) {
            if (regex.test(label)) {
                iconClass = foundClass;
                break;
            }
        }
        th.dataset.iconized = '1';
        th.innerHTML = '<span class="d-inline-flex align-items-center gap-1"><i class="bi ' + iconClass + '"></i><span>' + th.innerHTML + '</span></span>';
    });
})();
</script>
</body>
</html>
