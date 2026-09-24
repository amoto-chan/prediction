<?php

namespace Database\Seeders;

use App\Models\AcademicRecord;
use App\Models\Message;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\PredictionEngine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') && ! config('cpsu.allow_demo_seed')) {
            throw new \RuntimeException('Demo seed data is disabled in production. Create an administrator with php artisan app:create-admin.');
        }

        $year = config('prediction.academic_years.2');

        $admin = User::create([
            'name' => 'Mara Villanueva',
            'email' => 'admin@cpsu-hinigaran.edu.ph',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $profJunior = User::create([
            'name' => 'Prof. Adrian Santos',
            'email' => 'instructor@cpsu-hinigaran.edu.ph',
            'password' => 'password',
            'role' => 'instructor',
            'subjects' => ['IT Elective: Web Systems', 'Networking 1', 'Object-Oriented Programming'],
            'sections' => ['BSIT 3A', 'BSIT 3B'],
            'avatar_color' => '#0ea5e9',
        ]);

        $profSenior = User::create([
            'name' => 'Prof. Elena Reyes',
            'email' => 'instructor2@cpsu-hinigaran.edu.ph',
            'password' => 'password',
            'role' => 'instructor',
            'subjects' => ['Information Assurance and Security 1', 'Systems Integration and Architecture'],
            'sections' => ['BSIT 4A'],
            'avatar_color' => '#0ea5e9',
        ]);

        // name, student id, section, [attendance, quiz, midterm, final, exam, assignment, project, lab]
        $students = [
            ['Amara Dela Cruz', 'BSIT-2024-001', 'BSIT 3A', [92, 88, 90, 94, 91, 89, 96, 93]],
            ['Noel Ramirez', 'BSIT-2024-002', 'BSIT 3A', [81, 76, 79, 84, 77, 85, 80, 78]],
            ['Jessa Mae Flores', 'BSIT-2024-003', 'BSIT 3A', [72, 64, 68, 70, 66, 74, 70, 69]],
            ['Paolo Mendoza', 'BSIT-2024-004', 'BSIT 3B', [null, null, null, null, null, null, null, null]],
            ['Katrina Sy', 'BSIT-2024-005', 'BSIT 3B', [55, 58, 62, 59, 52, 60, 57, 61]],
            ['Miguel Torres', 'BSIT-2024-006', 'BSIT 3B', [88, 91, 85, 90, 87, 92, 89, 86]],
            ['Rhea Lim', 'BSIT-2024-007', 'BSIT 3A', [64, 59, 63, 61, 58, 66, 62, 60]],
            ['Joshua Garcia', 'BSIT-2024-008', 'BSIT 3A', [95, 93, 97, 96, 94, 92, 98, 95]],
            ['Angelica Ocampo', 'BSIT-2023-009', 'BSIT 4A', [78, 82, 80, 75, 79, 84, 77, 81]],
            ['Dennis Perez', 'BSIT-2023-010', 'BSIT 4A', [70, 66, 72, 68, 64, 71, 69, 67]],
            ['Lovely Ramos', 'BSIT-2023-011', 'BSIT 4A', [57, 61, 55, 58, 60, 56, 59, 54]],
            ['Kevin Alcantara', 'BSIT-2023-012', 'BSIT 4A', [86, 83, 88, 85, 84, 90, 82, 87]],
        ];

        foreach ($students as [$name, $studentId, $section, $scores]) {
            $student = User::create([
                'name' => $name,
                'email' => Str::slug($name, '.').'@cpsu-hinigaran.edu.ph',
                'student_id' => $studentId,
                'role' => 'student',
                'section' => $section,
                'academic_year' => $year,
                'password' => 'password',
            ]);

            $teacher = str_starts_with($section, 'BSIT 4') ? $profSenior : $profJunior;
            $assigned = $teacher->assignedSubjects();

            // Primary subject with recorded indicators.
            $record = new AcademicRecord([
                'student_id' => $student->id,
                'instructor_id' => $teacher->id,
                'subject' => $assigned[0],
                'section' => $section,
                'academic_year' => $year,
            ]);
            foreach (PredictionEngine::INDICATORS as $index => $indicator) {
                $record->{$indicator} = $scores[$index];
            }
            $record->recalculate()->save();

            // Secondary subject — assigned but not yet graded → "No Prediction yet".
            if (isset($assigned[1])) {
                $pending = new AcademicRecord([
                    'student_id' => $student->id,
                    'instructor_id' => $teacher->id,
                    'subject' => $assigned[1],
                    'section' => $section,
                    'academic_year' => $year,
                ]);
                $pending->recalculate()->save();
            }
        }

        // Support messages between staff and at-risk students.
        $byStudentId = fn (string $id) => User::where('student_id', $id)->firstOrFail();

        Message::create([
            'sender_id' => $profJunior->id,
            'recipient_id' => $byStudentId('BSIT-2024-003')->id,
            'subject' => 'Academic consultation — IT Elective: Web Systems',
            'body' => 'Hi Jessa, your current prediction is At Risk. Please see me before Friday so we can build a recovery plan for your quizzes and final project.',
        ]);

        Message::create([
            'sender_id' => $profJunior->id,
            'recipient_id' => $byStudentId('BSIT-2024-005')->id,
            'subject' => 'Urgent: let us turn things around, Katrina',
            'body' => 'Katrina, your prediction is currently Failed. Come to my consultation hours this week — we will prioritise your lowest indicators and agree on make-up tasks where allowed.',
        ]);

        Message::create([
            'sender_id' => $profSenior->id,
            'recipient_id' => $byStudentId('BSIT-2023-011')->id,
            'subject' => 'Information Assurance and Security 1 — check-in',
            'body' => 'Lovely, you are at risk in my subject. Submit your outstanding laboratory worksheets and attend the review session on Monday.',
        ]);

        Message::create([
            'sender_id' => $admin->id,
            'recipient_id' => $profJunior->id,
            'subject' => 'Grading reminder for BSIT 3A / 3B',
            'body' => 'Prof. Santos, please complete the pending grades for Networking 1 so predictions become available before the midterm cut-off. Thank you.',
        ]);

        // Recent system activity so the System Logs page opens with realistic history.
        $seedLogs = [
            [$admin, 'Logged in', 'Authenticated successfully.', '127.0.0.1', now()->subHours(6)],
            [$profJunior, 'Logged in', 'Authenticated successfully.', '192.168.1.14', now()->subHours(3)],
            [$profJunior, 'Imported records', 'Imported 3 performance record(s) from a CSV/XLSX file.', '192.168.1.14', now()->subHours(2)],
            [$profSenior, 'Updated record', 'Updated Information Assurance and Security 1 for Lovely Ramos — At Risk.', '192.168.1.21', now()->subHour()],
            [$admin, 'Created user', 'Created student account for Kevin Alcantara (kevin.alcantara@cpsu-hinigaran.edu.ph).', '127.0.0.1', now()->subMinutes(35)],
            [$profJunior, 'Sent message', 'Sent "Academic consultation — IT Elective: Web Systems" to Jessa Mae Flores.', '192.168.1.14', now()->subMinutes(20)],
            [$profJunior, 'Logged out', 'Session ended.', '192.168.1.14', now()->subMinutes(12)],
        ];

        foreach ($seedLogs as [$actor, $action, $description, $ip, $at]) {
            $log = new SystemLog([
                'user_id' => $actor->id,
                'action' => $action,
                'description' => $description,
                'ip_address' => $ip,
            ]);
            $log->created_at = $at;
            $log->updated_at = $at;
            $log->save();
        }
    }
}