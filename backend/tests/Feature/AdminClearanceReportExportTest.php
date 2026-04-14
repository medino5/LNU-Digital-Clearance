<?php

namespace Tests\Feature;

use App\Models\Semester;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ZipArchive;
use Tests\TestCase;

class AdminClearanceReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_download_completed_clearance_excel_report_for_selected_period(): void
    {
        $clearance = $this->createCompletedSeededClearance(
            remarks: 'Approved for downloadable report coverage.',
        );
        $admin = $this->seededAdminUser();
        $semester = $clearance->semester;

        $response = $this->actingAs($admin)->post(
            route('admin.clearance-reports.completed.export'),
            [
                'semester_id' => $semester->id,
                'academic_year' => $semester->displayAcademicYear(),
            ]
        );

        $response->assertOk();
        $response->assertDownload();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $file = $response->baseResponse->getFile();
        $this->assertNotNull($file);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($file->getPathname()) === true);

        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $summarySheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $programSheetXml = $zip->getFromName('xl/worksheets/sheet2.xml');

        $zip->close();

        $this->assertIsString($workbookXml);
        $this->assertIsString($summarySheetXml);
        $this->assertIsString($programSheetXml);

        $this->assertStringContainsString('Summary', $workbookXml);
        $this->assertStringContainsString('BSIT', $workbookXml);

        $this->assertStringContainsString('Completed Clearance Report', $summarySheetXml);
        $this->assertStringContainsString('2nd Semester 2024-2025', $summarySheetXml);
        $this->assertStringContainsString('2024-2025', $summarySheetXml);
        $this->assertStringContainsString('BSIT', $summarySheetXml);

        $this->assertStringContainsString('2302314', $programSheetXml);
        $this->assertStringContainsString('John A. Doe', $programSheetXml);
        $this->assertStringContainsString($clearance->reference_number, $programSheetXml);
    }

    public function test_export_redirects_with_message_when_selected_period_has_no_completed_clearances(): void
    {
        $admin = $this->seededAdminUser();

        $semester = Semester::create([
            'label' => '1st Semester 2025-2026',
            'academic_year' => '2025-2026',
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.clearance-history.index'))
            ->post(route('admin.clearance-reports.completed.export'), [
                'semester_id' => $semester->id,
                'academic_year' => '2025-2026',
            ]);

        $response->assertRedirect(route('admin.clearance-history.index'));
        $response->assertSessionHas('error', 'No completed clearances found for the selected semester and academic year.');
    }
}
