<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
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

        @if(session('status'))
            <div class="mb-4 rounded border border-blue-300 bg-blue-50 px-4 py-2 text-sm text-blue-800">
                {{ session('status') }}
            </div>
        @endif

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
                            $showRejectField = old('action') === 'reject' && (int) old('signature_id') === (int) $signature->id;
                        @endphp

                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $student?->name ?? 'Unknown Student' }}
                                </div>

                                @if(!empty($signature->remarks))
                                    <button
                                        type="button"
                                        id="remarks-toggle-{{ $signature->id }}"
                                        onclick="toggleRemarks({{ $signature->id }})"
                                        class="mt-2 text-xs font-medium text-blue-700 underline hover:text-blue-900"
                                    >
                                        View Remarks ▼
                                    </button>
                                    <div
                                        id="remarks-content-{{ $signature->id }}"
                                        class="mt-2 hidden text-xs italic text-gray-600"
                                    >
                                        {{ $signature->remarks }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-700">
                                    {{ $student?->student_number ?? 'â€”' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-700">
                                    {{ $student?->program?->name ?? 'â€”' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-sm text-gray-700">
                                    {{ $request?->created_at?->format('M d, Y') ?? 'â€”' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center align-top">
                                <form
                                    id="process-form-{{ $signature->id }}"
                                    method="POST"
                                    action="{{ route('staff.process', $signature) }}"
                                    class="w-64 text-left space-y-2"
                                >
                                    @csrf
                                    <input
                                        type="hidden"
                                        name="action"
                                        id="action-{{ $signature->id }}"
                                        value="{{ (int) old('signature_id') === (int) $signature->id ? old('action') : '' }}"
                                    >
                                    <input type="hidden" name="signature_id" value="{{ $signature->id }}">

                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">
                                            Remarks <span class="text-gray-400">(Optional)</span>
                                        </label>
                                        <textarea
                                            name="remarks"
                                            rows="3"
                                            class="w-full rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="Add a note (optional)..."
                                        >{{ (int) old('signature_id') === (int) $signature->id ? old('remarks') : '' }}</textarea>
                                    </div>

                                    <div id="rejection-field-{{ $signature->id }}" class="{{ $showRejectField ? '' : 'hidden' }}">
                                        <label class="block text-xs font-medium text-gray-700 mb-1">
                                            Rejection Reason <span class="text-red-600">*</span>
                                        </label>
                                        <textarea
                                            id="rejection-reason-{{ $signature->id }}"
                                            name="rejection_reason"
                                            rows="2"
                                            class="w-full rounded border border-gray-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                                            placeholder="Reason for rejection (required)..."
                                        >{{ $showRejectField ? old('rejection_reason') : '' }}</textarea>
                                        @error('rejection_reason')
                                            @if((int) old('signature_id') === (int) $signature->id)
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @endif
                                        @enderror
                                    </div>

                                    <div class="flex items-center justify-center gap-2 pt-1">
                                        <button
                                            type="button"
                                            onclick="handleApprove({{ $signature->id }})"
                                            class="px-3 py-1 text-xs font-semibold rounded bg-green-600 text-white hover:bg-green-700"
                                        >
                                            Approve
                                        </button>
                                        <button
                                            type="button"
                                            onclick="handleReject({{ $signature->id }})"
                                            class="px-3 py-1 text-xs font-semibold rounded bg-red-600 text-white hover:bg-red-700"
                                        >
                                            Reject
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </main>
</div>

<script>
    function handleApprove(signatureId) {
        const form = document.getElementById('process-form-' + signatureId);
        const actionInput = document.getElementById('action-' + signatureId);
        const rejectionField = document.getElementById('rejection-field-' + signatureId);
        const rejectionInput = document.getElementById('rejection-reason-' + signatureId);

        if (actionInput) {
            actionInput.value = 'approve';
        }

        if (rejectionInput) {
            rejectionInput.value = '';
        }

        if (rejectionField) {
            rejectionField.classList.add('hidden');
        }

        form.submit();
    }

    function handleReject(signatureId) {
        const form = document.getElementById('process-form-' + signatureId);
        const actionInput = document.getElementById('action-' + signatureId);
        const rejectionField = document.getElementById('rejection-field-' + signatureId);
        const rejectionInput = document.getElementById('rejection-reason-' + signatureId);

        if (actionInput) {
            actionInput.value = 'reject';
        }

        if (rejectionField && rejectionField.classList.contains('hidden')) {
            rejectionField.classList.remove('hidden');
            if (rejectionInput) {
                rejectionInput.focus();
            }
            return;
        }

        form.submit();
    }

    function toggleRemarks(signatureId) {
        const remarks = document.getElementById('remarks-content-' + signatureId);
        const toggle = document.getElementById('remarks-toggle-' + signatureId);

        if (!remarks || !toggle) return;

        const isHidden = remarks.classList.contains('hidden');
        if (isHidden) {
            remarks.classList.remove('hidden');
            toggle.textContent = 'Hide Remarks ▲';
        } else {
            remarks.classList.add('hidden');
            toggle.textContent = 'View Remarks ▼';
        }
    }
</script>
</body>
</html>
