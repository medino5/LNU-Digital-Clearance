@push('styles')
<style>
    .admin-page,
    .management-page {
        display: grid;
        gap: 14px;
    }

    .admin-page-header,
    .management-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
        padding: 16px 18px;
        border-radius: 18px;
        background: linear-gradient(135deg, #fbf7ef 0%, #fffdf8 100%);
        border: 1px solid #e8dfd1;
        box-shadow: 0 12px 28px rgba(24, 58, 99, 0.05);
        min-width: 0;
    }

    .admin-page-header h1,
    .management-header h1 {
        margin: 0;
        color: #173c66;
        font-size: clamp(1.38rem, 1.8vw, 1.95rem);
        line-height: 1.08;
        letter-spacing: -0.03em;
        overflow-wrap: anywhere;
    }

    .admin-page-header p,
    .management-header p {
        margin: 6px 0 0;
        color: #667085;
        max-width: 68ch;
        line-height: 1.45;
    }

    .management-primary-action {
        white-space: nowrap;
        background: linear-gradient(180deg, #173c66 0%, #0e2742 100%);
        box-shadow: 0 8px 18px rgba(14, 39, 66, 0.18);
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
        background: #ffffff;
        border: 1px solid #cfc7b7;
        color: #173c66;
    }

    .management-ghost-action,
    .button.ghost {
        background: transparent;
        border: 1px solid transparent;
        color: #173c66;
        box-shadow: none;
    }

    .admin-section-card,
    .management-card {
        display: grid;
        gap: 14px;
        padding: 18px;
        border-radius: 18px;
        background: linear-gradient(135deg, #fbf7ef 0%, #fffdf8 100%);
        border: 1px solid #e8dfd1;
        box-shadow: 0 12px 28px rgba(24, 58, 99, 0.05);
    }

    .management-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
    }

    .management-card-header h2 {
        margin: 0;
        color: #173c66;
        overflow-wrap: anywhere;
    }

    .management-card-kicker {
        margin: 0;
        color: #667085;
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
        border-radius: 14px;
        background: #ffffff;
        border: 1px solid #e4dacd;
        color: #183a63;
    }

    .management-summary-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 11px;
        border-radius: 999px;
        background: #eef4fb;
        color: #173c66;
        font-size: 12px;
        font-weight: 800;
    }

    .management-table-wrap {
        overflow-x: auto;
        border: 1px solid #e4dacd;
        border-radius: 18px;
        background: white;
    }

    .management-table {
        min-width: 720px;
    }

    .management-table th {
        background: #f8f4ea;
        color: #667085;
        font-size: 0.75rem;
        letter-spacing: 0.08em;
    }

    .management-table td {
        vertical-align: middle;
        background: #fff;
        overflow-wrap: anywhere;
    }

    .management-table tbody tr:hover td {
        background: #fffaf0;
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
        background: #dde8f7;
        color: #16385f;
        font-size: 13px;
    }

    .table-main-text {
        font-weight: 700;
        color: #183a63;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .org-pill {
        display: inline-flex;
        max-width: 260px;
        padding: 7px 12px;
        border-radius: 999px;
        background: rgba(210, 168, 61, 0.16);
        color: #76510e;
        font-size: 13px;
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .active-semester-row td {
        background: #fff8e8;
    }

    .active-semester-row:hover td {
        background: #fff4d7;
    }

    .empty-state {
        padding: 22px;
        text-align: center;
        color: #667085;
        border-radius: 16px;
        background: #fffdf8;
        border: 1px dashed #d5cbbd;
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
        background: #fffdf8;
        border: 1px solid #e4dacd;
        box-shadow: 0 24px 70px rgba(14, 39, 66, 0.24);
        overflow-x: hidden;
    }

    .management-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #e4dacd;
    }

    .management-modal-header h2 {
        margin: 0;
        color: #173c66;
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
        border: 1px solid #d7d3c8;
        color: #16385f;
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
        background: #dcfce7 !important;
        border: 1px solid #22c55e !important;
        color: #166534 !important;
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
        background: #dc2626;
        border-color: #dc2626;
        color: #ffffff;
    }

    .button.danger:hover,
    button.danger:hover {
        background: #b91c1c;
        border-color: #b91c1c;
        color: #ffffff;
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
