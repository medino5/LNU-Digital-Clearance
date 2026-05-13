@push('styles')
<style>
    .student-profile-panel {
        display: grid;
        gap: 18px;
    }

    .student-profile-hero,
    .profile-card {
        border: 1px solid #e4dacd;
        border-radius: 20px;
        background: #fffdf8;
        box-shadow: 0 12px 28px rgba(24, 58, 99, 0.04);
    }

    .student-profile-hero {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 18px;
        padding: 24px;
    }

    .student-profile-hero h1 {
        margin: 0;
        color: #173c66;
        font-size: clamp(1.55rem, 2vw, 2.15rem);
    }

    .student-profile-hero p {
        margin: 8px 0 0;
        color: #667085;
    }

    .student-profile-identity {
        display: flex;
        align-items: center;
        gap: 16px;
        min-width: 0;
    }

    .student-profile-avatar {
        width: 76px;
        height: 76px;
        flex: 0 0 76px;
        border-radius: 999px;
        display: grid;
        place-items: center;
        overflow: hidden;
        background: linear-gradient(135deg, #173c66 0%, #27588f 100%);
        color: #fff;
        font-size: 1.6rem;
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
        grid-template-columns: minmax(0, 1.3fr) minmax(280px, 0.7fr);
        gap: 18px;
    }

    .profile-card {
        padding: 22px;
    }

    .profile-facts {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin: 0;
    }

    .profile-facts div,
    .profile-counts span {
        padding: 12px 14px;
        border-radius: 14px;
        background: #ffffff;
        border: 1px solid #ece3d6;
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
        gap: 10px;
        margin-top: 18px;
        color: #183a63;
        font-size: 13px;
        font-weight: 800;
    }

    .profile-section-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
        margin-bottom: 16px;
    }

    .profile-section-header h2 {
        margin: 0;
        color: #173c66;
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
        gap: 16px;
        padding: 14px;
        border: 1px solid #ece3d6;
        border-radius: 16px;
        background: #ffffff;
    }

    .profile-history-record {
        border: 1px solid #ece3d6;
        border-radius: 16px;
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
        padding: 0 14px 14px;
    }

    .profile-history-step {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
        padding: 12px;
        border-radius: 14px;
        background: #f8f4ea;
        border: 1px solid #ede2d2;
    }

    .profile-history-status {
        text-align: right;
    }

    .profile-history-remarks {
        max-width: 32ch;
        color: #8b5a14;
        overflow-wrap: anywhere;
    }

    .profile-step-row strong,
    .profile-history-row strong {
        color: #183a63;
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
