@push('styles')
<style>
    .admin-page,
    .management-page {
        display: grid;
        gap: 1.5rem;
        font-size: 0.96rem;
    }

    .admin-page-header,
    .management-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
        padding: 8px 2px 16px;
        border-radius: 0;
        background: transparent;
        border: 0;
        border-bottom: 1px solid rgba(23, 60, 102, 0.12);
        box-shadow: none;
        min-width: 0;
    }

    .admin-page-header h1,
    .management-header h1 {
        margin: 0;
        color: var(--text-primary);
        font-size: 1.875rem;
        line-height: 1.08;
        letter-spacing: -0.03em;
        font-weight: 600;
        text-transform: none;
        overflow-wrap: anywhere;
    }

    .admin-page-header p,
    .management-header p {
        margin: 6px 0 0;
        color: var(--text-muted);
        max-width: 68ch;
        line-height: 1.45;
    }

    .management-primary-action {
        white-space: nowrap;
        background: var(--brand-navy);
        color: var(--bg-surface);
        border-radius: 0.5rem;
        box-shadow: none;
    }

    .management-primary-action,
    .management-secondary-action,
    .management-ghost-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 42px;
        text-decoration: none;
    }

    .management-secondary-action,
    .button.secondary.management-secondary-action {
        background: var(--bg-surface);
        border: 1px solid var(--border-subtle);
        color: var(--text-primary);
    }

    .management-ghost-action,
    .button.ghost {
        background: transparent;
        border: 1px solid transparent;
        color: var(--text-primary);
        box-shadow: none;
    }

    .admin-section-card,
    .management-card {
        display: grid;
        gap: 1rem;
        padding: 1.5rem;
        border-radius: 0.75rem;
        background: var(--bg-surface);
        border: 1px solid var(--border-subtle);
        box-shadow: var(--card-shadow);
    }

    .management-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
    }

    .management-card-header h2 {
        margin: 0;
        color: var(--text-primary);
        overflow-wrap: anywhere;
    }

    .management-card-kicker {
        margin: 0;
        color: var(--text-muted);
        font-size: 0.92rem;
        line-height: 1.45;
    }

    .management-filter-bar,
    .management-action-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
    }

    .management-filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 10px;
        align-items: end;
    }

    .management-filter-grid .button,
    .management-action-bar .button {
        min-height: 42px;
        white-space: nowrap;
    }

    .management-summary-strip {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        padding: 12px 14px;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.62);
        border: 1px solid rgba(23, 60, 102, 0.1);
        color: var(--text-primary);
    }

    .management-summary-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 11px;
        border-radius: 999px;
        background: var(--status-info-bg);
        color: var(--text-primary);
        font-size: 12px;
        font-weight: 800;
    }

    .management-table-wrap {
        overflow-x: auto;
        border: 1px solid var(--border-subtle);
        border-radius: 0.75rem;
        background: var(--bg-surface);
    }

    .management-table {
        min-width: 720px;
    }

    .management-table th {
        background: var(--bg-surface);
        color: var(--text-muted);
        font-size: 0.75rem;
        letter-spacing: 0.08em;
        font-weight: 500;
    }

    .management-table td {
        vertical-align: middle;
        background: var(--bg-surface);
        overflow-wrap: anywhere;
    }

    .management-table tbody tr:hover td {
        background: rgba(248, 250, 252, 0.6);
    }

    .management-action-col {
        width: 130px;
        text-align: right;
    }

    .management-table td:last-child {
        text-align: right;
    }

    .table-action-button {
        padding: 8px 14px;
        font-size: 13px;
    }

    .table-action-stack {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .program-code {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 64px;
        padding: 7px 12px;
        border-radius: 999px;
        background: var(--status-info-bg);
        color: var(--status-info-text);
        font-size: 13px;
    }

    .table-main-text {
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .org-pill {
        display: inline-flex;
        max-width: 260px;
        padding: 7px 12px;
        border-radius: 999px;
        background: var(--status-warning-bg);
        color: var(--status-warning-text);
        font-size: 13px;
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .active-semester-row td {
        background: var(--status-warning-bg);
    }

    .active-semester-row:hover td {
        background: var(--status-warning-bg);
    }

    .empty-state {
        padding: 22px;
        text-align: center;
        color: var(--text-muted);
        border-radius: 16px;
        background: var(--bg-surface);
        border: 1px dashed var(--border-subtle);
        line-height: 1.45;
    }

    .management-modal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: flex-start;
        justify-content: center;
        padding: 120px 22px 40px;
        background: rgba(8, 26, 43, 0.48);
        z-index: 1200;
        overflow-y: auto;
    }

    .management-modal.is-open {
        display: flex;
    }

    .management-modal-panel {
        width: min(680px, 100%);
        display: grid;
        gap: 18px;
        padding: 24px;
        border-radius: 24px;
        background: var(--bg-surface);
        border: 1px solid var(--border-subtle);
        box-shadow: 0 24px 70px rgba(14, 39, 66, 0.24);
        overflow-x: hidden;
    }

    .management-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border-subtle);
    }

    .management-modal-header h2 {
        margin: 0;
        color: var(--text-primary);
        overflow-wrap: anywhere;
    }

    .modal-close-button {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        padding: 0;
        border-radius: 50%;
        background: transparent;
        border: 1px solid var(--border-subtle);
        color: var(--brand-navy);
        font-size: 24px;
        line-height: 1;
    }

    .modal-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
        margin-top: 6px;
    }

    .alert-success {
        background: var(--status-success-bg) !important;
        border: 1px solid var(--status-success-text) !important;
        color: var(--status-success-text) !important;
        border-radius: 14px;
        box-shadow: 0 8px 20px rgba(34, 197, 94, 0.15);
    }

    .alert-danger {
        border-radius: 14px;
    }

    .alert-info {
        border-radius: 14px;
    }

        .table-action-group {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .table-action-group form {
        margin: 0;
    }

    .button.danger,
    button.danger {
        background: var(--status-danger-text);
        border-color: var(--status-danger-text);
        color: var(--bg-surface);
    }

    .button.danger:hover,
    button.danger:hover {
        background: var(--status-danger-text);
        border-color: var(--status-danger-text);
        color: var(--bg-surface);
    }

    .button.danger:focus,
    button.danger:focus {
        outline: 2px solid rgba(220, 38, 38, 0.35);
        outline-offset: 2px;
    }

    @media (max-width: 720px) {
        .management-header,
        .admin-page-header,
        .management-card-header,
        .modal-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .management-primary-action,
        .management-secondary-action,
        .management-ghost-action,
        .modal-actions button {
            width: 100%;
        }

        .management-modal {
            padding-top: 90px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const openModal = function (modalId) {
        const modal = document.getElementById(modalId);

        if (!modal) {
            return;
        }

        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    };

    const closeModal = function (modal) {
        modal.classList.remove('is-open');

        if (!document.querySelector('.management-modal.is-open')) {
            document.body.style.overflow = '';
        }
    };

    document.querySelectorAll('[data-modal-open]').forEach(function (button) {
        button.addEventListener('click', function () {
            openModal(button.dataset.modalOpen);
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(function (button) {
        button.addEventListener('click', function () {
            const modal = button.closest('[data-modal]');

            if (modal) {
                closeModal(modal);
            }
        });
    });

    document.querySelectorAll('[data-modal]').forEach(function (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        document.querySelectorAll('.management-modal.is-open').forEach(closeModal);
    });

    const hashTarget = window.location.hash.replace('#', '');

    if (hashTarget) {
        const target = document.getElementById(hashTarget);

        if (target && target.matches('[data-modal]')) {
            openModal(hashTarget);
        }
    }
});
</script>
@endpush



