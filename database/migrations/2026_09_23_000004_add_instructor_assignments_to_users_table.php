<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Instructor assignments: which subjects and sections each instructor handles.
            $table->text('subjects')->nullable()->after('academic_year');
            $table->text('sections')->nullable()->after('subjects');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['subjects', 'sections']);
        });
    }
};