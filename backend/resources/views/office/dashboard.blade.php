@extends('layouts.portal', ['title' => 'Office Dashboard'])

@section('page')
    <div class="topbar">
        <div>
            <h1>{{ $officeAccount->display_name }}</h1>
            <p>
                {{ $officeAccount->officeTypeLabel() }}
                @if($officeAccount->scopeLabel())
                    | Scope: {{ $officeAccount->scopeLabel() }}
                @endif
            </p>
        </div>
        <div class="toolbar">
            <span>{{ auth()->user()->username }}</span>
            <a class="button topbar-action" href="{{ route('admin.login') }}">Switch to Admin Portal</a>
            <form method="POST" action="{{ route('portal.logout') }}" class="topbar-form">
                @csrf
                <button type="submit" class="topbar-action">Log Out / Switch Account</button>
            </form>
        </div>
    </div>

    <div class="content stack">
        @if(session('success'))
            <div class="callout success">{{ session('success') }}</div>
        @endif

        @if(session('info'))
            <div class="callout success">{{ session('info') }}</div>
        @endif

        @if(session('error'))
            <div class="callout error">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="callout error">{{ $errors->first() }}</div>
        @endif

        <div class="grid-2">
            <div class="card">
                <div class="eyebrow">Pending</div>
                <h2>Awaiting your action</h2>
                <div class="list">
                    @forelse($pendingSteps as $step)
                        @php($student = $step->clearance->student)
                        <div class="record">
                            <div class="actions-inline" style="justify-content: space-between;">
                                <div>
                                    <strong>{{ $student->user->name }}</strong><br>
                                    <span class="mini">{{ $student->student_id_number }} | {{ $student->program->code }} | {{ $student->yearLevelLabel() }}</span>
                                </div>
                                <span class="badge awaiting_action">Awaiting Action</span>
                            </div>

                            <p class="mini" style="margin-top: 12px;">
                                Clearance status: <strong>{{ ucwords(str_replace('_', ' ', $step->clearance->status)) }}</strong>
                            </p>

                            @if($step->remarks)
                                <p class="mini"><strong>Last note:</strong> {{ $step->remarks }}</p>
                            @endif

                            <form method="POST" action="{{ route('office.steps.process', $step) }}">
                                @csrf
                                <label>
                                    Optional remarks
                                    <textarea name="remarks" placeholder="Add notes for the student or office history"></textarea>
                                </label>
                                <div class="actions-inline">
                                    <button type="submit" name="action" value="approve">Approve</button>
                                    <button type="submit" name="action" value="flag" class="warn">Flag</button>
                                </div>
                            </form>
                        </div>
                    @empty
                        <div class="record">
                            <p class="muted" style="margin: 0;">No routed students are waiting on this office right now.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="eyebrow">Processed</div>
                <h2>Recently completed office actions</h2>
                <div class="list">
                    @forelse($processedSteps as $step)
                        @php($student = $step->clearance->student)
                        <div class="record">
                            <div class="actions-inline" style="justify-content: space-between;">
                                <div>
                                    <strong>{{ $student->user->name }}</strong><br>
                                    <span class="mini">{{ $student->student_id_number }} | {{ $student->program->code }} | {{ $student->yearLevelLabel() }}</span>
                                </div>
                                <span class="badge {{ $step->status }}">{{ ucwords(str_replace('_', ' ', $step->status)) }}</span>
                            </div>

                            <p class="mini" style="margin-top: 12px;">
                                Processed: {{ optional($step->signed_at)->format('M d, Y h:i A') ?? 'Pending timestamp' }}
                            </p>

                            @if($step->remarks)
                                <p class="mini"><strong>Remarks:</strong> {{ $step->remarks }}</p>
                            @endif

                            <p class="mini">
                                Student clearance status:
                                <span class="badge {{ $step->clearance->status }}">{{ ucwords(str_replace('_', ' ', $step->clearance->status)) }}</span>
                            </p>
                        </div>
                    @empty
                        <div class="record">
                            <p class="muted" style="margin: 0;">No processed records yet for this office account.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
