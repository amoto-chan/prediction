<?php

namespace Tests\Feature;

use App\Models\AcademicRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_manage_pages_render(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.users'))->assertOk()->assertSee('Add users');
        $this->actingAs($admin)->get(route('admin.students'))->assertOk()->assertSee('Manage students');
        $this->actingAs($admin)->get(route('admin.instructors'))->assertOk()->assertSee('Manage instructors');
        $this->actingAs($admin)->get(route('admin.logs'))->assertOk()->assertSee('System logs');
    }

    public function test_admin_can_create_student_and_instructor_accounts(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'role' => 'student',
            'name' => 'New Student',
            'email' => 'new.student@cpsu-hinigaran.edu.ph',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'student_id' => 'BSIT-2026-999',
            'section' => 'BSIT 3A',
            'academic_year' => '2026-2027',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['email' => 'new.student@cpsu-hinigaran.edu.ph', 'role' => 'student']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'role' => 'instructor',
            'name' => 'New Instructor',
            'email' => 'new.instructor@cpsu-hinigaran.edu.ph',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'subjects' => ['Networking 1'],
            'sections' => ['BSIT 4A'],
        ])->assertRedirect()->assertSessionHas('success');

        $instructor = User::where('email', 'new.instructor@cpsu-hinigaran.edu.ph')->firstOrFail();
        $this->assertSame(['Networking 1'], $instructor->assignedSubjects());
        $this->assertSame(['BSIT 4A'], $instructor->assignedSections());
        $this->assertDatabaseHas('system_logs', ['user_id' => $admin->id, 'action' => 'Created user']);
    }

    public function test_instructor_account_requires_subjects_and_sections(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'role' => 'instructor',
            'name' => 'Incomplete Instructor',
            'email' => 'incomplete@cpsu-hinigaran.edu.ph',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertSessionHasErrors(['subjects', 'sections']);
    }

    public function test_admin_can_edit_student_and_assign_subject(): void
    {
        $admin = $this->admin();
        $student = User::factory()->create([
            'role' => 'student',
            'student_id' => 'BSIT-2026-500',
            'section' => 'BSIT 3A',
            'academic_year' => '2026-2027',
        ]);

        $this->actingAs($admin)->get(route('admin.students.show', $student))->assertOk()->assertSee($student->name);

        $this->actingAs($admin)->patch(route('admin.students.update', $student), [
            'name' => 'Renamed Student',
            'email' => $student->email,
            'student_id' => 'BSIT-2026-500',
            'section' => 'BSIT 3B',
            'academic_year' => '2026-2027',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $student->id, 'name' => 'Renamed Student', 'section' => 'BSIT 3B']);

        $this->actingAs($admin)->post(route('admin.students.subjects', $student), [
            'subject' => 'Networking 1',
        ])->assertRedirect()->assertSessionHas('success');

        $record = AcademicRecord::where('student_id', $student->id)->where('subject', 'Networking 1')->first();
        $this->assertNotNull($record);
        $this->assertSame('No Prediction yet', $record->prediction);

        // Assigning the same subject twice is rejected.
        $this->actingAs($admin)->post(route('admin.students.subjects', $student), [
            'subject' => 'Networking 1',
        ])->assertSessionHasErrors('subject');
    }

    public function test_admin_pages_forbidden_for_other_roles(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $instructor = User::factory()->create(['role' => 'instructor']);

        foreach (['admin.users', 'admin.students', 'admin.instructors', 'admin.logs'] as $route) {
            $this->actingAs($student)->get(route($route))->assertForbidden();
            $this->actingAs($instructor)->get(route($route))->assertForbidden();
        }
    }

    public function test_login_is_logged_in_system_logs(): void
    {
        $admin = $this->admin();

        $this->post(route('login.attempt'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('system_logs', ['user_id' => $admin->id, 'action' => 'Logged in']);
    }
}