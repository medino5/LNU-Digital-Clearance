<?php

namespace App\Http\Controllers;

use App\Models\Clearance;
use App\Models\Semester;
use App\Support\CompletedClearanceReportExporter;
use Illuminate\Http\Request;

class AdminClearanceReportController extends Controller
{
    public function __construct(
        protected CompletedClearanceReportExporter $exporter,
    ) {
    }

    public function export(Request $request)
    {
        $data = $this->validateForm(
            $request,
            'historyExport',
            [
                'semester_id' => ['required', 'integer', 'exists:semesters,id'],
                'academic_year' => ['required', 'regex:/^\d{4}-\d{4}$/'],
            ],
            $this->adminSectionUrl('history-records'),
            [
                'academic_year.regex' => 'Academic year must use the YYYY-YYYY format.',
            ],
        );

        $semester = Semester::query()->findOrFail($data['semester_id']);

        if ($semester->displayAcademicYear() !== $data['academic_year']) {
            return $this->redirectWithInputAndMessage(
                $request,
                $this->adminSectionUrl('history-records'),
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
                $this->adminSectionUrl('history-records'),
                'error',
                'No completed clearances found for the selected semester and academic year.',
            );
        }

        $filePath = $this->exporter->export($semester, $clearances);
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
