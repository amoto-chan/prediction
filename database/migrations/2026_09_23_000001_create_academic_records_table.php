<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject', 150);
            $table->string('section', 40);
            $table->string('academic_year', 20);
            $table->decimal('attendance', 5, 2)->nullable();
            $table->decimal('quiz', 5, 2)->nullable();
            $table->decimal('midterm_grade', 5, 2)->nullable();
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->decimal('exam', 5, 2)->nullable();
            $table->decimal('assignment', 5, 2)->nullable();
            $table->decimal('project_output', 5, 2)->nullable();
            $table->decimal('laboratory_activities', 5, 2)->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->string('prediction')->default('No Prediction yet');
            $table->timestamps();
            $table->index(['section', 'academic_year']);
        });
    }

    public function down(): void { Schema::dropIfExists('academic_records'); }
};