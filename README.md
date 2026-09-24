# CPSU-Hinigaran Academic Performance Predictor

A production-ready **Laravel 11** academic performance prediction system (Blade + Alpine.js + Tailwind CSS) for the College of Computer Studies, Central Philippines State University – Hinigaran Campus.

It predicts **Passed / At Risk / Failed / No Prediction yet** for IT students using eight weighted indicators and provides role-based dashboards, live analytics, messaging, CSV import, an AI chatbot and full system logging.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
npm ci
npm run build
php artisan serve --host=0.0.0.0 --port=8000
```

Open the forwarded port shown by VS Code (usually `http://localhost:8000` locally). Requirements: PHP 8.2–8.4 with `pdo_sqlite` and the Composer extensions listed in [`DEPLOYMENT.md`](DEPLOYMENT.md#requirements), Node 18+, and Composer 2. The PHP upper bound is required by the Laravel Excel 3.1/PhpSpreadsheet 1.x lockfile. The compiled `public/build` assets are included for immediate use; run `npm ci && npm run build` whenever frontend files change.

For a production deployment, follow [`DEPLOYMENT.md`](DEPLOYMENT.md). Do not expose the project root; configure the web server document root as `public/`.

## Release security note

The requested Laravel 11 constraint is retained, but the current framework release line has upstream Composer advisories. The application mitigates the email-validation surface with `email:rfc` plus `App\Rules\SafeEmail`; before exposing a public production service, run `composer audit` and obtain an approved framework upgrade/vendor patch if your policy requires a fully clean advisory result. See [`DEPLOYMENT.md`](DEPLOYMENT.md#security-note).

## Demo accounts (seeded — password `password` for all)

| Role | Email |
| --- | --- |
| Administrator | `admin@cpsu-hinigaran.edu.ph` |
| Instructor (BSIT 3A/3B) | `instructor@cpsu-hinigaran.edu.ph` |
| Instructor (BSIT 4A) | `instructor2@cpsu-hinigaran.edu.ph` |
| Student | `amara.dela.cruz@cpsu-hinigaran.edu.ph` |

Users can **only** sign in if the Administrator created their account (no public registration). Demo credentials are for local evaluation only; never run the demo seeder in production.

## Prediction engine (weighted)

Weights live in `config/prediction.php`:

| Indicator | Weight |
| --- | --- |
| final_grade | 20% |
| attendance, midterm_grade, exam | 15% each |
| quiz, project_output | 10% each |
| assignment | 8% |
| laboratory_activities | 7% |

Score = weighted average of the indicators that **have values** (missing indicators are excluded fairly, never counted as zero).

- **75 – 100** → `Passed`
- **60 – 74.99** → `At Risk`
- **below 60** → `Failed`
- **no indicator values** → `No Prediction yet`

Predictions recalculate automatically on every record create/update/import via `App\Services\PredictionEngine`.

## Roles & key routes

- **Admin** — `/admin/students` (Name, Student ID, Academic performance, Academic year, Section; view/edit/delete + assign sections & subjects), `/admin/instructors` (assign the subjects & sections each instructor handles), `/admin/users` (Add Student / Add Instructor tabs), `/admin/logs` (logins, logouts, updates — exact timestamps + IP, searchable, paginated).
- **Instructor** — scope limited to assigned subjects/sections: add students manually (`/dashboard#record-form`) or by CSV (`/dashboard#import-csv`, template at `/records/template`), edit grades live via modal, message every At Risk / Failed student in scope (`/messages` with “Select all At Risk / Failed”).
- **Student** — own progress table with all eight indicators, enrolled subjects + assigned instructors, personalized rule-based AI chatbot (`/dashboard#chatbot`).

Every authenticated page has the purple/white theme, **dark mode**, a collapsible & closable sidebar, a real-time client clock plus server timestamps, and is fully responsive.

## CSV import

Download the template at `/records/template`. Headers:

```
name,student_id,email,subject,section,academic_year,attendance,quiz,midterm_grade,final_grade,exam,assignment,project_output,laboratory_activities
```

`subject`, `section` and `academic_year` must exist in `config/prediction.php`. Students created by an instructor through manual entry or CSV import receive a cryptographically random, unknown password. An Administrator must set/reset the password from **Manage students → View / Edit** before the student can sign in. This prevents roster imports from granting unapproved login access.

## Tests

```bash
php artisan test
```

Covers weighted prediction thresholds, auth + role dashboards, admin CRUD & subject assignment, instructor scope enforcement (records + messaging), CSV import (success + out-of-scope rejection), live analytics JSON, chatbot safety, throttling, password changes, and transactional import hardening.

## Structure highlights

```
app/Services/PredictionEngine.php    weighted scoring + status bands
app/Models/User.php                  roles, scopedRecords(), scope helpers
app/Http/Controllers/                Public, Auth, Admin, Dashboard, Record, Message, Chat, Profile
app/Console/Commands/CreateAdmin      production first-administrator bootstrap
app/Rules/SafeEmail                   control-character email hardening
app/Imports/AcademicRecordsImport    validated, scope-aware CSV/XLSX import
config/prediction.php                weights, thresholds, subjects, sections, years
database/seeders/DatabaseSeeder      1 admin, 2 instructors, 12 students, 24 records,
                                     messages & realistic system logs
resources/views/                     landing, login, dashboard, admin/*, messages, profile
public/images/                       campus-logo.svg, ccs-logo.svg (replaceable placeholders)
```