        /* Hotfix final: mobile/tablet overflow + button contrast consistency */
        @media (max-width: 992px) {
            .app-content,
            .app-content > * {
                min-width: 0;
                max-width: 100%;
            }
            .app-content .row {
                margin-left: 0 !important;
                margin-right: 0 !important;
                --bs-gutter-x: 0.8rem;
            }
            .app-content .row > [class*="col-"] {
                min-width: 0;
                max-width: 100%;
                padding-left: calc(var(--bs-gutter-x) * 0.5);
                padding-right: calc(var(--bs-gutter-x) * 0.5);
            }
            .app-content .card,
            .app-content .saas-page,
            .app-content .saas-hero-card,
            .app-content .saas-table-card,
            .app-content .section-block {
                min-width: 0;
                max-width: 100%;
            }
            .app-content .table-responsive,
            .app-content .saas-table-scroll {
                max-width: 100%;
                overflow-x: auto;
            }
        }

        @media (max-width: 768px) {
            .app-content .saas-toolbar .btn {
                flex: 1 1 100%;
            }
            .app-content .table thead th,
            .app-content .table tbody td {
                word-break: break-word;
            }
        }
