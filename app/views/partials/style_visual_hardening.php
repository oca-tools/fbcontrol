        /* Hardening visual global: evita estouro lateral e moderniza barra de rolagem das tabelas */
        .app-content,
        .app-content > *,
        .saas-page,
        .saas-page > * {
            min-width: 0;
            max-width: 100%;
        }
        .app-content .row > [class*="col-"],
        .saas-page .row > [class*="col-"] {
            min-width: 0;
        }
        .card,
        .card-soft,
        .saas-hero-card,
        .saas-table-card,
        .section-block,
        .metric-card {
            max-width: 100%;
            min-width: 0;
        }
        .section-title,
        .section-title h3,
        .section-title h4,
        .section-title h5,
        .saas-stat-value,
        .saas-table-head h5 {
            min-width: 0;
            overflow-wrap: anywhere;
        }
        .table-responsive,
        .saas-table-scroll,
        .auto-table-wrap {
            max-width: 100%;
            overflow-x: auto !important;
            overflow-y: visible;
            scrollbar-width: thin;
            scrollbar-color: color-mix(in srgb, var(--ab-accent) 68%, #ffffff 32%) transparent;
        }
        .table-responsive::-webkit-scrollbar,
        .saas-table-scroll::-webkit-scrollbar,
        .auto-table-wrap::-webkit-scrollbar {
            height: 11px;
            width: 11px;
        }
        .table-responsive::-webkit-scrollbar-track,
        .saas-table-scroll::-webkit-scrollbar-track,
        .auto-table-wrap::-webkit-scrollbar-track {
            background: color-mix(in srgb, var(--ab-soft-bg) 82%, transparent);
            border-radius: 999px;
        }
        .table-responsive::-webkit-scrollbar-thumb,
        .saas-table-scroll::-webkit-scrollbar-thumb,
        .auto-table-wrap::-webkit-scrollbar-thumb {
            border-radius: 999px;
            border: 2px solid transparent;
            background-clip: padding-box;
            background-image: linear-gradient(135deg, color-mix(in srgb, var(--ab-accent) 80%, #fb923c 20%), color-mix(in srgb, var(--ab-accent-2) 76%, #f97316 24%));
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.15);
        }
        .table-responsive::-webkit-scrollbar-thumb:hover,
        .saas-table-scroll::-webkit-scrollbar-thumb:hover,
        .auto-table-wrap::-webkit-scrollbar-thumb:hover {
            background-image: linear-gradient(135deg, color-mix(in srgb, var(--ab-accent) 92%, #ea580c 8%), color-mix(in srgb, var(--ab-accent-2) 88%, #fb923c 12%));
        }
        [data-theme='dark'] .table-responsive::-webkit-scrollbar-track,
        [data-theme='dark'] .saas-table-scroll::-webkit-scrollbar-track,
        [data-theme='dark'] .auto-table-wrap::-webkit-scrollbar-track {
            background: color-mix(in srgb, #0f1a2c 70%, var(--ab-soft-bg) 30%);
        }
        @media (hover: none) {
            .card:hover,
            .metric-card:hover,
            .btn:hover {
                transform: none !important;
            }
        }
