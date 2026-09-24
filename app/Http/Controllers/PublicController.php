<?php

namespace App\Http\Controllers;

use App\Models\AcademicRecord;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class PublicController extends Controller
{
    public function index()
    {
        $stats = [
            'students' => 0,
            'records' => 0,
            'Passed' => 0,
            'At Risk' => 0,
            'Failed' => 0,
        ];

        if (Schema::hasTable('users') && Schema::hasTable('academic_records')) {
            $stats['students'] = User::where('role', User::ROLE_STUDENT)->count();
            $stats['records'] = AcademicRecord::count();

            $byStatus = AcademicRecord::groupBy('prediction')
                ->selectRaw('prediction, count(*) as total')
                ->pluck('total', 'prediction');

            foreach (['Passed', 'At Risk', 'Failed'] as $status) {
                $stats[$status] = (int) ($byStatus[$status] ?? 0);
            }
        }

        return view('welcome', ['stats' => $stats]);
    }
}

