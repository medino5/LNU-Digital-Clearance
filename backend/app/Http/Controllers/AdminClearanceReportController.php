<?php

namespace App\Http\Controllers;

use App\Models\Clearance;
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
        $semesters = Semester::orderByDesc('is_active')->orderByDesc('created_at')->get();

        return view('admin.clearance-history', [
            'semesters' => $semesters,
            'selectedSemesterId' => $selectedSemesterId,
            'selectedAcademicYear' => $selectedAcademicYear,
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
            ->orderBy('program_code')
            ->orderBy('student_name')
            ->get();

        if ($clearances->isEmpty()) {
            return $this->redirectWithInputAndMessage(
                $request,
                $filteredRedirectTo,
                'error',
                'No completed clearances found for the selected semester and academic year.',
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
            'completed-clearances-%s-%s.xlsx',
            str($semester->label)->lower()->replaceMatches('/[^a-z0-9]+/', '-')->trim('-'),
            $data['academic_year']
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
