<?php

namespace Tests\Feature;

use App\Models\AcademicRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_analytics_endpoint_is_scoped_and_json(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $other = User::factory()->create(['role' => User::ROLE_STUDENT]);
        AcademicRecord::create([
            'student_id' => $student->id,
            'subject' => 'Networking 1',
            'section' => 'BSIT 3A',
            'academic_year' => '2026-2027',
            'attendance' => 90,
        ]);
        AcademicRecord::create([
            'student_id' => $other->id,
            'subject' => 'Networking 1',
            'section' => 'BSIT 3A',
            'academic_year' => '2026-2027',
            'attendance' => 50,
        ]);

        $this->actingAs($student)->getJson(route('dashboard.analytics'))
            ->assertOk()
            ->assertJsonPath('counts.Passed', 1)
            ->assertJsonPath('monitoredStudents', 1);
    }

    public function test_academic_records_are_recalculated_by_the_model_on_save(): void
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'section' => 'BSIT 3A',
            'academic_year' => '2026-2027',
        ]);

        $record = AcademicRecord::create([
            'student_id' => $student->id,
            'subject' => 'Networking 1',
            'section' => 'BSIT 3A',
            'academic_year' => '2026-2027',
            'attendance' => 90,
        ]);

        $this->assertSame(90.0, (float) $record->fresh()->score);
        $this->assertSame('Passed', $record->fresh()->prediction);
    }

    public function test_instructor_scope_is_the_intersection_of_subject_and_section(): void
    {
        $instructor = User::factory()->create([
            'role' => User::ROLE_INSTRUCTOR,
            'subjects' => ['Networking 1'],
            'sections' => ['BSIT 3A'],
        ]);
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
            'section' => 'BSIT 3A',
            'academic_year' => '2026-2027',
        ]);
        $record = AcademicRecord::create([
            'student_id' => $student->id,
            'instructor_id' => $instructor->id,
            'subject' => 'Object-Oriented Programming',
            'section' => 'BSIT 3A',
            'academic_year' => '2026-2027',
            'attendance' => 80,
        ]);

        $this->assertFalse($instructor->managesRecord($record));
        $this->assertSame(0, $instructor->scopedRecords()->count());
    }

    public function test_profile_password_change_requires_current_password_and_is_logged(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('system_logs', [
            'user_id' => $user->id,
            'action' => 'Updated profile',
        ]);
    }

    public function test_chatbot_returns_plain_text_and_is_limited_to_students(): void
    {
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $instructor = User::factory()->create(['role' => User::ROLE_INSTRUCTOR]);

        $response = $this->actingAs($student)->postJson(route('chat.answer'), [
            'question' => 'What is my prediction?',
        ])->assertOk();

        $this->assertStringNotContainsString('<b>', $response->json('answer'));
        $this->actingAs($instructor)->postJson(route('chat.answer'), [
            'question' => 'hello',
        ])->assertForbidden();
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        $payload = [
            'email' => 'rate-limit-user@example.com',
            'password' => 'wrong-password',
        ];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.attempt'), $payload)->assertRedirect();
        }

        $this->post(route('login.attempt'), $payload)->assertStatus(429);
    }

    public function test_duplicate_import_rows_are_rejected_atomically(): void
    {
        $instructor = User::factory()->create([
            'role' => User::ROLE_INSTRUCTOR,
            'subjects' => ['Networking 1'],
            'sections' => ['BSIT 3A'],
        ]);

        $csv = implode("\n", [
            'name,student_id,email,subject,section,academic_year,attendance,quiz,midterm_grade,final_grade,exam,assignment,project_output,laboratory_activities',
            'Roll Student,BSIT-2026-777,roll.student@cpsu-hinigaran.edu.ph,Networking 1,BSIT 3A,2026-2027,80,80,80,80,80,80,80,80',
            'Roll Student,BSIT-2026-777,roll.student@cpsu-hinigaran.edu.ph,Networking 1,BSIT 3A,2026-2027,80,80,80,80,80,80,80,80',
        ]);

        $this->actingAs($instructor)->post(route('records.import'), [
            'file' => UploadedFile::fake()->createWithContent('duplicate.csv', $csv),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseCount('academic_records', 0);
        $this->assertDatabaseCount('users', 1);
    }
}

