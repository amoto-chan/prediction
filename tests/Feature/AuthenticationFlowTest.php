<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_login_and_dashboard_flow_is_connected(): void
    {
        $this->get(route('home'))->assertOk()->assertSee(route('login'));

        $user = User::factory()->create(['role' => 'student']);
        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->get(route('student.dashboard'))->assertOk();
    }

    public function test_role_based_logins_redirect_to_their_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor', 'sections' => ['BSIT 3A'], 'subjects' => ['Networking 1']]);
        $student = User::factory()->create(['role' => 'student']);

        $this->post(route('login.attempt'), ['email' => $admin->email, 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->post(route('login.attempt'), ['email' => $instructor->email, 'password' => 'password'])->assertRedirect(route('instructor.dashboard'));
        $this->assertAuthenticatedAs($instructor);
        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->post(route('login.attempt'), ['email' => $student->email, 'password' => 'password'])->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($student);
    }

    public function test_guests_are_redirected_from_protected_pages(): void
    {
        foreach (['dashboard', 'messages.index', 'profile.edit', 'admin.users'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_role_based_dashboards_render_for_every_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor', 'sections' => ['BSIT 3A'], 'subjects' => ['Networking 1']]);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('Students monitored');
        $this->actingAs($instructor)->get(route('dashboard'))->assertOk()->assertSee('Add performance record');
        $this->actingAs($student)->get(route('dashboard'))->assertOk()->assertSee('AI study chatbot');
    }

    public function test_logout_ends_session_and_is_logged(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseHas('system_logs', ['user_id' => $user->id, 'action' => 'Logged out']);
    }

    public function test_profile_can_be_updated(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => $user->email,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('Updated Name', $user->fresh()->name);
    }
}