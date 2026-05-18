<?php

namespace Tests\Feature;

use App\Models\Semester;
use App\Support\CompletedClearanceReportExporter;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class AdminClearanceReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_download_reports_page_uses_explicit_report_filters(): void
    {
        $clearance = $this->createCompletedSeededClearance();
        $semester = $clearance->semester;

        $response = $this->actingAs($this->seededAdminUser())
            ->get(route('admin.clearance-history.index', [
                'history_semester' => $semester->id,
            ]));

        $response->assertOk()
            ->assertSee('id="history-export-form"', false)
            ->assertSee('data-export-semester-select', false)
            ->assertSee('name="semester_id"', false)
            ->assertSee('data-export-academic-year-select', false)
            ->assertSee('name="academic_year"', false)
            ->assertSee('name="program_code"', false)
            ->assertSee('Download Reports')
            ->assertDontSee('Program</th>', false)
            ->assertDontSee('Use this for')
            ->assertDontSee('Not shown here')
            ->assertDontSee('Format')
            ->assertSee('Download Excel Report')
            ->assertSee($semester->displayAcademicYear());
    }

    public function test_analytics_page_filters_by_program_and_exports_report(): void
    {
        $clearance = $this->createCompletedSeededClearance();
        $semester = $clearance->semester;

        $response = $this->actingAs($this->seededAdminUser())
            ->get(route('admin.analytics.index', [
                'program_code' => 'BSIT',
                'semester_id' => $semester->id,
                'academic_year' => $semester->displayAcademicYear(),
            ]));

        $response->assertOk()
            ->assertSee('Analytics Dashboard')
            ->assertSee('Requests by Signer / Office')
            ->assertSee('Program Flow')
            ->assertSee('BSIT - Bachelor of Science in Information Technology');

        $export = $this->actingAs($this->seededAdminUser())
            ->get(route('admin.analytics.export', [
                'program_code' => 'BSIT',
                'semester_id' => $semester->id,
                'academic_year' => $semester->displayAcademicYear(),
            ]));

        $export->assertOk();
        $export->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $export->streamedContent();

        $this->assertStringContainsString('Digital Clearance Analytics Report', $content);
        $this->assertStringContainsString('Office Performance', $content);
        $this->assertStringContainsString('Program Flow', $content);
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
                'program_code' => 'BSIT',
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
        $this->assertStringContainsString($semester->label, $summarySheetXml);
        $this->assertStringContainsString($semester->displayAcademicYear(), $summarySheetXml);
        $this->assertStringContainsString('BSIT', $summarySheetXml);

        $this->assertStringContainsString('2302314', $programSheetXml);
        $this->assertStringContainsString('John A. Doe', $programSheetXml);
        $this->assertStringContainsString($clearance->reference_number, $programSheetXml);
    }

    public function test_admin_can_filter_completed_clearance_excel_report_by_program(): void
    {
        $clearance = $this->createCompletedSeededClearance();
        $semester = $clearance->semester;

        $response = $this->actingAs($this->seededAdminUser())->post(
            route('admin.clearance-reports.completed.export'),
            [
                'semester_id' => $semester->id,
                'academic_year' => $semester->displayAcademicYear(),
                'program_code' => 'BSIT',
            ]
        );

        $response->assertOk();
        $response->assertDownload();

        $file = $response->baseResponse->getFile();
        $this->assertNotNull($file);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($file->getPathname()) === true);

        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $zip->close();

        $this->assertIsString($workbookXml);
        $this->assertStringContainsString('BSIT', $workbookXml);
    }

    public function test_export_redirects_with_validation_when_required_filters_are_missing(): void
    {
        $response = $this->actingAs($this->seededAdminUser())
            ->from(route('admin.clearance-history.index'))
            ->post(route('admin.clearance-reports.completed.export'), [
                '_form_key' => 'history-export',
            ]);

        $response->assertRedirect(route('admin.clearance-history.index'));
        $response->assertSessionHasErrors(['semester_id', 'academic_year'], null, 'historyExport');
    }

    public function test_export_redirects_with_message_when_semester_and_academic_year_do_not_match(): void
    {
        $admin = $this->seededAdminUser();

        $semester = Semester::create([
            'label' => 'Midyear 2025-2026',
            'academic_year' => '2025-2026',
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.clearance-history.index'))
            ->post(route('admin.clearance-reports.completed.export'), [
                '_form_key' => 'history-export',
                'semester_id' => $semester->id,
                'academic_year' => '2024-2025',
            ]);

        $response->assertRedirect(route('admin.clearance-history.index', [
            'history_semester' => $semester->id,
            'history_academic_year' => '2024-2025',
        ]));
        $response->assertSessionHas('error', 'The selected semester does not belong to the selected academic year.');
    }

    public function test_export_redirects_with_message_when_selected_period_has_no_completed_clearances(): void
    {
        $admin = $this->seededAdminUser();

        $semester = Semester::create([
            'label' => 'Midyear 2025-2026',
            'academic_year' => '2025-2026',
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.clearance-history.index'))
            ->post(route('admin.clearance-reports.completed.export'), [
                'semester_id' => $semester->id,
                'academic_year' => '2025-2026',
            ]);

        $response->assertRedirect(route('admin.clearance-history.index', [
            'history_semester' => $semester->id,
            'history_academic_year' => '2025-2026',
        ]));
        $response->assertSessionHas('error', 'No completed clearances found for the selected filters.');
    }

    public function test_export_redirects_with_message_when_report_file_cannot_be_created(): void
    {
        $clearance = $this->createCompletedSeededClearance();
        $semester = $clearance->semester;

        $this->app->instance(CompletedClearanceReportExporter::class, new class extends CompletedClearanceReportExporter
        {
            public function export(Semester $semester, Collection $clearances): string
            {
                throw new RuntimeException('Spreadsheet support is unavailable.');
            }
        });

        $response = $this->actingAs($this->seededAdminUser())
            ->from(route('admin.clearance-history.index'))
            ->post(route('admin.clearance-reports.completed.export'), [
                '_form_key' => 'history-export',
                'semester_id' => $semester->id,
                'academic_year' => $semester->displayAcademicYear(),
            ]);

        $response->assertRedirect(route('admin.clearance-history.index', [
            'history_semester' => $semester->id,
            'history_academic_year' => $semester->displayAcademicYear(),
        ]));
        $response->assertSessionHas(
            'error',
            'Unable to create the Excel report. Please check that PHP ZIP and XML support are enabled, then try again.'
        );
    }
}
