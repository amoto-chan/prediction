<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PredictionEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /**
     * Rule-based academic advisor.
     * The router below can be replaced by an OpenAI-ready endpoint without
     * changing the request/response contract: POST { question } → { answer }.
     */
    public function answer(Request $request)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:500'],
        ]);

        $question = strtolower(trim($data['question']));

        return response()->json([
            'answer' => $this->reply($request->user(), $question),
        ]);
    }

    private function reply(User $user, string $question): string
    {
        $p = $this->profile($user);

        if ($question === '') {
            return 'Ask me anything about your academics — e.g. "How can I improve my quiz score?"';
        }

        if (preg_match('/\b(hi|hello|hey|good (morning|afternoon|evening))\b/', $question)) {
            return "Hello {$p['first']}! I am your CPSU-Hinigaran academic coach. Ask me about attendance, quizzes, exams, assignments, projects, labs, your prediction standing, or how to improve.";
        }

        if (str_contains($question, 'attendance') || str_contains($question, 'absent') || str_contains($question, 'present')) {
            return $this->indicatorTip($p, 'attendance', 'Attendance carries 15% of your prediction score. Aim for at least 90% — set a daily alarm, sit near the front, and talk to your instructor before absences pile up.');
        }

        if (str_contains($question, 'quiz') || str_contains($question, 'midterm') || str_contains($question, 'final') || str_contains($question, 'exam') || str_contains($question, 'study')) {
            return $this->indicatorTip($p, 'quiz', 'Quizzes, midterms, finals and exams together drive most of your score. Use active recall: 25-minute review blocks after class, practice problems twice a week, and past modules the day before any assessment.');
        }

        if (str_contains($question, 'assignment') || str_contains($question, 'homework') || str_contains($question, 'seatwork')) {
            return $this->indicatorTip($p, 'assignment', 'Assignments are the easiest marks to protect — start at least two days early, cite your sources, and submit before the due date.');
        }

        if (str_contains($question, 'project') || str_contains($question, 'output') || str_contains($question, 'capstone')) {
            return $this->indicatorTip($p, 'project_output', 'Project outputs reward consistency: break the scope into weekly milestones, push code to Git early and often, and ask your instructor for a rubric check-in halfway through.');
        }

        if (str_contains($question, 'lab') || str_contains($question, 'laboratory') || str_contains($question, 'activity')) {
            return $this->indicatorTip($p, 'laboratory_activities', 'Laboratory activities are hands-on practice — follow the procedure sheet step by step, screenshot your results, and never leave the lab without submitting your worksheet.');
        }

        if (preg_match('/\b(risk|at risk|failed|fail|dropping|irregular)\b/', $question)) {
            if ($p['status'] === 'Failed') {
                $scoreBit = $p['score'] !== null ? " with a weighted score of {$p['score']}%" : '';
                $weak = $p['weakestLabel'] ? " Start with your weakest area — {$p['weakestLabel']} — and meet your instructor this week for a recovery plan." : '';
                return "Your current prediction is Failed{$scoreBit}.{$weak}";
            }
            if ($p['status'] === 'At Risk') {
                $scoreBit = $p['score'] !== null ? " (weighted score {$p['score']}%)" : '';
                $weak = $p['weakestLabel'] ? " Focus on {$p['weakestLabel']} first, then use every allowed retake or make-up before finals." : '';
                return "Your current prediction is At Risk{$scoreBit}. You need at least 75% to reach Passed.{$weak}";
            }
            if ($p['status'] === 'Passed') {
                return 'Great news — your current prediction is Passed. Keep the momentum: stay consistent with attendance and submit every requirement on time.';
            }
            return 'There is no prediction for you yet because no indicators have been recorded. Once your instructor enters grades, your Passed / At Risk / Failed status will appear on your dashboard.';
        }

        if (preg_match('/\b(prediction|predicted|score|standing|result|grade|status|perform)\b/', $question)) {
            if ($p['status'] === PredictionEngine::NO_PREDICTION) {
                return 'There is no prediction yet — your status shows “No Prediction yet” because no indicator (attendance, quiz, midterm, final, exam, assignment, project, or lab) has a recorded value. Ask your instructor to start grading.';
            }
            $breakdown = collect($p['averages'])
                ->filter(fn ($value) => $value !== null)
                ->map(fn ($value, $key) => ($p['labels'][$key] ?? $key).': '.$value.'%')
                ->implode(' · ');
            return "Your weighted score is {$p['score']}% → prediction: {$p['status']}. Indicator breakdown — {$breakdown}.";
        }

        if (str_contains($question, 'subject') || str_contains($question, 'enrolled') || str_contains($question, 'class')) {
            return $p['subjects']
                ? 'You are currently enrolled in: '.implode(', ', $p['subjects']).'.'
                : 'You have no enrolled subjects on record yet. Your instructor or the administrator can assign subjects from the dashboard.';
        }

        if (str_contains($question, 'instructor') || str_contains($question, 'teacher') || str_contains($question, 'prof')) {
            return $p['instructors']
                ? 'Your assigned instructors: '.implode(', ', $p['instructors']).'. Message them from your dashboard if you need help.'
                : 'No instructor is assigned to your records yet.';
        }

        if (preg_match('/\b(improve|improving|better|tips|advice|help|how do i|what should)\b/', $question)) {
            if ($p['weakestLabel']) {
                return "Here is your plan, {$p['first']}: 1) Raise {$p['weakestLabel']} (currently {$p['weakest']}%) — it is your biggest opportunity. 2) Keep attendance above 90%. 3) Submit assignments and projects at least a day early. 4) Review this dashboard after every assessment.";
            }
            return 'Start by asking your instructor to record your indicators, then aim for 75%+ in every category: attendance, quizzes, exams, assignments, projects, and labs.';
        }

        return 'I can help with attendance, quizzes, exams, assignments, projects, labs, your prediction standing, enrolled subjects, and improvement plans. Which of those would you like to explore?';
    }

    /** Personalised indicator line with the student's live average. */
    private function indicatorTip(array $p, string $key, string $tip): string
    {
        if (! $p['is_student']) {
            return $tip;
        }

        $avg = $p['averages'][$key] ?? null;
        $current = $avg === null ? 'no value recorded yet' : "your current average is {$avg}%";

        return "{$tip} Right now {$current}.";
    }

    /** @return array<string, mixed> */
    private function profile(User $user): array
    {
        $labels = config('prediction.indicator_labels');
        $records = $user->isRole('student') ? $user->records->load('instructor') : collect();

        $averages = [];
        foreach (PredictionEngine::INDICATORS as $indicator) {
            $values = $records->pluck($indicator)->filter(fn ($value) => $value !== null);
            $averages[$indicator] = $values->isNotEmpty() ? round((float) $values->avg(), 1) : null;
        }

        $scores = $records->pluck('score')->filter(fn ($value) => $value !== null);
        $status = $scores->isNotEmpty()
            ? app(PredictionEngine::class)->statusFor((float) $scores->avg())
            : PredictionEngine::NO_PREDICTION;

        $ranked = collect($averages)->filter(fn ($value) => $value !== null)->sort();
        $weakestKey = $ranked->keys()->first();

        return [
            'first' => Str::before($user->name, ' '),
            'is_student' => $user->isRole('student'),
            'status' => $status,
            'score' => $scores->isNotEmpty() ? round((float) $scores->avg(), 1) : null,
            'averages' => $averages,
            'labels' => $labels,
            'weakest' => $ranked->first(),
            'weakestLabel' => $weakestKey ? ($labels[$weakestKey] ?? $weakestKey) : null,
            'subjects' => $records->pluck('subject')->unique()->values()->all(),
            'instructors' => $records->pluck('instructor')->filter()->unique('id')->pluck('name')->values()->all(),
        ];
    }
}