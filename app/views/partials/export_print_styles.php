<style>
    :root {
        --ink: #1c2b36;
        --muted: #6a7a86;
        --line: #d9e2e8;
        --soft: #f5f8fa;
        --accent: #ef6b2e;
        --accent-soft: #fff2ea;
        --accent-dark: #b84d19;
        --card: #ffffff;
    }

    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }

    body {
        font-family: "Segoe UI", Tahoma, Arial, sans-serif;
        background: linear-gradient(180deg, #fff8f3 0%, #ffffff 24%);
        color: var(--ink);
        font-size: 11.5px;
        line-height: 1.38;
    }

    .export-page {
        max-width: 1240px;
        margin: 0 auto;
        padding: 14px;
    }

    .export-shell {
        background: var(--card);
        border: 1px solid rgba(28, 43, 54, 0.08);
        border-radius: 20px;
        box-shadow: 0 20px 44px rgba(28, 43, 54, 0.08);
        overflow: hidden;
    }

    .export-hero {
        padding: 18px 22px 14px;
        background:
            radial-gradient(circle at top right, rgba(239, 107, 46, 0.16), transparent 34%),
            linear-gradient(135deg, #17384d 0%, #1d4d67 100%);
        color: #fff;
    }

    .export-kicker {
        text-transform: uppercase;
        letter-spacing: 0.12em;
        font-size: 10px;
        opacity: 0.82;
        margin-bottom: 6px;
    }

    .export-title {
        font-size: 24px;
        line-height: 1.1;
        font-weight: 700;
        margin: 0 0 6px;
    }

    .export-subtitle {
        font-size: 12px;
        max-width: 700px;
        color: rgba(255, 255, 255, 0.86);
    }

    .export-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 8px;
        padding: 12px 18px 8px;
        background: linear-gradient(180deg, rgba(243, 246, 248, 0.95) 0%, rgba(255, 255, 255, 1) 100%);
        border-bottom: 1px solid var(--line);
    }

    .meta-card {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 12px;
        padding: 10px 12px;
        min-height: 60px;
    }

    .meta-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--muted);
        margin-bottom: 7px;
    }

    .meta-value {
        font-size: 14px;
        font-weight: 700;
        color: var(--ink);
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
        padding: 12px 18px 16px;
    }

    .summary-card {
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 12px 14px;
        background: linear-gradient(180deg, #ffffff 0%, #fbfcfd 100%);
    }

    .summary-number {
        font-size: 24px;
        font-weight: 800;
        color: var(--accent-dark);
        line-height: 1;
        margin-bottom: 5px;
    }

    .summary-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--muted);
        margin-bottom: 4px;
    }

    .summary-note {
        font-size: 11px;
        color: var(--ink);
    }

    .content-wrap {
        padding: 0 18px 18px;
    }

    .turno-section {
        border: 1px solid var(--line);
        border-radius: 16px;
        overflow: hidden;
        margin-bottom: 14px;
        background: #fff;
        break-inside: avoid;
    }

    .turno-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        background: linear-gradient(90deg, var(--accent-soft) 0%, rgba(255,255,255,0.96) 82%);
        border-bottom: 1px solid var(--line);
    }

    .turno-title {
        font-size: 16px;
        font-weight: 800;
        margin: 0;
        color: var(--ink);
    }

    .turno-subtitle {
        font-size: 11px;
        color: var(--muted);
        margin-top: 2px;
    }

    .turno-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }

    .stat-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        border: 1px solid rgba(239, 107, 46, 0.24);
        background: #fff;
        padding: 6px 10px;
        font-size: 10px;
        color: var(--ink);
    }

    .stat-pill strong {
        font-size: 12px;
    }

    .turno-table-wrap {
        padding: 8px 10px 10px;
    }

    .group-block-list {
        display: grid;
        gap: 10px;
        padding: 10px;
    }

    .group-block {
        border: 1px solid rgba(24, 129, 120, 0.22);
        border-radius: 16px;
        background: linear-gradient(180deg, #f1fbf8 0%, #ffffff 100%);
        overflow: hidden;
    }

    .group-block-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 14px;
        background: linear-gradient(90deg, rgba(24, 129, 120, 0.12) 0%, rgba(255,255,255,0.96) 100%);
        border-bottom: 1px solid rgba(24, 129, 120, 0.16);
    }

    .group-block-kicker {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: #14766f;
        font-weight: 800;
        margin-bottom: 5px;
    }

    .group-block-title {
        font-size: 16px;
        font-weight: 800;
        margin: 0;
        color: var(--ink);
    }

    .group-block-subtitle {
        margin-top: 3px;
        font-size: 11px;
        color: var(--muted);
    }

    .group-block-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }

    .group-stat-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border-radius: 999px;
        border: 1px solid rgba(24, 129, 120, 0.22);
        background: #fff;
        padding: 6px 10px;
        font-size: 10px;
        color: var(--ink);
    }

    .group-stat-pill strong {
        font-size: 12px;
        color: #14766f;
    }

    .group-block-body {
        padding: 12px 14px 14px;
    }

    .group-inline-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 10px;
    }

    .group-inline-grid.single-panel {
        grid-template-columns: 1fr;
    }

    .group-inline-panel {
        border: 1px solid var(--line);
        border-radius: 12px;
        background: #fff;
        padding: 10px;
    }

    .group-inline-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--muted);
        margin-bottom: 6px;
        font-weight: 700;
    }

    .group-member-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(128px, 1fr));
        gap: 8px;
        align-items: stretch;
    }

    .group-member-card {
        border: 1px solid var(--line);
        border-radius: 12px;
        background: #fff;
        padding: 9px;
        min-width: 0;
    }

    .group-member-unit {
        display: grid;
        gap: 4px;
        margin-bottom: 7px;
    }

    .group-member-uh {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        justify-self: stretch;
        min-height: 26px;
        width: 100%;
        border-radius: 999px;
        background: #1d4d67;
        color: #fff;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.02em;
        padding: 5px 9px;
        white-space: nowrap;
        text-align: center;
    }

    .group-member-name {
        font-size: 10px;
        line-height: 1.18;
        color: var(--ink);
    }

    .group-member-metrics {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 5px;
    }

    .group-member-metrics div {
        border: 1px solid rgba(217, 226, 232, 0.9);
        border-radius: 10px;
        background: #f9fbfc;
        padding: 6px 4px;
        text-align: center;
        min-width: 0;
    }

    .group-member-metrics strong {
        display: block;
        color: var(--accent-dark);
        font-size: 13px;
        line-height: 1;
    }

    .group-member-metrics span {
        display: block;
        margin-top: 3px;
        color: var(--muted);
        font-size: 8.5px;
        line-height: 1.05;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .group-note-stack {
        display: grid;
        gap: 5px;
        margin-top: 7px;
    }

    .group-note-item {
        border: 1px solid rgba(239, 107, 46, 0.28);
        border-radius: 10px;
        padding: 6px 8px;
        background: #fff7f1;
        font-size: 10.5px;
        color: var(--ink);
    }

    .chd-age-line {
        margin-top: 6px;
        border-radius: 999px;
        background: #eef6fb;
        border: 1px solid rgba(29, 77, 103, 0.14);
        color: #1d4d67;
        font-size: 9.5px;
        font-weight: 700;
        padding: 4px 7px;
        line-height: 1.15;
        text-align: center;
    }

    .group-note-item span {
        display: inline-block;
        margin-right: 6px;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--muted);
        font-weight: 700;
    }

    table.export-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        table-layout: fixed;
    }

    .export-table .pax-column {
        width: 245px;
    }

    .export-table thead th {
        text-align: left;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--muted);
        background: var(--soft);
        padding: 8px 9px;
        border-top: 1px solid var(--line);
        border-bottom: 1px solid var(--line);
    }

    .export-table thead th:first-child {
        border-left: 1px solid var(--line);
        border-top-left-radius: 12px;
    }

    .export-table thead th:last-child {
        border-right: 1px solid var(--line);
        border-top-right-radius: 12px;
    }

    .export-table tbody td {
        vertical-align: top;
        padding: 9px;
        border-bottom: 1px solid var(--line);
        border-left: 1px solid var(--line);
        background: #fff;
    }

    .export-table tbody tr td:last-child {
        border-right: 1px solid var(--line);
    }

    .uh-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 72px;
        min-width: 72px;
        padding: 6px 10px;
        border-radius: 999px;
        background: #1d4d67;
        color: #fff;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-align: center;
    }

    .guest-name {
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .chip-row {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .pax-chip-row {
        flex-wrap: nowrap;
        gap: 5px;
        white-space: nowrap;
    }

    .chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        border-radius: 999px;
        background: #f4f7f9;
        border: 1px solid var(--line);
        padding: 4px 8px;
        font-size: 10px;
        color: var(--ink);
    }

    .pax-chip {
        flex: 0 0 auto;
        padding-inline: 7px;
        white-space: nowrap;
    }

    .chip.status {
        background: var(--accent-soft);
        border-color: rgba(239, 107, 46, 0.26);
        color: var(--accent-dark);
        font-weight: 700;
    }

    .chip.group,
    .chip-group {
        background: #fff2ea;
        border-color: rgba(239, 107, 46, 0.24);
        color: var(--accent-dark);
        font-weight: 700;
    }

    .chip-uh {
        background: #eef6fb;
        border-color: rgba(29, 77, 103, 0.16);
        color: #1d4d67;
        font-weight: 700;
    }

    .notes-block {
        display: grid;
        gap: 6px;
    }

    .note-line {
        min-height: 24px;
        border: 1px solid rgba(28, 43, 54, 0.12);
        border-radius: 10px;
        padding: 6px 8px;
        background: #fcfdfd;
    }

    .note-line.has-note {
        border-color: rgba(239, 107, 46, 0.3);
        background: #fff7f1;
        box-shadow: inset 3px 0 0 rgba(239, 107, 46, 0.72);
    }

    .note-label {
        display: inline-block;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--muted);
        margin-bottom: 4px;
    }

    .check-column {
        width: 72px;
        text-align: center;
    }

    .check-box {
        width: 24px;
        height: 24px;
        border: 2px solid rgba(29, 77, 103, 0.35);
        border-radius: 6px;
        margin: 2px auto 0;
    }

    .empty-state {
        border: 1px dashed var(--line);
        border-radius: 18px;
        padding: 28px 20px;
        text-align: center;
        color: var(--muted);
        background: linear-gradient(180deg, #ffffff 0%, #fbfcfd 100%);
    }

    .compact-empty-state {
        padding: 16px 14px;
        border-radius: 12px;
    }

    .toolbar {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        align-items: center;
        gap: 12px;
        padding: 14px 18px 18px;
    }

    .toolbar-top {
        padding: 12px 18px;
        background: #fff;
        border-bottom: 1px solid var(--line);
    }

    .toolbar-note {
        font-size: 12px;
        color: var(--muted);
        max-width: 560px;
    }

    .toolbar-actions {
        display: flex;
        gap: 10px;
    }

    .toolbar-actions button {
        border: 0;
        border-radius: 999px;
        padding: 10px 14px;
        font-weight: 700;
        cursor: pointer;
        color: #fff;
        background: linear-gradient(135deg, var(--accent) 0%, #ff8d54 100%);
        box-shadow: 0 12px 26px rgba(239, 107, 46, 0.22);
    }

    .toolbar-actions .ghost {
        background: #fff;
        color: var(--ink);
        border: 1px solid var(--line);
        box-shadow: none;
    }

    @media screen and (max-width: 760px) {
        body {
            font-size: 11px;
            background: #fff;
        }

        .export-page {
            padding: 0;
        }

        .export-shell {
            border-radius: 0;
            border-left: 0;
            border-right: 0;
            box-shadow: none;
        }

        .export-hero {
            padding: 16px 14px 12px;
        }

        .export-title {
            font-size: 20px;
        }

        .export-subtitle {
            font-size: 11px;
        }

        .export-meta,
        .summary-grid,
        .content-wrap,
        .toolbar {
            padding-left: 12px;
            padding-right: 12px;
        }

        .export-meta,
        .summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .meta-card,
        .summary-card {
            min-height: 0;
        }

        .turno-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .turno-stats {
            justify-content: flex-start;
        }

        .group-block-head {
            flex-direction: column;
        }

        .group-block-stats {
            justify-content: flex-start;
        }

        .group-inline-grid {
            grid-template-columns: 1fr;
        }

        .turno-table-wrap {
            padding: 8px;
        }

        .export-table,
        .export-table tbody,
        .export-table tr,
        .export-table td {
            display: block;
            width: 100%;
        }

        .export-table thead {
            display: none;
        }

        .export-table tbody tr {
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 10px;
            background: #fff;
            box-shadow: 0 8px 18px rgba(28, 43, 54, 0.04);
        }

        .export-table tbody td {
            border: 0;
            border-bottom: 1px solid rgba(217, 226, 232, 0.9);
            padding: 10px 10px 10px 112px;
            position: relative;
            min-height: 44px;
        }

        .export-table tbody tr td:last-child {
            border-right: 0;
        }

        .export-table tbody td:last-child {
            border-bottom: 0;
        }

        .export-table tbody td::before {
            content: attr(data-label);
            position: absolute;
            left: 10px;
            top: 10px;
            width: 92px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--muted);
        }

        .uh-badge {
            min-width: 0;
        }

        .guest-name {
            font-size: 13px;
        }

        .chip-row {
            gap: 5px;
        }

        .pax-chip-row {
            flex-wrap: wrap;
            white-space: normal;
        }

        .check-column {
            text-align: left;
        }

        .check-box {
            margin: 0;
        }

        .toolbar {
            flex-direction: column;
            align-items: stretch;
        }

        .toolbar-actions {
            width: 100%;
            justify-content: space-between;
        }

        .toolbar-actions button {
            flex: 1 1 0;
        }
    }

    @media screen and (max-width: 420px) {
        .export-meta,
        .summary-grid {
            grid-template-columns: 1fr;
        }
    }

    @page {
        size: A4 landscape;
        margin: 10mm;
    }

    @media print {
        body {
            background: #fff;
            font-size: 10.5px;
        }

        .export-page {
            max-width: none;
            padding: 0;
        }

        .export-shell {
            border: 0;
            border-radius: 0;
            box-shadow: none;
        }

        .toolbar,
        .no-print {
            display: none !important;
        }

        .turno-section {
            page-break-inside: avoid;
        }

        .summary-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }
</style>
