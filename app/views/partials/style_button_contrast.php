        /* Button contrast safety */
        .btn-outline-primary,
        .btn-outline-primary:visited {
            color: #c2410c !important;
            border-color: #fb923c !important;
            background: rgba(255, 255, 255, 0.9) !important;
        }
        .btn-outline-primary:hover,
        .btn-outline-primary:focus,
        .btn-outline-primary:active,
        .btn-check:checked + .btn-outline-primary {
            color: #fff !important;
            border-color: #f97316 !important;
            background: linear-gradient(135deg, #f97316 0%, #fb923c 100%) !important;
        }
        .btn-outline-secondary,
        .btn-outline-secondary:visited,
        .btn-outline-dark,
        .btn-outline-dark:visited {
            color: var(--ab-ink) !important;
            border-color: var(--ab-border) !important;
            background: transparent !important;
        }
        .btn-outline-secondary:hover,
        .btn-outline-secondary:focus,
        .btn-outline-secondary:active,
        .btn-outline-dark:hover,
        .btn-outline-dark:focus,
        .btn-outline-dark:active {
            color: var(--ab-ink) !important;
            border-color: color-mix(in srgb, var(--ab-border) 55%, #94a3b8 45%) !important;
            background: color-mix(in srgb, var(--ab-soft-bg) 88%, transparent) !important;
        }
        html[data-theme='dark'] .btn-outline-primary,
        html[data-theme='dark'] .btn-outline-primary:visited {
            color: #fdba74 !important;
            border-color: #fb923c !important;
            background: rgba(251, 146, 60, 0.09) !important;
        }
        html[data-theme='dark'] .btn-outline-primary:hover,
        html[data-theme='dark'] .btn-outline-primary:focus,
        html[data-theme='dark'] .btn-outline-primary:active,
        html[data-theme='dark'] .btn-check:checked + .btn-outline-primary {
            color: #fff !important;
            border-color: #fb923c !important;
            background: linear-gradient(135deg, #fb923c 0%, #f97316 100%) !important;
        }
