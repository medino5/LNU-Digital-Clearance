@push('styles')
<style>
    .student-profile-panel {
        display: grid;
        gap: 14px;
        min-width: 0;
    }

    .student-profile-hero,
    .profile-card {
        border: 1px solid #e4dacd;
        border-radius: 18px;
        background: #fffdf8;
        box-shadow: 0 12px 28px rgba(24, 58, 99, 0.04);
        min-width: 0;
    }

    .student-profile-hero {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
        padding: 18px;
    }

    .student-profile-hero h1 {
        margin: 0;
        color: #173c66;
        font-size: clamp(1.3rem, 1.6vw, 1.75rem);
        line-height: 1.12;
        overflow-wrap: anywhere;
    }

    .student-profile-hero p {
        margin: 8px 0 0;
        color: #667085;
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
        background: linear-gradient(135deg, #173c66 0%, #27588f 100%);
        color: #fff;
        font-size: 1.35rem;
        font-weight: 900;
        border: 3px solid #fff;
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
        padding: 16px;
    }

    .profile-facts {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin: 0;
    }

    .profile-facts div,
    .profile-counts span {
        padding: 10px 12px;
        border-radius: 13px;
        background: #ffffff;
        border: 1px solid #ece3d6;
        min-width: 0;
    }

    .profile-facts dt {
        color: #667085;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .profile-facts dd {
        margin: 6px 0 0;
        color: #183a63;
        font-weight: 800;
        line-height: 1.35;
        overflow-wrap: anywhere;
    }

    .profile-progress {
        display: grid;
        gap: 10px;
    }

    .profile-progress-meter {
        height: 14px;
        border-radius: 999px;
        overflow: hidden;
        background: #e9e1d4;
    }

    .profile-progress-meter span {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #d2a83d, #1f7a4f);
    }

    .profile-counts {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        margin-top: 14px;
        color: #183a63;
        font-size: 13px;
        font-weight: 800;
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
        color: #173c66;
        font-size: 1.18rem;
        overflow-wrap: anywhere;
    }

    .profile-step-list,
    .profile-history-list {
        display: grid;
        gap: 10px;
    }

    .profile-step-row,
    .profile-history-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        padding: 12px;
        border: 1px solid #ece3d6;
        border-radius: 14px;
        background: #ffffff;
        min-width: 0;
    }

    .profile-history-record {
        border: 1px solid #ece3d6;
        border-radius: 14px;
        background: #ffffff;
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
        color: #173c66;
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
        padding: 11px;
        border-radius: 14px;
        background: #f8f4ea;
        border: 1px solid #ede2d2;
    }

    .profile-history-status {
        text-align: right;
        flex: 0 0 auto;
    }

    .profile-history-remarks {
        max-width: 32ch;
        color: #8b5a14;
        overflow-wrap: anywhere;
    }

    .profile-step-row strong,
    .profile-history-row strong {
        color: #183a63;
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
        background: #ece7dc;
        color: #3d3a36;
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
