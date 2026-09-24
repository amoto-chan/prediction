<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Academic indicators & scoring weights
    |--------------------------------------------------------------------------
    | The weighted scoring engine multiplies each available indicator by its
    | weight and divides by the total weight of the indicators present, so
    | partially graded records are still scored fairly.
    */
    'weights' => [
        'attendance' => 0.15,
        'quiz' => 0.10,
        'midterm_grade' => 0.15,
        'final_grade' => 0.20,
        'exam' => 0.15,
        'assignment' => 0.08,
        'project_output' => 0.10,
        'laboratory_activities' => 0.07,
    ],

    'thresholds' => [
        'passed' => 75,
        'at_risk' => 60,
    ],

    'indicator_labels' => [
        'attendance' => 'Attendance',
        'quiz' => 'Quiz',
        'midterm_grade' => 'Midterm grade',
        'final_grade' => 'Final grade',
        'exam' => 'Exam',
        'assignment' => 'Assignment',
        'project_output' => 'Project output',
        'laboratory_activities' => 'Laboratory activities',
    ],

    /*
    |--------------------------------------------------------------------------
    | Curriculum catalogue (College of Computer Studies – BSIT)
    |--------------------------------------------------------------------------
    */
    'subjects' => [
        'IT Elective: Web Systems',
        'Systems Integration and Architecture',
        'Networking 1',
        'Object-Oriented Programming',
        'Information Assurance and Security 1',
        'Integrative Programming and Technologies 1',
        'Social and Professional Issues',
        'Quantitative Methods (Modeling and Simulation)',
        'Capstone Project and Research 1',
        'Learning Integration and Capstone 1',
    ],

    'sections' => [
        'BSIT 1A', 'BSIT 1B', 'BSIT 2A', 'BSIT 2B',
        'BSIT 3A', 'BSIT 3B', 'BSIT 4A', 'BSIT 4B',
    ],

    'academic_years' => [
        '2024-2025',
        '2025-2026',
        '2026-2027',
    ],
];