<?php

namespace Tests\Feature;

use App\Models\AcademicRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class RecordImportTest extends TestCase
{
    use RefreshDatabase;

    private function instructor(): User
    {
        return User::factory()->create([
            'role' => 'instructor',
            'subjects' => ['IT Elective: Web Systems'],
            'sections' => ['BSIT 3A'],
        ]);
    }

    public function test_csv_template_downloads_with_expected_headers(): void
    {
        $response = $this->actingAs($this->instructor())->get(route('records.template'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $content = $response->streamedContent();
        $this->assertStringContainsString('"name","student_id","email","subject","section","academic_year"', $content);
        $this->assertStringContainsString('"laboratory_activities"', $content);
    }

    public function test_csv_import_creates_students_and_predicted_records(): void
    {
        $instructor = $this->instructor();

        $csv = implode("\n", [
            'name,student_id,email,subject,section,academic_year,attendance,quiz,midterm_grade,final_grade,exam,assignment,project_output,laboratory_activities',
            'Imported One,BSIT-2026-901,imported.one@cpsu-hinigaran.edu.ph,IT Elective: Web Systems,BSIT 3A,2026-2027,90,88,92,91,89,95,93,90',
            'Imported Two,BSIT-2026-902,imported.two@cpsu-hinigaran.edu.ph,IT Elective: Web Systems,BSIT 3A,2026-2027,55,58,52,60,57,61,54,59',
        ]);

        $file = UploadedFile::fake()->createWithContent('records.csv', $csv);

        $this->actingAs($instructor)->post(route('records.import'), ['file' => $file])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['email' => 'imported.one@cpsu-hinigaran.edu.ph', 'role' => 'student']);
        $this->assertSame(2, AcademicRecord::count());

        $passed = AcademicRecord::where('subject', 'IT Elective: Web Systems')->where('score', '>=', 75)->first();
        $this->assertNotNull($passed);
        $this->assertSame('Passed', $passed->prediction);
        $this->assertDatabaseHas('system_logs', ['user_id' => $instructor->id, 'action' => 'Imported records']);
    }

    public function test_csv_import_rejects_rows_outside_instructor_scope(): void
    {
        $instructor = $this->instructor();

        $csv = implode("\n", [
            'name,student_id,email,subject,section,academic_year,attendance,quiz,midterm_grade,final_grade,exam,assignment,project_output,laboratory_activities',
            'Out of Scope,BSIT-2026-990,out.of.scope@cpsu-hinigaran.edu.ph,IT Elective: Web Systems,BSIT 4B,2026-2027,80,80,80,80,80,80,80,80',
        ]);

        $file = UploadedFile::fake()->createWithContent('records.csv', $csv);

        $this->actingAs($instructor)->post(route('records.import'), ['file' => $file])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, AcademicRecord::count());
    }

    public function test_guests_cannot_import(): void
    {
        $this->post(route('records.import'), ['file' => UploadedFile::fake()->create('a.csv', 10)])
            ->assertRedirect(route('login'));
    }
}