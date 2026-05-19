<?php

namespace App\Http\Controllers;

use App\Models\Clearance;
use App\Models\Program;
use App\Models\Semester;
use App\Support\CompletedClearanceReportExporter;
use Illuminate\Http\Request;
use RuntimeException;

class AdminClearanceReportController extends Controller
{
    public function __construct(
        protected CompletedClearanceReportExporter $exporter,
    ) {
    }

    public function index(Request $request)
    {
        $selectedSemesterId = $request->integer('history_semester');
        $selectedAcademicYear = trim((string) $request->query('history_academic_year', ''));
        $selectedProgramCode = trim((string) $request->query('history_program_code', ''));
        $semesters = Semester::query()
            ->orderByDesc('academic_year')
            ->orderByRaw("
                CASE
                    WHEN LOWER(label) LIKE '%midyear%' THEN 3
                    WHEN LOWER(label) LIKE '%2nd%' THEN 2
                    WHEN LOWER(label) LIKE '%1st%' THEN 1
                    ELSE 0
                END DESC
            ")
            ->orderByDesc('created_at')
            ->get();
        $programs = Program::orderBy('code')->get(['code', 'name']);
        $reportPreviewRows = Clearance::query()
            ->where('status', Clearance::STATUS_COMPLETED)
            ->selectRaw('semester_id, program_code, COUNT(*) as total, MAX(completed_at) as latest_completed_at')
            ->groupBy('semester_id', 'program_code')
            ->get();

        return view('admin.clearance-history', [
            'semesters' => $semesters,
            'programs' => $programs,
            'reportPreview' => [
                'semesters' => $semesters
                    ->mapWithKeys(fn (Semester $semester) => [
                        (string) $semester->id => [
                            'label' => $semester->label,
                            'academicYear' => $semester->displayAcademicYear(),
                        ],
                    ]),
                'programs' => $programs
                    ->mapWithKeys(fn (Program $program) => [
                        $program->code => [
                            'code' => $program->code,
                            'name' => $program->name,
                        ],
                    ]),
                'rows' => $reportPreviewRows->map(fn ($row) => [
                    'semesterId' => (string) $row->semester_id,
                    'programCode' => (string) $row->program_code,
                    'total' => (int) $row->total,
                    'latestCompletedAt' => $row->latest_completed_at,
                ])->values(),
            ],
            'selectedSemesterId' => $selectedSemesterId,
            'selectedAcademicYear' => $selectedAcademicYear,
            'selectedProgramCode' => $selectedProgramCode,
            'academicYears' => $semesters
                ->map(fn (Semester $semester) => $semester->displayAcademicYear())
                ->filter()
                ->unique()
                ->sortDesc()
                ->values(),
        ]);
    }

    public function export(Request $request)
    {
        $redirectTo = route('admin.clearance-history.index');

        $data = $this->validateForm(
            $request,
            'historyExport',
            [
                'semester_id' => ['required', 'integer', 'exists:semesters,id'],
                'academic_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
                'program_code' => ['nullable', 'string', 'exists:programs,code'],
            ],
            $redirectTo,
            [
                'semester_id.required' => 'Choose a semester before downloading the report.',
                'academic_year.required' => 'Choose an academic year before downloading the report.',
                'academic_year.regex' => 'Academic year must use the YYYY-YYYY format.',
            ],
        );

        $semester = Semester::query()->findOrFail($data['semester_id']);
        $filteredRedirectTo = route('admin.clearance-history.index', [
            'history_semester' => $semester->id,
            'history_academic_year' => $data['academic_year'],
            'history_program_code' => $data['program_code'] ?? null,
        ]);

        if ($semester->displayAcademicYear() !== $data['academic_year']) {
            return $this->redirectWithInputAndMessage(
                $request,
                $filteredRedirectTo,
                'error',
                'The selected semester does not belong to the selected academic year.',
            );
        }

        $clearances = Clearance::query()
            ->where('status', Clearance::STATUS_COMPLETED)
            ->where('semester_id', $semester->id)
            ->when(! empty($data['program_code']), fn ($query) => $query->where('program_code', $data['program_code']))
            ->orderBy('program_code')
            ->orderBy('student_name')
            ->get();

        if ($clearances->isEmpty()) {
            return $this->redirectWithInputAndMessage(
                $request,
                $filteredRedirectTo,
                'error',
                'No completed clearances found for the selected filters.',
            );
        }

        try {
            $filePath = $this->exporter->export($semester, $clearances);
        } catch (RuntimeException $exception) {
            report($exception);

            return $this->redirectWithInputAndMessage(
                $request,
                $filteredRedirectTo,
                'error',
                'Unable to create the Excel report. Please check that PHP ZIP and XML support are enabled, then try again.',
            );
        }

        $fileName = sprintf(
            'completed-clearances-%s-%s%s.xlsx',
            str($semester->label)->lower()->replaceMatches('/[^a-z0-9]+/', '-')->trim('-'),
            $data['academic_year'],
            ! empty($data['program_code']) ? '-' . str($data['program_code'])->lower()->toString() : ''
        );

        return response()->download(
            $filePath,
            $fileName,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        )->deleteFileAfterSend(true);
    }
}
