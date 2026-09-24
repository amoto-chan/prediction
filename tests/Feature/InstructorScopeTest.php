<?php

namespace Tests\Feature;

use App\Models\AcademicRecord;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorScopeTest extends TestCase
{
    use RefreshDatabase;

    private array $fixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $instructorA = User::factory()->create([
            'role' => 'instructor',
            'subjects' => ['IT Elective: Web Systems'],
            'sections' => ['BSIT 3A'],
        ]);
        $instructorB = User::factory()->create([
            'role' => 'instructor',
            'subjects' => ['Networking 1'],
            'sections' => ['BSIT 3B'],
        ]);
        $studentA = User::factory()->create(['role' => 'student', 'section' => 'BSIT 3A', 'student_id' => 'BSIT-A-1', 'academic_year' => '2026-2027']);
        $studentB = User::factory()->create(['role' => 'student', 'section' => 'BSIT 3B', 'student_id' => 'BSIT-B-1', 'academic_year' => '2026-2027']);

        $recordB = new AcademicRecord([
            'student_id' => $studentB->id,
            'instructor_id' => $instructorB->id,
            'subject' => 'Networking 1',
            'section' => 'BSIT 3B',
            'academic_year' => '2026-2027',
            'attendance' => 55,
        ]);
        $recordB->recalculate()->save();

        $this->fixtures = compact('instructorA', 'instructorB', 'studentA', 'studentB', 'recordB');
    }

    public function test_dashboard_only_shows_records_in_assigned_scope(): void
    {
        $response = $this->actingAs($this->fixtures['instructorA'])->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee($this->fixtures['studentB']->name);
        $response->assertDontSee('Networking 1');
    }

    public function test_instructor_cannot_add_record_outside_assigned_sections(): void
    {
        $this->actingAs($this->fixtures['instructorA'])->post(route('records.store'), [
            'name' => 'Outsider',
            'student_id' => 'BSIT-OUT-1',
            'email' => 'outsider@cpsu-hinigaran.edu.ph',
            'subject' => 'IT Elective: Web Systems',
            'section' => 'BSIT 3B',
            'academic_year' => '2026-2027',
            'attendance' => 80,
        ])->assertSessionHasErrors('section');

        $this->assertDatabaseMissing('users', ['email' => 'outsider@cpsu-hinigaran.edu.ph']);
    }

    public function test_instructor_cannot_add_record_outside_assigned_subjects(): void
    {
        $this->actingAs($this->fixtures['instructorA'])->post(route('records.store'), [
            'name' => 'Outsider Two',
            'student_id' => 'BSIT-OUT-2',
            'email' => 'outsider2@cpsu-hinigaran.edu.ph',
            'subject' => 'Networking 1',
            'section' => 'BSIT 3A',
            'academic_year' => '2026-2027',
        ])->assertSessionHasErrors('subject');
    }

    public function test_instructor_can_add_and_edit_records_within_scope(): void
    {
        $instructorA = $this->fixtures['instructorA'];
        $studentA = $this->fixtures['studentA'];

        $this->actingAs($instructorA)->post(route('records.store'), [
            'name' => $studentA->name,
            'student_id' => $studentA->student_id,
            'email' => $studentA->email,
            'subject' => 'IT Elective: Web Systems',
            'section' => 'BSIT 3A',
            'academic_year' => '2026-2027',
            'attendance' => 92,
            'quiz' => 88,
            'final_grade' => 90,
        ])->assertRedirect()->assertSessionHas('success');

        $record = AcademicRecord::where('student_id', $studentA->id)->where('subject', 'IT Elective: Web Systems')->firstOrFail();
        $this->assertSame($instructorA->id, $record->instructor_id);
        $this->assertSame('Passed', $record->prediction);

        $this->actingAs($instructorA)->patch(route('records.update', $record), [
            'attendance' => 50,
            'quiz' => 50,
            'final_grade' => 50,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('Failed', $record->fresh()->prediction);
    }

    public function test_instructor_cannot_edit_another_instructors_record(): void
    {
        $this->actingAs($this->fixtures['instructorA'])
            ->patch(route('records.update', $this->fixtures['recordB']), ['attendance' => 99])
            ->assertForbidden();
    }

    public function test_bulk_messaging_is_limited_to_scope(): void
    {
        $instructorA = $this->fixtures['instructorA'];
        $studentA = $this->fixtures['studentA'];
        $studentB = $this->fixtures['studentB'];

        $this->actingAs($instructorA)->post(route('messages.bulk'), [
            'recipient_ids' => [$studentB->id],
            'subject' => 'Hello',
            'body' => 'Should not send.',
        ])->assertForbidden();

        $this->actingAs($instructorA)->post(route('messages.bulk'), [
            'recipient_ids' => [$studentA->id],
            'subject' => 'Academic consultation',
            'body' => 'Please see me regarding your At Risk prediction.',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('messages', [
            'sender_id' => $instructorA->id,
            'recipient_id' => $studentA->id,
            'subject' => 'Academic consultation',
        ]);
        $this->assertDatabaseHas('system_logs', ['user_id' => $instructorA->id, 'action' => 'Sent message']);
        $this->assertNull(Message::where('recipient_id', $studentB->id)->first());
    }

    public function test_messages_page_lists_only_in_scope_students(): void
    {
        $response = $this->actingAs($this->fixtures['instructorA'])->get(route('messages.index'));

        $response->assertOk();
        $response->assertSee($this->fixtures['studentA']->name);
        $response->assertDontSee($this->fixtures['studentB']->name);
    }
}