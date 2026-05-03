        </main>
        <footer class="mt-4 text-center text-muted small">
            <div>Grand Oca Maragogi Resort</div>
            <div><a href="/?r=privacidade/index" class="text-muted">Privacidade e LGPD</a></div>
            <div style="font-size:.72rem; color:#94a3b8; opacity:.75;">Desenvolvido por Gilson Matias</div>
        </footer>
    </div>
</div>
<div id="exportToastWrap" class="export-toast-wrap" aria-live="polite" aria-atomic="true"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sistema de temas (multi-theme) com persistencia local.
(function () {
    const html = document.documentElement;
    const options = Array.from(document.querySelectorAll('.js-theme-option'));
    const labels = Array.from(document.querySelectorAll('.js-theme-label'));
    const allowed = ['light', 'dark', 'sand', 'ocean'];
    const labelsByTheme = {
        light: 'Tema claro',
        dark: 'Tema escuro',
        sand: 'Tema areia',
        ocean: 'Tema oceano'
    };

    function normalizeTheme(theme) {
        return allowed.includes(theme) ? theme : 'light';
    }

    function syncThemeUi(theme) {
        options.forEach((btn) => {
            const isActive = (btn.dataset.theme || '') === theme;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
        labels.forEach((node) => {
            node.innerHTML = '<i class="bi bi-palette2"></i> ' + (labelsByTheme[theme] || 'Tema');
        });
        document.querySelectorAll('.js-theme-logo').forEach((logo) => {
            const lightLogo = logo.getAttribute('data-logo-light') || logo.getAttribute('src') || '';
            const darkLogo = logo.getAttribute('data-logo-dark') || lightLogo;
            logo.setAttribute('src', theme === 'dark' ? darkLogo : lightLogo);
        });
    }

    function applyTheme(theme) {
        const safeTheme = normalizeTheme(theme);
        html.setAttribute('data-theme', safeTheme);
        syncThemeUi(safeTheme);
        try { localStorage.setItem('oca_theme', safeTheme); } catch (e) {}
    }

    let startTheme = 'light';
    try {
        startTheme = normalizeTheme(localStorage.getItem('oca_theme'));
    } catch (e) {}
    applyTheme(startTheme);

    options.forEach((btn) => {
        btn.addEventListener('click', () => {
            applyTheme(btn.dataset.theme || 'light');
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

    function showExportToast(message, options = {}) {
        const toast = document.createElement('div');
        toast.className = 'export-toast' + (options.progress ? ' progress-toast' : '');
        toast.innerHTML =
            '<span class="ok-icon"><i class="bi ' + (options.progress ? 'bi-arrow-repeat' : 'bi-check-lg') + '"></i></span>' +
            '<div class="txt">' + message + '</div>' +
            (options.progress ? '<div class="export-toast-progress is-indeterminate"><span></span></div><div class="export-toast-status">Preparando arquivo...</div>' : '');
        wrap.appendChild(toast);
        const remove = () => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(6px) scale(.98)';
            toast.style.transition = 'all .2s ease';
            setTimeout(() => toast.remove(), 220);
        };
        if (!options.persist) {
            setTimeout(remove, 2500);
        }
        return {
            setMessage(text) {
                const txt = toast.querySelector('.txt');
                if (txt) txt.textContent = text;
            },
            setProgress(percent, status) {
                const progress = toast.querySelector('.export-toast-progress');
                const bar = toast.querySelector('.export-toast-progress span');
                const label = toast.querySelector('.export-toast-status');
                if (progress) progress.classList.remove('is-indeterminate');
                if (bar) bar.style.width = Math.max(0, Math.min(100, percent)) + '%';
                if (label && status) label.textContent = status;
            },
            finish(text) {
                const icon = toast.querySelector('.ok-icon i');
                if (icon) icon.className = 'bi bi-check-lg';
                this.setProgress(100, 'Download iniciado.');
                this.setMessage(text || 'Exportação pronta. O download foi iniciado.');
                setTimeout(remove, 2500);
            },
            fail(text) {
                const icon = toast.querySelector('.ok-icon i');
                if (icon) icon.className = 'bi bi-exclamation-lg';
                toast.classList.add('export-toast-error');
                this.setMessage(text || 'Não foi possível preparar o download.');
                const progress = toast.querySelector('.export-toast-progress');
                if (progress) progress.classList.remove('is-indeterminate');
                setTimeout(remove, 4500);
            }
        };
    }

    function filenameFromDisposition(disposition, fallback) {
        if (!disposition) return fallback;
        const utf = disposition.match(/filename\*=UTF-8''([^;]+)/i);
        if (utf && utf[1]) {
            try { return decodeURIComponent(utf[1].replace(/["']/g, '')); } catch (e) { return utf[1]; }
        }
        const simple = disposition.match(/filename="?([^"]+)"?/i);
        return simple && simple[1] ? simple[1] : fallback;
    }

    function messageFromHtmlResponse(html) {
        if (!html || !window.DOMParser) {
            return '';
        }

        try {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const alert = doc.querySelector('.alert.alert-warning, .alert.alert-danger, .alert');
            return alert ? (alert.textContent || '').replace(/\s+/g, ' ').trim() : '';
        } catch (error) {
            return '';
        }
    }

    async function downloadWithProgress(link, event) {
        if (!window.fetch || !window.ReadableStream) {
            return;
        }

        event.preventDefault();
        const href = link.getAttribute('href');
        const msg = link.getAttribute('data-toast') || 'Preparando arquivo. O download será iniciado.';
        const toast = showExportToast(msg, { progress: true, persist: true });

        try {
            const response = await fetch(href, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json, application/octet-stream',
                    'X-Requested-With': 'fetch'
                }
            });
            const disposition = response.headers.get('Content-Disposition') || '';
            const contentType = response.headers.get('Content-Type') || '';
            if (!response.ok || !response.body || !/attachment/i.test(disposition)) {
                let message = '';
                const normalizedType = contentType.toLowerCase();
                if (normalizedType.includes('application/json')) {
                    const data = await response.json().catch(() => ({}));
                    message = data.message || '';
                } else if (normalizedType.includes('text/html')) {
                    message = messageFromHtmlResponse(await response.text());
                }
                toast.fail(message || 'Não há PDFs de vouchers para exportar nesta seleção.');
                return;
            }

            const total = parseInt(response.headers.get('Content-Length') || '0', 10);
            const reader = response.body.getReader();
            const chunks = [];
            let received = 0;
            toast.setMessage('Baixando arquivo preparado...');

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                chunks.push(value);
                received += value.length;
                if (total > 0) {
                    const percent = Math.round((received / total) * 100);
                    toast.setProgress(percent, percent + '% recebido');
                }
            }

            const blob = new Blob(chunks, { type: contentType || 'application/octet-stream' });
            const filename = filenameFromDisposition(disposition, 'exportacao.zip');
            const url = URL.createObjectURL(blob);
            const anchor = document.createElement('a');
            anchor.href = url;
            anchor.download = filename;
            document.body.appendChild(anchor);
            anchor.click();
            anchor.remove();
            setTimeout(() => URL.revokeObjectURL(url), 30000);
            toast.finish('Exportação pronta. O download foi iniciado.');
        } catch (error) {
            toast.fail('Falha ao acompanhar o progresso. Abrindo download direto...');
            setTimeout(() => {
                window.location.href = href;
            }, 900);
        }
    }

    document.querySelectorAll('a.js-export-btn').forEach((link) => {
        link.addEventListener('click', (event) => {
            if (link.getAttribute('data-progress-download') === '1') {
                downloadWithProgress(link, event);
                return;
            }
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
                        '<button type="button" class="btn btn-outline-secondary js-cancel">' + cfg.cancelText + '</button>' +
                        '<button type="button" class="btn ' + (cfg.type === 'danger' ? 'btn-danger' : 'btn-primary') + ' js-ok">' + cfg.confirmText + '</button>' +
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

// Atualizacao parcial para filtros GET em telas analiticas.
(function () {
    const parser = new DOMParser();
    let controller = null;

    function buildFormUrl(form, submitter) {
        const action = form.getAttribute('action') || window.location.pathname;
        const url = new URL(action, window.location.origin);
        const data = new FormData(form);
        if (submitter && submitter.name) {
            data.set(submitter.name, submitter.value || '');
        }
        url.search = new URLSearchParams(data).toString();
        return url;
    }

    function hardNavigate(url) {
        window.location.assign(url.toString());
    }

    async function executeScripts(container) {
        const scripts = Array.from(container.querySelectorAll('script'));
        for (const script of scripts) {
            const replacement = document.createElement('script');
            Array.from(script.attributes).forEach((attr) => {
                replacement.setAttribute(attr.name, attr.value);
            });

            if (script.src) {
                const src = script.src;
                const alreadyLoaded = Array.from(document.scripts).some((item) => item !== script && item.src === src);
                if (alreadyLoaded) {
                    script.remove();
                    continue;
                }
                await new Promise((resolve) => {
                    replacement.addEventListener('load', resolve, { once: true });
                    replacement.addEventListener('error', resolve, { once: true });
                    replacement.src = src;
                    script.replaceWith(replacement);
                });
                continue;
            }

            replacement.textContent = script.textContent || '';
            script.replaceWith(replacement);
        }
    }

    async function replaceTarget(url, selector, options = {}) {
        const target = document.querySelector(selector);
        if (!target) {
            hardNavigate(url);
            return;
        }

        const preserveScroll = options.preserveScroll === true;
        const previousScrollX = window.scrollX;
        const previousScrollY = window.scrollY;

        if (controller) controller.abort();
        controller = new AbortController();
        target.classList.add('is-ajax-loading');
        target.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url.toString(), {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'fetch',
                },
                signal: controller.signal,
            });
            if (!response.ok) {
                hardNavigate(url);
                return;
            }

            const html = await response.text();
            const doc = parser.parseFromString(html, 'text/html');
            const next = doc.querySelector(selector);
            if (!next) {
                hardNavigate(url);
                return;
            }

            target.replaceWith(next);
            const current = document.querySelector(selector);
            if (current) {
                await executeScripts(current);
            }
            if (doc.title) {
                document.title = doc.title;
            }
            window.history.pushState({ ajaxFilter: true, selector }, '', url.toString());
            document.dispatchEvent(new CustomEvent('fbcontrol:partial-update', {
                detail: { url: url.toString(), selector },
            }));
            if (preserveScroll) {
                window.requestAnimationFrame(() => {
                    window.scrollTo(previousScrollX, previousScrollY);
                });
            } else if (current && current.matches('.app-content')) {
                current.scrollIntoView({ block: 'start', behavior: 'smooth' });
            }
        } catch (error) {
            if (error && error.name === 'AbortError') return;
            hardNavigate(url);
        } finally {
            const current = document.querySelector(selector);
            if (current) {
                current.classList.remove('is-ajax-loading');
                current.removeAttribute('aria-busy');
            }
        }
    }

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (!form.matches('[data-ajax-filter]')) return;
        if ((form.getAttribute('method') || 'get').toLowerCase() !== 'get') return;

        event.preventDefault();
        const submitter = event.submitter || document.activeElement;
        const source = (submitter && submitter.form === form) ? submitter : null;
        const selector = form.getAttribute('data-ajax-target') || '.app-main';
        replaceTarget(buildFormUrl(form, source), selector, {
            preserveScroll: form.getAttribute('data-ajax-preserve-scroll') === '1',
        });
    });

    document.addEventListener('click', (event) => {
        const link = event.target instanceof Element ? event.target.closest('a[data-ajax-link]') : null;
        if (!link) return;
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#')) return;

        event.preventDefault();
        const selector = link.getAttribute('data-ajax-target') || '.app-main';
        replaceTarget(new URL(href, window.location.origin), selector, {
            preserveScroll: link.closest('.pagination') !== null || link.getAttribute('data-ajax-preserve-scroll') === '1',
        });
    });

    document.addEventListener('click', (event) => {
        const btn = event.target instanceof Element ? event.target.closest('[data-range]') : null;
        if (!btn) return;
        const form = btn.closest('form');
        if (!form) return;
        const start = form.querySelector('input[name="data_inicio"]');
        const end = form.querySelector('input[name="data_fim"]');
        const single = form.querySelector('input[name="data"]');
        if (!start || !end) return;

        const days = parseInt(btn.getAttribute('data-range') || '0', 10);
        if (!days) return;
        if (single) single.value = '';
        const fmt = (date) => date.toISOString().slice(0, 10);
        const today = new Date();
        const from = new Date(today);
        if (days === 1) {
            from.setDate(today.getDate() - 1);
            start.value = fmt(from);
            end.value = fmt(from);
            return;
        }
        from.setDate(today.getDate() - (days - 1));
        start.value = fmt(from);
        end.value = fmt(today);
    });

    document.addEventListener('change', (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement)) return;
        const form = input.closest('form[data-ajax-filter]');
        if (!form) return;

        const groups = [
            ['data', 'data_inicio', 'data_fim'],
            ['flow_data', 'flow_data_inicio', 'flow_data_fim'],
            ['candle_data', 'candle_data_inicio', 'candle_data_fim'],
        ];

        groups.forEach(([singleName, startName, endName]) => {
            if (input.name === singleName && input.value !== '') {
                const start = form.querySelector(`input[name="${startName}"]:not([type="hidden"])`);
                const end = form.querySelector(`input[name="${endName}"]:not([type="hidden"])`);
                if (start) start.value = '';
                if (end) end.value = '';
            }

            if ((input.name === startName || input.name === endName) && input.value !== '') {
                const single = form.querySelector(`input[name="${singleName}"]:not([type="hidden"])`);
                if (single) single.value = '';
            }
        });
    });

    window.addEventListener('popstate', () => {
        window.location.reload();
    });
})();

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

// Modo simplificado para perfis operacionais no mobile/tablet.
(function () {
    const root = document.body;
    if (!root) return;
    const role = (root.getAttribute('data-role') || '').toLowerCase();
    const simpleRoles = ['hostess', 'supervisor', 'gerente'];

    function syncSimpleMode() {
        const isMobile = window.matchMedia('(max-width: 992px)').matches;
        const enabled = isMobile && simpleRoles.includes(role);
        root.classList.toggle('mobile-beginner', enabled);
    }

    syncSimpleMode();
    window.addEventListener('resize', syncSimpleMode, { passive: true });
})();

// Garante rolagem horizontal para tabelas que vieram sem wrapper responsivo.
(function () {
    const tables = Array.from(document.querySelectorAll('table.table'));
    if (!tables.length) return;

    tables.forEach((table) => {
        if (!table || table.dataset.noAutoWrap === '1') return;
        if (table.classList.contains('table-editor')) return;
        if (table.closest('.table-responsive, .saas-table-scroll, .js-no-auto-wrap')) return;

        const wrapper = document.createElement('div');
        wrapper.className = 'table-responsive auto-table-wrap';
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);
    });
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

// Paginacao automatica para tabelas de listagem (dashboard, relatorios e modulos operacionais).
(function () {
    const body = document.body;
    if (!body) return;

    const route = String(body.dataset.route || '').toLowerCase();
    if (route.endsWith('/print') || route.includes('/print')) return;

    const hasManualPagerNear = (table) => {
        const wrapper = table.closest('.table-responsive') || table.parentElement;
        if (!wrapper) return false;
        let next = wrapper.nextElementSibling;
        let guard = 0;
        while (next && guard < 3) {
            const id = String(next.id || '').toLowerCase();
            const cls = String(next.className || '').toLowerCase();
            if (id.includes('pagination') || cls.includes('pagination')) {
                return true;
            }
            next = next.nextElementSibling;
            guard += 1;
        }
        return false;
    };

    const createPager = (table, pageSize, pagerId) => {
        const tbody = table.tBodies && table.tBodies[0] ? table.tBodies[0] : null;
        if (!tbody) return;

        const rows = Array.from(tbody.rows).filter((row) => !row.classList.contains('js-no-auto-pagination'));
        if (rows.length <= pageSize) return;

        const pager = document.createElement('div');
        pager.className = 'auto-table-pagination d-flex flex-wrap align-items-center gap-2 mt-2';
        pager.id = pagerId;

        const range = document.createElement('span');
        range.className = 'text-muted small me-1';
        pager.appendChild(range);

        const nav = document.createElement('div');
        nav.className = 'd-flex flex-wrap gap-2';
        pager.appendChild(nav);

        const wrapper = table.closest('.table-responsive') || table;
        wrapper.insertAdjacentElement('afterend', pager);

        let currentPage = 1;
        const totalPages = Math.max(1, Math.ceil(rows.length / pageSize));

        const render = () => {
            const start = (currentPage - 1) * pageSize;
            const end = start + pageSize;
            rows.forEach((row, idx) => {
                row.style.display = (idx >= start && idx < end) ? '' : 'none';
            });

            range.textContent = `Mostrando ${Math.min(rows.length, start + 1)}-${Math.min(rows.length, end)} de ${rows.length}`;

            nav.innerHTML = '';
            const maxButtons = 7;
            const pages = [];
            for (let p = 1; p <= totalPages; p++) {
                if (
                    p === 1 ||
                    p === totalPages ||
                    (p >= currentPage - 1 && p <= currentPage + 1) ||
                    (currentPage <= 3 && p <= 4) ||
                    (currentPage >= totalPages - 2 && p >= totalPages - 3)
                ) {
                    pages.push(p);
                }
            }
            const uniquePages = Array.from(new Set(pages)).sort((a, b) => a - b);
            let last = 0;
            uniquePages.forEach((p) => {
                if (p - last > 1) {
                    const dots = document.createElement('span');
                    dots.className = 'text-muted small px-1';
                    dots.textContent = '...';
                    nav.appendChild(dots);
                }
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `btn btn-sm ${p === currentPage ? 'btn-primary' : 'btn-outline-primary'}`;
                btn.textContent = String(p);
                btn.addEventListener('click', () => {
                    currentPage = p;
                    render();
                });
                nav.appendChild(btn);
                last = p;
            });

            if (uniquePages.length > maxButtons) {
                nav.style.maxWidth = '100%';
            }
        };

        render();
    };

    function initAutoTablePagination(root = document) {
        const tableNodes = Array.from(root.querySelectorAll('table.table'));
        const defaultPageSize = window.matchMedia('(max-width: 768px)').matches ? 8 : 12;
        let autoIndex = document.querySelectorAll('.auto-table-pagination').length;

        tableNodes.forEach((table) => {
            if (!table || table.dataset.noAutoPagination === '1') return;
            if (table.dataset.autoPaginationReady === '1') return;
            if (table.closest('.js-no-auto-pagination')) return;
            if (hasManualPagerNear(table)) return;

            const tbody = table.tBodies && table.tBodies[0] ? table.tBodies[0] : null;
            if (!tbody) return;
            if (tbody.rows.length <= defaultPageSize) return;

            autoIndex += 1;
            table.dataset.autoPaginationReady = '1';
            const pageSize = Math.max(5, parseInt(table.dataset.pageSize || String(defaultPageSize), 10) || defaultPageSize);
            createPager(table, pageSize, `autoTablePagination${autoIndex}`);
        });
    }

    initAutoTablePagination(document);
    document.addEventListener('fbcontrol:partial-update', (event) => {
        const selector = event.detail && event.detail.selector ? String(event.detail.selector) : '';
        const root = selector ? document.querySelector(selector) : document;
        initAutoTablePagination(root || document);
    });
})();

// Guia contextual por página e perfil (aberto somente pelo usuário).
(function () {
    const body = document.body;
    if (!body) return;

    const route = String(body.dataset.route || '').toLowerCase();
    const role = String(body.dataset.role || 'guest').toLowerCase();
    const userId = String(body.dataset.userId || '0');
    if (!route || role === 'guest' || userId === '0') return;

    const allowedRoles = ['hostess', 'supervisor', 'gerente', 'admin'];
    if (!allowedRoles.includes(role)) return;

    const routeAllowListByRole = {
        hostess: [
            'access/index',
            'hostess/turnos',
            'reservastematicas/operacao',
            'reservastematicas/reservas',
            'vouchers/index',
        ],
        supervisor: [
            'control/index',
            'dashboard/index',
            'dashboard/restaurant',
            'kpis/index',
            'relatorios/index',
            'relatoriostematicos/index',
            'reservastematicas/operacao',
            'reservastematicas/reservas',
            'vouchers/index',
        ],
        gerente: [
            'control/index',
            'dashboard/index',
            'dashboard/restaurant',
            'kpis/index',
            'relatorios/index',
        ],
        admin: [
            'access/index',
            'hostess/turnos',
            'vouchers/index',
            'reservastematicas/reservas',
            'reservastematicas/operacao',
            'reservastematicas/admin',
            'control/index',
            'dashboard/index',
            'dashboard/restaurant',
            'kpis/index',
            'relatorios/index',
            'relatoriostematicos/index',
            'auditoria/index',
            'lgpd/index',
            'restaurantes/index',
            'portas/index',
            'operacoes/index',
            'horarios/index',
            'usuarios/index',
            'emailrelatorios/index',
        ],
    };

    const allowedRoutes = routeAllowListByRole[role] || [];
    if (!allowedRoutes.includes(route)) return;

    const key = `fbcontrol_guide_seen_v1:${userId}:${role}:${route}`;
    const openButtons = Array.from(document.querySelectorAll('.js-open-tour'));

    function pageSteps(currentRoute, currentRole) {
        const map = {
            'access/index': [
                {
                    kicker: 'Módulo Registro',
                    title: 'Para que serve',
                    text: 'Use este módulo para registrar acessos aos restaurantes durante a operação ativa.',
                    items: [
                        'Cada lançamento fica associado ao usuário, restaurante, operação, porta e horário.',
                        'O sistema valida UH, PAX, duplicidades, regras de múltiplos acessos e alertas operacionais.',
                        'Sempre confira restaurante e operação antes de iniciar o turno.',
                    ],
                    tip: 'Atalhos especiais: 998 para Não informado e 999 para Day use, quando essa regra estiver habilitada.',
                },
                {
                    selector: 'form[action="/?r=turnos/start"]',
                    kicker: 'Registro',
                    title: '1. Iniciar turno',
                    text: 'Escolha restaurante, operação e porta, quando aplicável. O registro só abre depois do turno iniciado.',
                },
                {
                    selector: 'input[name="uh_numero"]',
                    kicker: 'Registro',
                    title: '2. Digitar UH',
                    text: 'Informe a UH do hóspede. A UH deve existir na base operacional, exceto códigos especiais permitidos.',
                },
                {
                    selector: 'input[name="pax"]',
                    kicker: 'Registro',
                    title: '3. Ajustar PAX',
                    text: 'Ajuste a quantidade de PAX antes de registrar. PAX incorreto distorce relatórios e indicadores.',
                },
                {
                    selector: 'form[action="/?r=access/register"] button[type="submit"]',
                    kicker: 'Registro',
                    title: '4. Registrar',
                    text: 'Finalize o lançamento somente depois de conferir UH e PAX. Alertas aparecem na hora quando houver regra sensível.',
                    tip: 'Múltiplos acessos são tratados por regra: mesma UH, PAX diferente e intervalo maior que 15 minutos.',
                },
            ],
            'hostess/turnos': [
                {
                    kicker: 'Módulo Meus turnos',
                    title: 'Para que serve',
                    text: 'Mostra o histórico de turnos realizados pela hostess, com status e informações da operação.',
                    items: [
                        'Use para conferir se os turnos foram iniciados e encerrados corretamente.',
                        'Ajuda a acompanhar produtividade e histórico operacional individual.',
                    ],
                },
                {
                    selector: '.app-content .card',
                    kicker: 'Meus turnos',
                    title: 'Resumo dos seus turnos',
                    text: 'Aqui você acompanha turnos concluídos, cancelados e o histórico diário da sua operação.',
                },
            ],
            'control/index': [
                {
                    kicker: 'Módulo Centro de Controle',
                    title: 'Para que serve',
                    text: 'Concentra a leitura do dia para supervisão, gerência e administração.',
                    items: [
                        'Acompanhe turnos ativos, registros recentes, alertas e indicadores do dia.',
                        'Use como painel de acompanhamento durante a operação.',
                        'Não substitui relatório detalhado; ele mostra o pulso operacional.',
                    ],
                },
                {
                    selector: '.control-kpis',
                    kicker: 'Centro de Controle',
                    title: 'Visão executiva',
                    text: 'Esses cards mostram o pulso operacional do dia para tomada de decisão rápida.',
                },
                {
                    selector: '.saas-table-card',
                    kicker: 'Centro de Controle',
                    title: 'Operação em andamento',
                    text: currentRole === 'supervisor'
                        ? 'Acompanhe turnos ativos e faça intervenções quando necessário.'
                        : 'Use esta visão para acompanhar o andamento da operação e apoiar decisões do dia.',
                },
                {
                    selector: '.control-pagination',
                    kicker: 'Centro de Controle',
                    title: 'Histórico paginado',
                    text: 'Navegue pelas páginas para auditar todos os registros do dia.',
                },
            ],
            'dashboard/index': [
                {
                    kicker: 'Módulo Dashboard Geral',
                    title: 'Para que serve',
                    text: 'Mostra indicadores consolidados da operação por período, restaurante, operação e status.',
                    items: [
                        'Use filtros para evitar comparar contextos diferentes.',
                        'Leia primeiro os KPIs e depois os gráficos/tabelas de apoio.',
                        'Fluxo por horário ajuda a entender concentração de acessos durante o serviço.',
                    ],
                },
                {
                    selector: '.saas-hero-card',
                    kicker: 'Dashboard Geral',
                    title: 'Filtros estratégicos',
                    text: currentRole === 'gerente'
                        ? 'Defina período e status para leitura executiva da operação e comparação entre dias.'
                        : 'Defina período e status para analisar tendências operacionais e qualidade da execução.',
                },
                {
                    selector: '.saas-kpi-grid',
                    kicker: 'Dashboard Geral',
                    title: 'KPIs do período',
                    text: currentRole === 'gerente'
                        ? 'Aqui ficam os indicadores que apoiam decisão rápida de gestão.'
                        : 'Aqui ficam os indicadores principais para comparações rápidas por contexto.',
                },
                {
                    selector: '.saas-table-card',
                    kicker: 'Dashboard Geral',
                    title: 'Distribuição e monitoramento',
                    text: currentRole === 'gerente'
                        ? 'As tabelas mostram distribuição de resultados e comportamento recente da operação.'
                        : 'As tabelas mostram a composição dos resultados e os últimos eventos lançados.',
                },
            ],
            'dashboard/restaurant': [
                {
                    kicker: 'Módulo Dashboard do Restaurante',
                    title: 'Para que serve',
                    text: 'Analisa uma unidade/restaurante específico, com filtros e indicadores focados.',
                    items: [
                        'Use quando precisar entender comportamento de uma operação pontual.',
                        'Compare status, horários e ocorrências recentes.',
                    ],
                },
                {
                    selector: '.saas-hero-card',
                    kicker: 'Dashboard do Restaurante',
                    title: 'Contexto do restaurante',
                    text: 'Use filtros por data e status para focar o comportamento do restaurante selecionado.',
                },
                {
                    selector: '.saas-kpi-grid',
                    kicker: 'Dashboard do Restaurante',
                    title: 'Indicadores focados',
                    text: 'Esses KPIs mostram produtividade, alertas e consistência operacional da unidade.',
                },
                {
                    selector: '.saas-table-card',
                    kicker: 'Dashboard do Restaurante',
                    title: 'Detalhamento analítico',
                    text: 'Cruze operações, horários e histórico recente para identificar gargalos e oportunidades.',
                },
            ],
            'relatorios/index': [
                {
                    kicker: 'Módulo Relatórios',
                    title: 'Para que serve',
                    text: 'Permite consultar e exportar registros operacionais com filtros detalhados.',
                    items: [
                        'Use período, UH, restaurante, operação e status para chegar no recorte correto.',
                        'Exportações servem para conferência, BI, auditoria e prestação de contas.',
                        'Se não houver dados no filtro, ajuste data ou contexto antes de exportar.',
                    ],
                },
                {
                    selector: 'form[action="/"]',
                    kicker: 'Relatórios',
                    title: 'Construção do relatório',
                    text: 'Combine filtros de período, UH, restaurante, operação e status para extrair cenários específicos.',
                },
                {
                    selector: '.js-export-btn',
                    kicker: 'Relatórios',
                    title: 'Exportação operacional',
                    text: 'Exporte CSV/Excel para BI, auditoria e reuniões de performance.',
                },
                {
                    selector: '.split-full, .saas-table-card, .card',
                    kicker: 'Relatórios',
                    title: 'Leitura em camadas',
                    text: 'Leia os cards primeiro e depois aprofunde nas tabelas detalhadas.',
                },
            ],
            'kpis/index': [
                {
                    kicker: 'Módulo KPIs Estratégicos',
                    title: 'Para que serve',
                    text: 'Apoia análises gerenciais com indicadores mais amplos de consumo, ocupação e previsibilidade.',
                    items: [
                        'Informe período e ocupação quando o cálculo depender de demanda.',
                        'Use para leitura gerencial, não para registro operacional.',
                    ],
                },
                {
                    selector: 'form[action="/?r=kpis/index"]',
                    kicker: 'KPIs Estratégicos',
                    title: 'KPIs estratégicos',
                    text: 'Ajuste período e ocupação para comparar consumo real versus demanda do dia.',
                },
                {
                    selector: '.kpi-candles, .kpi-chart-card, .kpi-stat-grid',
                    kicker: 'KPIs Estratégicos',
                    title: 'Visões analíticas',
                    text: 'Os blocos gráficos ajudam a identificar tendência, desvio e previsibilidade operacional.',
                },
            ],
            'reservastematicas/reservas': [
                {
                    kicker: 'Módulo Reservas Temáticas',
                    title: 'Para que serve',
                    text: 'Cadastra e consulta reservas dos restaurantes temáticos.',
                    items: [
                        'Registre UH, PAX, restaurante, turno e observações com cuidado.',
                        'A reserva alimenta a tela de Operação Temática no dia do serviço.',
                        'Status e horários ajudam a equipe a organizar atendimento e capacidade.',
                    ],
                },
                {
                    selector: 'form[action="/?r=reservasTematicas/reservas"]',
                    kicker: 'Reservas Temáticas',
                    title: 'Cadastro de reservas',
                    text: 'Registre UH, PAX, turno e observações para montar a base da operação temática.',
                },
                {
                    selector: '.table-responsive',
                    kicker: 'Reservas Temáticas',
                    title: 'Controle de capacidade',
                    text: 'Acompanhe status e lotação por turno para evitar sobrecarga no atendimento.',
                },
            ],
            'reservastematicas/operacao': [
                {
                    kicker: 'Módulo Operação Temática',
                    title: 'Para que serve',
                    text: 'Apoia a hostess na conferência das reservas temáticas durante o serviço.',
                    items: [
                        'Use a busca por UH para localizar reservas rapidamente.',
                        'Atualize status conforme a execução: reservada, finalizada ou não compareceu.',
                        'A lista do dia serve como apoio panorâmico, não como única forma de localizar reservas.',
                    ],
                },
                {
                    selector: '.section-block, .saas-hero-card',
                    kicker: 'Operação Temática',
                    title: 'Conferência operacional',
                    text: 'Aqui a hostess valida reservas, atualiza status e prepara a execução do serviço.',
                },
                {
                    selector: '.table-responsive',
                    kicker: 'Operação Temática',
                    title: 'Base do turno',
                    text: 'Use busca e filtros para localizar rapidamente cada reserva durante a operação.',
                },
            ],
            'reservastematicas/admin': [
                {
                    kicker: 'Módulo Config. Temáticas',
                    title: 'Para que serve',
                    text: 'Permite ajustar regras operacionais dos restaurantes temáticos.',
                    items: [
                        'Configure capacidades por restaurante, turno e data futura quando houver exceção.',
                        'Ajustes aqui impactam a distribuição de reservas e a operação do dia.',
                        'Use com critério e valide a data antes de salvar.',
                    ],
                    tip: 'Supervisores podem usar esta tela para ajustes operacionais conforme necessidade do serviço.',
                },
                {
                    selector: 'form, .section-block, .card',
                    kicker: 'Config. Temáticas',
                    title: 'Ajustes de capacidade',
                    text: 'Revise restaurante, turnos e totais antes de salvar. Capacidades erradas afetam reservas futuras.',
                },
            ],
            'relatoriostematicos/index': [
                {
                    kicker: 'Módulo Relatórios Temáticos',
                    title: 'Para que serve',
                    text: 'Mostra desempenho das reservas temáticas, comparecimento e no-show.',
                    items: [
                        'Use período e restaurante para analisar uma operação específica.',
                        'A base detalhada mostra a reserva e o usuário responsável pelo lançamento, quando disponível.',
                        'Acompanhe no-show e finalizações para melhorar planejamento de capacidade.',
                    ],
                },
                {
                    selector: 'form[action="/"]',
                    kicker: 'Relatórios Temáticos',
                    title: 'Filtros temáticos',
                    text: 'Defina o período e o restaurante para acompanhar desempenho de reservas e no-show.',
                },
                {
                    selector: '.saas-table-card, .table-responsive',
                    kicker: 'Relatórios Temáticos',
                    title: 'Leitura operacional',
                    text: 'Use os blocos para acompanhar comparecimento, cancelamentos e ocupação por turno.',
                },
            ],
            'vouchers/index': [
                {
                    kicker: 'Módulo Vouchers',
                    title: 'Para que serve',
                    text: 'Registra vouchers recebidos e anexos para conferência financeira e operacional.',
                    items: [
                        'Preencha dados essenciais e anexe comprovantes quando houver.',
                        'O histórico ajuda em auditorias e conferências posteriores.',
                        'Use exportação de PDFs quando precisar consolidar anexos por data.',
                    ],
                },
                {
                    selector: 'form[action="/?r=vouchers/index"]',
                    kicker: 'Vouchers',
                    title: 'Registro de vouchers',
                    text: 'Cadastre os vouchers recebidos no turno para auditoria financeira e operacional.',
                },
                {
                    selector: '.table-responsive',
                    kicker: 'Vouchers',
                    title: 'Histórico e conferência',
                    text: 'Revise anexos, origem e dados essenciais para validação posterior.',
                },
            ],
            'auditoria/index': [
                {
                    kicker: 'Módulo Auditoria',
                    title: 'Para que serve',
                    text: 'Centraliza eventos importantes do sistema para rastreabilidade e conferência.',
                    items: [
                        'Use filtros por data, módulo e usuário para investigar alterações ou eventos.',
                        'A auditoria não é tela operacional; ela serve para controle e responsabilização.',
                        'Sempre alinhe período e módulo antes de concluir uma análise.',
                    ],
                },
                {
                    selector: 'form[action="/"], form',
                    kicker: 'Auditoria',
                    title: 'Filtros de auditoria',
                    text: 'Defina data e contexto para evitar misturar eventos de dias ou módulos diferentes.',
                },
                {
                    selector: '.table-responsive, .card, .saas-table-card',
                    kicker: 'Auditoria',
                    title: 'Histórico auditável',
                    text: 'A tabela mostra registros com dados suficientes para conferência técnica e operacional.',
                },
            ],
            'lgpd/index': [
                {
                    kicker: 'Módulo LGPD',
                    title: 'Para que serve',
                    text: 'Apoia o controle de privacidade, termos e solicitações relacionadas a dados pessoais.',
                    items: [
                        'Use apenas quando houver necessidade administrativa ou solicitação formal.',
                        'Evite expor informações sensíveis fora do fluxo correto.',
                    ],
                },
            ],
            'restaurantes/index': [
                {
                    kicker: 'Cadastro de Restaurantes',
                    title: 'Para que serve',
                    text: 'Mantém a lista de restaurantes/unidades usadas em registros, dashboards e relatórios.',
                    items: [
                        'Cadastre nomes claros e consistentes.',
                        'Evite excluir ou alterar cadastros com histórico sem avaliar impacto nos relatórios.',
                    ],
                },
            ],
            'portas/index': [
                {
                    kicker: 'Cadastro de Portas',
                    title: 'Para que serve',
                    text: 'Controla pontos de entrada usados no registro operacional.',
                    items: [
                        'Use quando uma operação tiver mais de uma porta ou ponto de controle.',
                        'Nomes simples ajudam a hostess a escolher corretamente no início do turno.',
                    ],
                },
            ],
            'operacoes/index': [
                {
                    kicker: 'Cadastro de Operações',
                    title: 'Para que serve',
                    text: 'Define operações como café, almoço, jantar ou eventos específicos.',
                    items: [
                        'Operações aparecem em filtros, turnos e relatórios.',
                        'Evite duplicar operações com nomes parecidos.',
                    ],
                },
            ],
            'horarios/index': [
                {
                    kicker: 'Cadastro de Horários',
                    title: 'Para que serve',
                    text: 'Controla janelas de horário usadas para validar registros e leitura operacional.',
                    items: [
                        'Horários impactam regras de fora do horário e fluxo por período.',
                        'Altere somente quando a regra da operação mudar de fato.',
                    ],
                },
            ],
            'usuarios/index': [
                {
                    kicker: 'Cadastro de Usuários',
                    title: 'Para que serve',
                    text: 'Gerencia acessos, perfis e permissões do sistema.',
                    items: [
                        'Cada perfil deve receber apenas o acesso necessário para sua função.',
                        'Revise usuários inativos periodicamente.',
                        'Perfis influenciam menu, módulos e permissões de ação.',
                    ],
                },
            ],
            'emailrelatorios/index': [
                {
                    kicker: 'E-mail Diário',
                    title: 'Para que serve',
                    text: 'Configura destinatários e rotinas de envio de relatórios por e-mail.',
                    items: [
                        'Use para automatizar acompanhamento da operação.',
                        'Mantenha destinatários atualizados para evitar envio desnecessário.',
                    ],
                },
            ],
        };

        return map[currentRoute] || [];
    }

    function sanitizeSteps(steps) {
        const rows = [];
        (steps || []).forEach((step) => {
            if (!step || typeof step !== 'object') return;
            if (!step.selector) {
                rows.push(step);
                return;
            }
            const el = document.querySelector(step.selector);
            if (el) {
                rows.push(Object.assign({}, step, { _resolvedSelector: step.selector }));
            }
        });
        return rows;
    }

    const steps = sanitizeSteps(pageSteps(route, role));
    if (!steps.length) {
        openButtons.forEach((btn) => btn.classList.add('d-none'));
        return;
    }

    const overlay = document.createElement('div');
    overlay.className = 'tour-overlay';
    overlay.innerHTML = `
        <div class="tour-backdrop"></div>
        <div class="tour-highlight is-hidden"></div>
        <div class="tour-popover" role="dialog" aria-modal="true" aria-label="Guia da página">
            <div class="tour-kicker"></div>
            <div class="tour-title"></div>
            <div class="tour-text"></div>
            <div class="tour-footer">
                <div class="tour-progress"></div>
                <div class="tour-actions">
                    <button type="button" class="btn btn-outline-secondary btn-sm js-tour-close">Fechar</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm js-tour-skip">Pular</button>
                    <button type="button" class="btn btn-outline-primary btn-sm js-tour-prev">Voltar</button>
                    <button type="button" class="btn btn-primary btn-sm js-tour-next">Próximo</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);

    const highlight = overlay.querySelector('.tour-highlight');
    const pop = overlay.querySelector('.tour-popover');
    const kicker = overlay.querySelector('.tour-kicker');
    const title = overlay.querySelector('.tour-title');
    const text = overlay.querySelector('.tour-text');
    const progress = overlay.querySelector('.tour-progress');
    const btnClose = overlay.querySelector('.js-tour-close');
    const btnPrev = overlay.querySelector('.js-tour-prev');
    const btnNext = overlay.querySelector('.js-tour-next');
    const btnSkip = overlay.querySelector('.js-tour-skip');

    let index = 0;
    let currentTarget = null;

    function markSeen() {
        try { localStorage.setItem(key, '1'); } catch (e) {}
    }

    function hasSeen() {
        try { return localStorage.getItem(key) === '1'; } catch (e) { return false; }
    }

    function closeTour(markAsSeen) {
        currentTarget = null;
        highlight.classList.add('is-hidden');
        overlay.classList.remove('is-open');
        overlay.style.display = 'none';
        document.body.classList.remove('tour-open');
        if (markAsSeen) markSeen();
    }

    function clamp(v, min, max) {
        return Math.max(min, Math.min(max, v));
    }

    function appendTextBlock(parent, value) {
        if (!value) return;
        const values = Array.isArray(value) ? value : [value];
        values.forEach((line) => {
            if (!line) return;
            const p = document.createElement('p');
            p.textContent = String(line);
            parent.appendChild(p);
        });
    }

    function renderStepText(step) {
        text.replaceChildren();
        appendTextBlock(text, step.text);

        if (Array.isArray(step.items) && step.items.length) {
            const ul = document.createElement('ul');
            step.items.forEach((item) => {
                if (!item) return;
                const li = document.createElement('li');
                li.textContent = String(item);
                ul.appendChild(li);
            });
            if (ul.children.length) text.appendChild(ul);
        }

        if (step.tip) {
            const note = document.createElement('div');
            note.className = 'tour-note';
            const icon = document.createElement('i');
            icon.className = 'bi bi-lightbulb';
            const span = document.createElement('span');
            span.textContent = String(step.tip);
            note.append(icon, span);
            text.appendChild(note);
        }
    }

    function positionPopover(rect) {
        const margin = 10;
        const vw = window.innerWidth;
        const vh = window.innerHeight;

        if (!rect) {
            const w = pop.offsetWidth || 340;
            const h = pop.offsetHeight || 220;
            const left = Math.round((window.innerWidth - w) / 2);
            const top = Math.round((window.innerHeight - h) / 2);
            const maxLeft = Math.max(margin, vw - w - margin);
            const maxTop = Math.max(margin, vh - h - margin);
            pop.style.left = `${Math.round(clamp(left, margin, maxLeft))}px`;
            pop.style.top = `${Math.round(clamp(top, margin, maxTop))}px`;
            return;
        }

        const popW = pop.offsetWidth || 340;
        const popH = pop.offsetHeight || 220;
        const maxLeft = Math.max(margin, vw - popW - margin);
        const maxTop = Math.max(margin, vh - popH - margin);

        let left = rect.left + (rect.width / 2) - (popW / 2);
        left = clamp(left, margin, maxLeft);

        let top = rect.bottom + 14;
        if ((top + popH) > (vh - margin)) {
            top = rect.top - popH - 14;
        }
        top = clamp(top, margin, maxTop);

        pop.style.left = `${Math.round(left)}px`;
        pop.style.top = `${Math.round(top)}px`;
    }

    function renderStep() {
        const step = steps[index];
        if (!step) return;
        currentTarget = null;

        kicker.textContent = step.kicker || 'Guia do módulo';
        title.textContent = step.title || 'Guia rapido';
        renderStepText(step);
        progress.textContent = `Passo ${index + 1} de ${steps.length}`;
        btnPrev.disabled = index === 0;
        btnNext.textContent = index === (steps.length - 1) ? 'Concluir' : 'Próximo';

        let rect = null;
        if (step.selector || step._resolvedSelector) {
            const selector = step._resolvedSelector || step.selector;
            const target = document.querySelector(selector);
            if (target) {
                currentTarget = target;
                currentTarget.scrollIntoView({ block: 'center', inline: 'nearest', behavior: 'smooth' });
                rect = target.getBoundingClientRect();
                const pad = 8;
                highlight.style.top = `${Math.max(4, rect.top - pad)}px`;
                highlight.style.left = `${Math.max(4, rect.left - pad)}px`;
                highlight.style.width = `${Math.min(window.innerWidth - 8, rect.width + (pad * 2))}px`;
                highlight.style.height = `${Math.min(window.innerHeight - 8, rect.height + (pad * 2))}px`;
                highlight.classList.remove('is-hidden');
            }
        }

        if (!rect) {
            highlight.classList.add('is-hidden');
        }

        requestAnimationFrame(() => {
            positionPopover(rect);
            setTimeout(() => positionPopover(rect), 180);
        });
    }

    function startTour(forceOpen) {
        const seen = hasSeen();
        if (!forceOpen && seen) return;
        overlay.style.display = 'block';
        overlay.classList.add('is-open');
        document.body.classList.add('tour-open');
        index = 0;
        renderStep();
    }

    btnClose?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        closeTour(true);
    });
    btnPrev?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (index <= 0) return;
        index -= 1;
        renderStep();
    });
    btnNext?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (index >= steps.length - 1) {
            closeTour(true);
            return;
        }
        index += 1;
        renderStep();
    });
    btnSkip?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        closeTour(true);
    });

    overlay.addEventListener('click', (e) => {
        if (!overlay.classList.contains('is-open')) return;
        if (e.target === overlay || (e.target instanceof HTMLElement && e.target.classList.contains('tour-backdrop'))) {
            closeTour(true);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (!overlay.classList.contains('is-open')) return;
        if (e.key === 'Escape') {
            e.preventDefault();
            closeTour(true);
            return;
        }
        if (e.key === 'ArrowRight') {
            e.preventDefault();
            btnNext?.click();
        } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            btnPrev?.click();
        }
    });

    window.addEventListener('resize', () => {
        if (!overlay.classList.contains('is-open')) return;
        renderStep();
    }, { passive: true });

    openButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            startTour(true);
        });
    });

    overlay.style.display = 'none';
    // O guia é aberto somente quando o usuário clica no botão "Guia".
})();
</script>
</body>
</html>
