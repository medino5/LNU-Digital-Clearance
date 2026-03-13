<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Staff Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        #student-detail-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 999;
        }

        #modal-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        #modal-box {
            position: relative;
            background: #ffffff;
            border-radius: 8px;
            max-width: 560px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            padding: 24px;
            z-index: 1000;
        }

        #modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        #modal-student-name {
            font-weight: 700;
            color: #1B3A6B;
            font-size: 18px;
        }

        #modal-close {
            background: transparent;
            border: none;
            font-size: 18px;
            color: #6B7280;
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-gray-100">
<div class="min-h-screen">
    <nav class="bg-blue-900 text-yellow-400 px-6 py-4 flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold">Leyte Normal University</h1>
            <p class="text-sm text-yellow-200">Clearance Staff Dashboard</p>
        </div>
        <div class="text-right">
            <p class="font-semibold">
                {{ $staff->name }}
            </p>
            <p class="text-sm">
                {{ $designation?->name ?? 'No Office Assigned' }}
            </p>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto py-8 px-4">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">
            Pending Clearance Requests
        </h2>

        @if(session('success'))
            <div class="mb-4 rounded border border-green-300 bg-green-50 px-4 py-2 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-2 text-sm text-red-800">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($pendingSignatures->isEmpty())
            <div class="bg-white rounded-lg shadow p-6 text-center text-gray-600">
                No pending clearance requests for your office at this time.
            </div>
        @else
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Student Name
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Student Number
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Program
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date Requested
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($pendingSignatures as $signature)
                        @php
                            $request = $signature->clearanceRequest;
                            $student = $request?->student;
                            $allSignatures = $request?->signatures ?? collect();
                        @endphp

                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $student?->name ?? 'Unknown Student' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-700">
                                    {{ $student?->student_number ?? '—' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-700">
                                    {{ $student?->program?->name ?? '—' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-700">
                                    {{ $request?->created_at?->format('M d, Y') ?? '—' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center align-top">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="inline-flex gap-2">
                                        <button
                                            type="button"
                                            class="btn-view-student px-3 py-1 text-xs font-semibold rounded border border-blue-600 text-blue-600 hover:bg-blue-50"
                                            data-student-id="{{ $signature->clearanceRequest->student->id }}"
                                            data-student-name="{{ $signature->clearanceRequest->student->name }}"
                                        >
                                            View
                                        </button>

                                        <button
                                            type="button"
                                            onclick="toggleApproveForm({{ $signature->id }})"
                                            class="px-3 py-1 text-xs font-semibold rounded bg-green-600 text-white hover:bg-green-700"
                                        >
                                            Approve
                                        </button>

                                        <button
                                            type="button"
                                            onclick="toggleRejectForm({{ $signature->id }})"
                                            class="px-3 py-1 text-xs font-semibold rounded bg-red-600 text-white hover:bg-red-700"
                                        >
                                            Reject
                                        </button>
                                    </div>

                                    <!-- APPROVE FORM -->
                                    <div id="approve-form-{{ $signature->id }}" class="hidden w-64 rounded-lg border border-green-200 bg-green-50 p-3 text-left">
                                        <form method="POST" action="{{ route('staff.process', $signature) }}" class="space-y-2">
                                            @csrf
                                            <input type="hidden" name="action" value="approve">

                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                                    Remarks <span class="text-gray-400">(Optional)</span>
                                                </label>
                                                <textarea
                                                    name="remarks"
                                                    rows="3"
                                                    class="w-full rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                                                    placeholder="Enter remarks"
                                                >{{ old('action') === 'approve' ? old('remarks') : '' }}</textarea>
                                            </div>

                                            <div class="flex justify-end gap-2">
                                                <button
                                                    type="button"
                                                    onclick="toggleApproveForm({{ $signature->id }})"
                                                    class="px-3 py-1 text-xs font-semibold rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-100"
                                                >
                                                    Cancel
                                                </button>
                                                <button
                                                    type="submit"
                                                    class="px-3 py-1 text-xs font-semibold rounded bg-green-600 text-white hover:bg-green-700"
                                                >
                                                    Confirm Approve
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- REJECT FORM -->
                                    <div id="reject-form-{{ $signature->id }}" class="hidden w-64 rounded-lg border border-red-200 bg-red-50 p-3 text-left">
                                        <form method="POST" action="{{ route('staff.process', $signature) }}" class="space-y-2">
                                            @csrf
                                            <input type="hidden" name="action" value="reject">

                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                                    Rejection Reason <span class="text-red-600">*</span>
                                                </label>
                                                <textarea
                                                    name="rejection_reason"
                                                    rows="3"
                                                    required
                                                    class="w-full rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                                                    placeholder="Enter rejection reason"
                                                >{{ old('rejection_reason') }}</textarea>
                                            </div>

                                            <div class="flex justify-end gap-2">
                                                <button
                                                    type="button"
                                                    onclick="toggleRejectForm({{ $signature->id }})"
                                                    class="px-3 py-1 text-xs font-semibold rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-100"
                                                >
                                                    Cancel
                                                </button>
                                                <button
                                                    type="submit"
                                                    class="px-3 py-1 text-xs font-semibold rounded bg-red-600 text-white hover:bg-red-700"
                                                >
                                                    Confirm Reject
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    @if($signature->remarks)
                                        <button
                                            type="button"
                                            onclick="toggleRemarks({{ $signature->id }})"
                                            class="text-xs font-medium text-blue-700 underline hover:text-blue-900"
                                        >
                                            View Submitted Note
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        @if($signature->remarks)
                            <tr id="remarks-row-{{ $signature->id }}" class="hidden bg-blue-50">
                                <td colspan="5" class="px-4 py-3">
                                    <div class="rounded-md border border-blue-200 bg-white p-3">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-800 mb-1">
                                            Submitted Remarks
                                        </p>
                                        <p class="text-sm text-gray-700 whitespace-pre-line">
                                            {{ $signature->remarks }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endif

                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </main>

    <div id="student-detail-modal" style="display:none;">
        <div id="modal-overlay"></div>
        <div id="modal-box">
            <div id="modal-header">
                <span id="modal-student-name"></span>
                <button id="modal-close" type="button">&times;</button>
            </div>
            <div id="modal-body">
                <!-- dynamically populated -->
            </div>
        </div>
    </div>
</div>

<script>
    function toggleApproveForm(signatureId) {
        const approveForm = document.getElementById('approve-form-' + signatureId);
        const rejectForm = document.getElementById('reject-form-' + signatureId);

        approveForm.classList.toggle('hidden');
        rejectForm.classList.add('hidden');
    }

    function toggleRejectForm(signatureId) {
        const approveForm = document.getElementById('approve-form-' + signatureId);
        const rejectForm = document.getElementById('reject-form-' + signatureId);

        rejectForm.classList.toggle('hidden');
        approveForm.classList.add('hidden');
    }

    function toggleRemarks(signatureId) {
        const remarksRow = document.getElementById('remarks-row-' + signatureId);
        remarksRow.classList.toggle('hidden');
    }

    const modal = document.getElementById('student-detail-modal');
    const modalOverlay = document.getElementById('modal-overlay');
    const modalClose = document.getElementById('modal-close');
    const modalStudentName = document.getElementById('modal-student-name');
    const modalBody = document.getElementById('modal-body');

    function openModal() {
        modal.style.display = 'flex';
        document.body.classList.add('overflow-hidden');
    }

    function closeModal() {
        modal.style.display = 'none';
        document.body.classList.remove('overflow-hidden');
    }

    function escapeHtml(value) {
        if (value === null || value === undefined) return '';
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatDate(value) {
        if (!value) return '\u2014';
        const parsed = new Date(value);
        if (Number.isNaN(parsed.getTime())) return escapeHtml(value);
        return parsed.toLocaleString();
    }

    function statusBadge(status) {
        const normalized = (status || 'pending').toLowerCase();
        const config = {
            approved: { bg: '#E8F5E9', text: '#2E7D32', label: 'Approved' },
            rejected: { bg: '#FFEBEE', text: '#B71C1C', label: 'Rejected' },
            pending: { bg: '#FFF8E1', text: '#F57F17', label: 'Pending' },
            cancelled: { bg: '#F5F5F5', text: '#757575', label: 'Cancelled' },
        }[normalized] || { bg: '#FFF8E1', text: '#F57F17', label: 'Pending' };

        return `<span style="display:inline-block;padding:4px 10px;border-radius:999px;background:${config.bg};color:${config.text};font-size:12px;font-weight:600;">${config.label}</span>`;
    }

    function renderModalContent(data) {
        const student = data.student || {};
        const request = data.request || {};
        const signatures = Array.isArray(data.signatures) ? data.signatures : [];

        const program = escapeHtml(student.program || '\u2014');
        const email = escapeHtml(student.email || '\u2014');
        const semester = escapeHtml(request.semester || '\u2014');
        const requestStatus = statusBadge(request.status || 'pending');

        const signatureRows = signatures.map(signature => {
            const status = statusBadge(signature.status || 'pending');
            const rejectionReason = signature.rejection_reason
                ? `<span style="color:#B71C1C;font-style:italic;">${escapeHtml(signature.rejection_reason)}</span>`
                : '\u2014';

            return `
                <tr>
                    <td style="padding:8px;border-bottom:1px solid #E5E7EB;">${escapeHtml(signature.designation || '\u2014')}</td>
                    <td style="padding:8px;border-bottom:1px solid #E5E7EB;">${status}</td>
                    <td style="padding:8px;border-bottom:1px solid #E5E7EB;">${escapeHtml(signature.processed_by || '\u2014')}</td>
                    <td style="padding:8px;border-bottom:1px solid #E5E7EB;">${formatDate(signature.processed_at)}</td>
                    <td style="padding:8px;border-bottom:1px solid #E5E7EB;">${rejectionReason}</td>
                </tr>
            `;
        }).join('');

        const tableBody = signatureRows || `
            <tr>
                <td colspan="5" style="padding:12px;text-align:center;color:#6B7280;">No signatures found.</td>
            </tr>
        `;

        modalBody.innerHTML = `
            <div style="background:#F5F5F5;border-radius:8px;padding:12px;margin-bottom:16px;">
                <div style="margin-bottom:8px;">
                    <div style="font-size:12px;color:#6B7280;">Program</div>
                    <div style="font-size:14px;font-weight:700;color:#111827;">${program}</div>
                </div>
                <div>
                    <div style="font-size:12px;color:#6B7280;">Email</div>
                    <div style="font-size:14px;font-weight:700;color:#111827;">${email}</div>
                </div>
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <div>
                    <div style="font-size:12px;color:#6B7280;">Semester</div>
                    <div style="font-size:14px;font-weight:700;color:#111827;">${semester}</div>
                </div>
                ${requestStatus}
            </div>

            <div style="border-top:1px solid #E5E7EB;padding-top:12px;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="text-align:left;color:#6B7280;">
                            <th style="padding:8px;border-bottom:1px solid #E5E7EB;">Office</th>
                            <th style="padding:8px;border-bottom:1px solid #E5E7EB;">Status</th>
                            <th style="padding:8px;border-bottom:1px solid #E5E7EB;">Processed By</th>
                            <th style="padding:8px;border-bottom:1px solid #E5E7EB;">Date</th>
                            <th style="padding:8px;border-bottom:1px solid #E5E7EB;">Rejection Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableBody}
                    </tbody>
                </table>
            </div>
        `;
    }

    document.querySelectorAll('.btn-view-student').forEach(btn => {
        btn.addEventListener('click', async () => {
            const studentId = btn.dataset.studentId;
            const studentName = btn.dataset.studentName;

            modalStudentName.textContent = studentName || 'Student Details';
            modalBody.innerHTML = '<p>Loading...</p>';
            openModal();

            try {
                const response = await fetch(`/staff/student/${studentId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (!response.ok) throw new Error('Failed to load student details.');
                const data = await response.json();
                renderModalContent(data);
            } catch (err) {
                modalBody.innerHTML = `<p style="color:red;">${escapeHtml(err.message)}</p>`;
            }
        });
    });

    modalClose.addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', closeModal);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
</script>
</body>
</html>
