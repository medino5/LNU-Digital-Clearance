@push('styles')
<style>
    .student-profile-panel {
        display: grid;
        gap: 14px;
        min-width: 0;
    }

    .student-profile-page-shell {
        max-width: 72rem;
        margin-inline: auto;
        padding: 2rem 1.5rem;
    }

    .student-profile-back-link {
        display: inline-flex;
        width: fit-content;
        align-items: center;
        color: var(--text-muted);
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
    }

    .student-profile-back-link:hover,
    .student-profile-back-link:focus-visible {
        color: var(--text-primary);
    }

    .student-profile-hero,
    .profile-card {
        border: 1px solid var(--border-subtle);
        border-radius: 0.75rem;
        background: var(--bg-surface);
        box-shadow: var(--card-shadow);
        min-width: 0;
    }

    .student-profile-hero {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
        padding: 16px;
    }

    .student-profile-hero h1 {
        margin: 0;
        color: var(--text-primary);
        font-size: clamp(1.3rem, 1.6vw, 1.75rem);
        line-height: 1.12;
        overflow-wrap: anywhere;
    }

    .student-profile-hero p {
        margin: 8px 0 0;
        color: var(--text-muted);
        overflow-wrap: anywhere;
    }

    .student-profile-identity {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .student-profile-avatar {
        width: 62px;
        height: 62px;
        flex: 0 0 62px;
        border-radius: 999px;
        display: grid;
        place-items: center;
        overflow: hidden;
        background: var(--brand-navy);
        color: var(--bg-surface);
        font-size: 1.35rem;
        font-weight: 900;
        border: 3px solid var(--bg-surface);
        box-shadow: 0 10px 22px rgba(24, 58, 99, 0.14);
    }

    .student-profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .student-profile-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(260px, 0.8fr);
        gap: 14px;
    }

    .profile-card {
        padding: 14px;
    }

    .profile-facts {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        margin: 0;
    }

    .profile-facts div,
    .profile-count-pill {
        padding: 9px 11px;
        border-radius: 0.75rem;
        background: var(--bg-surface);
        border: 1px solid var(--border-subtle);
        min-width: 0;
    }

    .profile-facts dt {
        color: var(--text-muted);
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .profile-facts dd {
        margin: 6px 0 0;
        color: var(--text-primary);
        font-weight: 800;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .profile-progress {
        display: grid;
        gap: 10px;
    }

    .profile-progress-meter {
        height: 0.5rem;
        border-radius: 999px;
        overflow: hidden;
        background: var(--border-subtle);
    }

    .profile-progress-meter span {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: var(--brand-navy);
    }

    .profile-counts {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        margin-top: 14px;
        color: var(--text-muted);
        font-size: 13px;
        font-weight: 800;
    }

    .profile-count-pill.total {
        background: var(--status-info-bg);
        border-color: rgba(30, 64, 175, 0.16);
        color: var(--status-info-text);
    }

    .profile-count-pill.approved {
        background: var(--status-success-bg);
        border-color: rgba(22, 101, 52, 0.16);
        color: var(--status-success-text);
    }

    .profile-count-pill.flagged {
        background: var(--status-danger-bg);
        border-color: rgba(153, 27, 27, 0.16);
        color: var(--status-danger-text);
    }

    .profile-count-pill.waiting {
        background: var(--status-warning-bg);
        border-color: rgba(146, 64, 14, 0.16);
        color: var(--status-warning-text);
    }

    .profile-section-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 12px;
        min-width: 0;
    }

    .profile-section-header h2 {
        margin: 0;
        color: var(--text-primary);
        font-size: 1.18rem;
        overflow-wrap: anywhere;
    }

    .profile-step-list,
    .profile-history-list {
        display: grid;
        gap: 8px;
    }

    .profile-step-row,
    .profile-history-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        padding: 10px 11px;
        border: 1px solid var(--border-subtle);
        border-radius: 14px;
        background: var(--bg-surface);
        min-width: 0;
    }

    .profile-history-record {
        border: 1px solid var(--border-subtle);
        border-radius: 14px;
        background: var(--bg-surface);
        overflow: hidden;
    }

    .profile-history-record[open] {
        border-color: rgba(23, 60, 102, 0.28);
        box-shadow: 0 14px 32px rgba(24, 58, 99, 0.08);
    }

    .profile-history-record summary {
        cursor: pointer;
        list-style: none;
    }

    .profile-history-record summary::-webkit-details-marker {
        display: none;
    }

    .profile-history-record summary::after {
        content: "View signers";
        align-self: center;
        color: var(--brand-navy);
        font-size: 12px;
        font-weight: 900;
        white-space: nowrap;
    }

    .profile-history-record[open] summary::after {
        content: "Hide signers";
    }

    .profile-history-record .profile-history-row {
        border: 0;
        border-radius: 0;
    }

    .profile-history-details {
        display: grid;
        gap: 9px;
        padding: 0 12px 12px;
    }

    .profile-history-step {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        padding: 10px;
        border-radius: 14px;
        background: var(--bg-app);
        border: 1px solid var(--border-subtle);
    }

    .profile-history-status {
        text-align: right;
        flex: 0 0 auto;
    }

    .profile-history-remarks {
        max-width: 32ch;
        color: var(--status-warning-text);
        overflow-wrap: anywhere;
    }

    .profile-step-row strong,
    .profile-history-row strong {
        color: var(--text-primary);
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .profile-step-row p,
    .profile-history-row p,
    .profile-history-step p {
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .student-profile-panel .badge {
        flex: 0 0 auto;
        max-width: 100%;
        white-space: normal;
        text-align: center;
    }

    .badge.neutral {
        background: var(--border-subtle);
        color: var(--text-primary);
    }

    @media (max-width: 860px) {
        .student-profile-hero,
        .profile-step-row,
        .profile-history-row,
        .profile-history-step {
            flex-direction: column;
        }

        .profile-history-status {
            text-align: left;
        }

        .student-profile-grid,
        .profile-facts,
        .profile-counts {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

