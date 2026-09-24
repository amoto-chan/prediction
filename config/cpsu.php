<?php

return [
    'school' => env('SCHOOL_NAME', 'Central Philippines State University'),
    'campus' => env('CAMPUS_NAME', 'Hinigaran Campus'),
    'department' => env('DEPARTMENT_NAME', 'College of Computer Studies'),
    'address' => env('CAMPUS_ADDRESS', 'Negros Occidental, Philippines'),
    'email' => env('CONTACT_EMAIL', 'ccs@cpsu.edu.ph'),
    'phone' => env('CONTACT_PHONE', 'Contact the CCS office for the official number'),
    'office_hours' => env('OFFICE_HOURS', 'Monday–Friday, 8:00 AM–5:00 PM'),
    'allow_demo_seed' => filter_var(env('ALLOW_DEMO_SEED', false), FILTER_VALIDATE_BOOL),
];

