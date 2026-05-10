<?php

namespace Tests\Unit;

use App\Models\Clearance;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Services\ClearancePdfService;
use App\Services\Pdf\SimplePdfDocument;
use App\Support\CompletedClearanceReportExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class SupportServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_simple_pdf_document_outputs_valid_pdf_header_and_trailer(): void
    {
        $pdf = new SimplePdfDocument();
        $pdf->text(48, 700, 'Digital Clearance');

        $output = $pdf->output();

        $this->assertStringStartsWith('%PDF-1.4', $output);
        $this->assertStringContainsString('xref', $output);
        $this->assertStringContainsString('%%EOF', $output);
    }

    public function test_simple_pdf_document_escapes_parentheses_and_backslashes(): void
    {
        $pdf = new SimplePdfDocument();
        $pdf->text(48, 700, 'Value (A) \\ path');

        $output = $pdf->output();

        $this->assertStringContainsString('Value \\(A\\) \\\\ path', $output);
    }

    public function test_simple_pdf_wrapped_text_returns_lower_y_position(): void
    {
        $pdf = new SimplePdfDocument();

        $endingY = $pdf->wrappedText(
            48,
            700,
            80,
            'This line is intentionally long enough to wrap into several rows.',
            12,
        );

        $this->assertLessThan(700, $endingY);
    }

    public function test_clearance_pdf_service_generates_file_under_clearances_folder(): void
    {
        Storage::fake('local');
        $clearance = Clearance::factory()->create([
            'reference_number' => 'CLR-TEST-0001',
            'status' => Clearance::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        $path = app(ClearancePdfService::class)->generate($clearance);

        Storage::disk('local')->assertExists($path);
        $this->assertSame('clearances/clr-test-0001.pdf', $path);
        $contents = Storage::disk('local')->get($path);

        $this->assertStringStartsWith('%PDF', $contents);
        $this->assertStringContainsString('/Subtype /Image', $contents);
        $this->assertStringContainsString('/XObject', $contents);
    }

    public function test_clearance_pdf_service_uses_fallback_name_when_reference_is_missing(): void
    {
        Storage::fake('local');
        $clearance = Clearance::factory()->create([
            'reference_number' => null,
            'status' => Clearance::STATUS_COMPLETED,
        ]);

        $path = app(ClearancePdfService::class)->generate($clearance);

        $this->assertSame('clearances/clearance-' . $clearance->id . '.pdf', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_excel_exporter_creates_summary_and_program_sheets(): void
    {
        $semester = Semester::factory()->create([
            'label' => '1st Semester 2025-2026',
            'academic_year' => '2025-2026',
        ]);
        $clearances = collect([
            $this->completedClearanceForProgram($semester, 'BSIT', 'Information Technology', '2400001', 'Ana Santos'),
            $this->completedClearanceForProgram($semester, 'BAEL', 'English Language', '2400002', 'Ben Reyes'),
        ]);

        $path = app(CompletedClearanceReportExporter::class)->export($semester, $clearances);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path));
        $workbook = $zip->getFromName('xl/workbook.xml');
        $summary = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($path);

        $this->assertStringContainsString('name="Summary"', $workbook);
        $this->assertStringContainsString('name="BAEL"', $workbook);
        $this->assertStringContainsString('name="BSIT"', $workbook);
        $this->assertStringContainsString('Completed Clearance Report', $summary);
    }

    public function test_excel_exporter_sanitizes_invalid_sheet_name_characters(): void
    {
        $semester = Semester::factory()->create();
        $clearance = $this->completedClearanceForProgram($semester, 'BS/IT:*?', 'Program With Symbols', '2400003', 'Cara Lim');

        $path = app(CompletedClearanceReportExporter::class)->export($semester, collect([$clearance]));

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path));
        $workbook = $zip->getFromName('xl/workbook.xml');
        $zip->close();
        @unlink($path);

        $this->assertStringContainsString('name="BSIT"', $workbook);
        $this->assertStringNotContainsString('BS/IT:*?', $workbook);
    }

    public function test_excel_exporter_keeps_duplicate_program_sheet_names_unique(): void
    {
        $semester = Semester::factory()->create();
        $longCode = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789';
        $clearances = collect([
            $this->completedClearanceForProgram($semester, $longCode, 'Program A', '2400004', 'Dino Cruz'),
            $this->completedClearanceForProgram($semester, $longCode . 'X', 'Program B', '2400005', 'Ella Dizon'),
        ]);

        $path = app(CompletedClearanceReportExporter::class)->export($semester, $clearances);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path));
        $workbook = $zip->getFromName('xl/workbook.xml');
        $zip->close();
        @unlink($path);

        $this->assertStringContainsString('name="ABCDEFGHIJKLMNOPQRSTUVWXYZ12345"', $workbook);
        $this->assertStringContainsString('name="ABCDEFGHIJKLMNOPQRSTUVWXYZ123 1"', $workbook);
    }

    public function test_excel_exporter_sorts_program_sheets_by_program_code(): void
    {
        $semester = Semester::factory()->create();
        $clearances = new Collection([
            $this->completedClearanceForProgram($semester, 'ZZZ', 'Last Program', '2400006', 'Zed Garcia'),
            $this->completedClearanceForProgram($semester, 'AAA', 'First Program', '2400007', 'Abby Flores'),
        ]);

        $path = app(CompletedClearanceReportExporter::class)->export($semester, $clearances);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path));
        $workbook = $zip->getFromName('xl/workbook.xml');
        $zip->close();
        @unlink($path);

        $this->assertLessThan(
            strpos($workbook, 'name="ZZZ"'),
            strpos($workbook, 'name="AAA"')
        );
    }

    private function completedClearanceForProgram(
        Semester $semester,
        string $programCode,
        string $programName,
        string $studentId,
        string $studentName,
    ): Clearance {
        $program = Program::factory()->create([
            'code' => preg_replace('/[^A-Z0-9-]/', '', $programCode) ?: 'PRG',
            'name' => $programName,
            'org_name' => $programCode . ' Org',
        ]);
        $student = Student::factory()->create([
            'student_id_number' => $studentId,
            'program_id' => $program->id,
        ]);

        return Clearance::factory()
            ->forStudentAndSemester($student, $semester)
            ->create([
                'status' => Clearance::STATUS_COMPLETED,
                'reference_number' => 'CLR-' . $studentId,
                'completed_at' => now(),
                'student_name' => $studentName,
                'student_id_number' => $studentId,
                'program_code' => $programCode,
                'program_name' => $programName,
            ]);
    }
}
