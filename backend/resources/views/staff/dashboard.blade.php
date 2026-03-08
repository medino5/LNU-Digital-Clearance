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
                            <td class="px-4 py-3 whitespace-nowrap text-center">
                                <div class="inline-flex gap-2">
                                    <form method="POST" action="{{ route('staff.process', $signature) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="approved">
                                        <button
                                            type="submit"
                                            class="px-3 py-1 text-xs font-semibold rounded bg-green-600 text-white hover:bg-green-700"
                                        >
                                            Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('staff.process', $signature) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="rejected">
                                        <button
                                            type="submit"
                                            class="px-3 py-1 text-xs font-semibold rounded bg-red-600 text-white hover:bg-red-700"
                                        >
                                            Reject
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </main>
</div>
</body>
</html>

