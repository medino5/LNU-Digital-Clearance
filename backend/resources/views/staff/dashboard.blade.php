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
</script>
</body>
</html>