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

    const tableNodes = Array.from(document.querySelectorAll('table.table'));
    const defaultPageSize = window.matchMedia('(max-width: 768px)').matches ? 8 : 12;
    let autoIndex = 0;

    tableNodes.forEach((table) => {
        if (!table || table.dataset.noAutoPagination === '1') return;
        if (table.closest('.js-no-auto-pagination')) return;
        if (hasManualPagerNear(table)) return;

        const tbody = table.tBodies && table.tBodies[0] ? table.tBodies[0] : null;
        if (!tbody) return;
        if (tbody.rows.length <= defaultPageSize) return;

        autoIndex += 1;
        const pageSize = Math.max(5, parseInt(table.dataset.pageSize || String(defaultPageSize), 10) || defaultPageSize);
        createPager(table, pageSize, `autoTablePagination${autoIndex}`);
    });
})();

// Tutorial guiado por pagina e perfil (primeiro uso).
(function () {
    const body = document.body;
    if (!body) return;

    const route = String(body.dataset.route || '').toLowerCase();
    const role = String(body.dataset.role || 'guest').toLowerCase();
    const userId = String(body.dataset.userId || '0');
    if (!route || role === 'guest' || userId === '0') return;

    const allowedRoles = ['hostess', 'supervisor', 'gerente'];
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
    };

    const allowedRoutes = routeAllowListByRole[role] || [];
    if (!allowedRoutes.includes(route)) return;

    const key = `oca_tour_seen_v3:${userId}:${role}:${route}`;
    const openButtons = Array.from(document.querySelectorAll('.js-open-tour'));

    function pageSteps(currentRoute, currentRole) {
        const map = {
            'access/index': [
                {
                    selector: 'form[action="/?r=turnos/start"]',
                    title: '1. Iniciar turno',
                    text: 'Escolha restaurante, operacao e porta (quando aplicavel). O registro so abre depois do turno iniciado.',
                },
                {
                    selector: 'input[name="uh_numero"]',
                    title: '2. Digitar UH',
                    text: 'Informe a UH do hospede. Para excecoes, use os atalhos 998 (Nao informado) e 999 (Day use).',
                },
                {
                    selector: 'input[name="pax"]',
                    title: '3. Ajustar PAX',
                    text: 'Ajuste a quantidade de PAX antes de registrar. O sistema valida limites automaticamente.',
                },
                {
                    selector: 'form[action="/?r=access/register"] button[type="submit"]',
                    title: '4. Registrar',
                    text: 'Finalize o lancamento. Alertas de duplicidade, reserva tematica e fora do horario aparecem na hora.',
                },
            ],
            'hostess/turnos': [
                {
                    selector: '.app-content .card',
                    title: 'Resumo dos seus turnos',
                    text: 'Aqui voce acompanha turnos concluidos, cancelados e o historico diario da sua operacao.',
                },
            ],
            'control/index': [
                {
                    selector: '.control-kpis',
                    title: 'Visao executiva',
                    text: 'Esses cards mostram o pulso operacional do dia para tomada de decisao rapida.',
                },
                {
                    selector: '.saas-table-card',
                    title: 'Operacao em andamento',
                    text: currentRole === 'supervisor'
                        ? 'Acompanhe turnos ativos e faca intervencoes quando necessario.'
                        : 'Use esta visao para acompanhar o andamento da operacao e apoiar decisoes do dia.',
                },
                {
                    selector: '.control-pagination',
                    title: 'Historico paginado',
                    text: 'Navegue pelas paginas para auditar todos os registros do dia.',
                },
            ],
            'dashboard/index': [
                {
                    selector: '.saas-hero-card',
                    title: 'Filtros estrategicos',
                    text: currentRole === 'gerente'
                        ? 'Defina periodo e status para leitura executiva da operacao e comparacao entre dias.'
                        : 'Defina periodo e status para analisar tendencias operacionais e qualidade da execucao.',
                },
                {
                    selector: '.saas-kpi-grid',
                    title: 'KPIs do periodo',
                    text: currentRole === 'gerente'
                        ? 'Aqui ficam os indicadores que apoiam decisao rapida de gestao.'
                        : 'Aqui ficam os indicadores principais para comparacoes rapidas por contexto.',
                },
                {
                    selector: '.saas-table-card',
                    title: 'Distribuicao e monitoramento',
                    text: currentRole === 'gerente'
                        ? 'As tabelas mostram distribuicao de resultados e comportamento recente da operacao.'
                        : 'As tabelas mostram a composicao dos resultados e os ultimos eventos lancados.',
                },
            ],
            'dashboard/restaurant': [
                {
                    selector: '.saas-hero-card',
                    title: 'Contexto do restaurante',
                    text: 'Use filtros por data e status para focar o comportamento do restaurante selecionado.',
                },
                {
                    selector: '.saas-kpi-grid',
                    title: 'Indicadores focados',
                    text: 'Esses KPIs mostram produtividade, alertas e consistencia operacional da unidade.',
                },
                {
                    selector: '.saas-table-card',
                    title: 'Detalhamento analitico',
                    text: 'Cruze operacoes, horarios e historico recente para identificar gargalos e oportunidades.',
                },
            ],
            'relatorios/index': [
                {
                    selector: 'form[action="/"]',
                    title: 'Construcao do relatorio',
                    text: 'Combine filtros de periodo, UH, restaurante, operacao e status para extrair cenarios especificos.',
                },
                {
                    selector: '.js-export-btn',
                    title: 'Exportacao operacional',
                    text: 'Exporte CSV/Excel para BI, auditoria e reunioes de performance.',
                },
                {
                    selector: '.split-full, .saas-table-card, .card',
                    title: 'Leitura em camadas',
                    text: 'Leia os cards primeiro e depois aprofunde nas tabelas detalhadas.',
                },
            ],
            'kpis/index': [
                {
                    selector: 'form[action="/?r=kpis/index"]',
                    title: 'KPIs estrategicos',
                    text: 'Ajuste periodo e ocupacao para comparar consumo real versus demanda do dia.',
                },
                {
                    selector: '.kpi-candles, .kpi-chart-card, .kpi-stat-grid',
                    title: 'Visoes analiticas',
                    text: 'Os blocos graficos ajudam a identificar tendencia, desvio e previsibilidade operacional.',
                },
            ],
            'reservastematicas/reservas': [
                {
                    selector: 'form[action="/?r=reservasTematicas/reservas"]',
                    title: 'Cadastro de reservas',
                    text: 'Registre UH, PAX, turno e observacoes para montar a base da operacao tematica.',
                },
                {
                    selector: '.table-responsive',
                    title: 'Controle de capacidade',
                    text: 'Acompanhe status e lotacao por turno para evitar sobrecarga no atendimento.',
                },
            ],
            'reservastematicas/operacao': [
                {
                    selector: '.section-block, .saas-hero-card',
                    title: 'Conferencia operacional',
                    text: 'Aqui a hostess valida reservas, atualiza status e prepara a execucao do servico.',
                },
                {
                    selector: '.table-responsive',
                    title: 'Base do turno',
                    text: 'Use busca e filtros para localizar rapidamente cada reserva durante a operacao.',
                },
            ],
            'relatoriostematicos/index': [
                {
                    selector: 'form[action="/"]',
                    title: 'Filtros tematicos',
                    text: 'Defina o periodo e o restaurante para acompanhar desempenho de reservas e no-show.',
                },
                {
                    selector: '.saas-table-card, .table-responsive',
                    title: 'Leitura operacional',
                    text: 'Use os blocos para acompanhar comparecimento, cancelamentos e ocupacao por turno.',
                },
            ],
            'vouchers/index': [
                {
                    selector: 'form[action="/?r=vouchers/index"]',
                    title: 'Registro de vouchers',
                    text: 'Cadastre os vouchers recebidos no turno para auditoria financeira e operacional.',
                },
                {
                    selector: '.table-responsive',
                    title: 'Historico e conferencia',
                    text: 'Revise anexos, origem e dados essenciais para validacao posterior.',
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
        <div class="tour-popover" role="dialog" aria-modal="true" aria-label="Tutorial da pagina">
            <div class="tour-kicker">Guia da pagina</div>
            <div class="tour-title"></div>
            <div class="tour-text"></div>
            <div class="tour-footer">
                <div class="tour-progress"></div>
                <div class="tour-actions">
                    <button type="button" class="btn btn-outline-secondary btn-sm js-tour-close">Fechar</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm js-tour-skip">Pular</button>
                    <button type="button" class="btn btn-outline-primary btn-sm js-tour-prev">Voltar</button>
                    <button type="button" class="btn btn-primary btn-sm js-tour-next">Proximo</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);

    const highlight = overlay.querySelector('.tour-highlight');
    const pop = overlay.querySelector('.tour-popover');
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

        title.textContent = step.title || 'Guia rapido';
        text.textContent = step.text || '';
        progress.textContent = `Passo ${index + 1} de ${steps.length}`;
        btnPrev.disabled = index === 0;
        btnNext.textContent = index === (steps.length - 1) ? 'Concluir' : 'Proximo';

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
    setTimeout(() => startTour(false), 550);
})();
</script>
</body>
</html>



