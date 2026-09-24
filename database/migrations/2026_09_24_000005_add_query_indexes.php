<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->index('role');
        });

        Schema::table('academic_records', function (Blueprint $table): void {
            $table->index('prediction');
            $table->index(['subject', 'section']);
            $table->index(['student_id', 'academic_year']);
            $table->unique(
                ['student_id', 'subject', 'section', 'academic_year'],
                'academic_records_student_subject_scope_year_unique',
            );
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->index(['recipient_id', 'read_at']);
        });

        Schema::table('system_logs', function (Blueprint $table): void {
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('system_logs', function (Blueprint $table): void {
            $table->dropIndex(['action']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->dropIndex(['recipient_id', 'read_at']);
        });

        Schema::table('academic_records', function (Blueprint $table): void {
            $table->dropIndex(['prediction']);
            $table->dropIndex(['subject', 'section']);
            $table->dropIndex(['student_id', 'academic_year']);
            $table->dropUnique('academic_records_student_subject_scope_year_unique');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['role']);
        });
    }
};

