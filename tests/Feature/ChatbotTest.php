<?php

namespace Tests\Feature;

use App\Models\AcademicRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_receives_personalised_advice_for_weakest_indicator(): void
    {
        $student = User::factory()->create(['role' => 'student', 'section' => 'BSIT 3A']);

        $record = new AcademicRecord([
            'student_id' => $student->id,
            'subject' => 'IT Elective: Web Systems',
            'section' => 'BSIT 3A',
            'academic_year' => '2026-2027',
            'attendance' => 40,
        ]);
        $record->recalculate()->save();

        $response = $this->actingAs($student)->postJson(route('chat.answer'), [
            'question' => 'How can I improve?',
        ]);

        $response->assertOk();
        $answer = $response->json('answer');
        $this->assertIsString($answer);
        $this->assertStringContainsString('Attendance', $answer);
        $this->assertStringContainsString('40', $answer);
    }

    public function test_chatbot_reports_prediction_status(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $record = new AcademicRecord([
            'student_id' => $student->id,
            'subject' => 'IT Elective: Web Systems',
            'section' => 'BSIT 3A',
            'academic_year' => '2026-2027',
            'attendance' => 95,
            'quiz' => 92,
            'final_grade' => 94,
        ]);
        $record->recalculate()->save();

        $response = $this->actingAs($student)->postJson(route('chat.answer'), [
            'question' => 'What is my prediction status?',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('Passed', $response->json('answer'));
    }

    public function test_chatbot_explains_missing_prediction(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->postJson(route('chat.answer'), [
            'question' => 'Why is there no prediction yet?',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('No Prediction yet', $response->json('answer'));
    }

    public function test_guest_cannot_use_chatbot(): void
    {
        $this->postJson(route('chat.answer'), ['question' => 'hi'])->assertUnauthorized();
    }
}