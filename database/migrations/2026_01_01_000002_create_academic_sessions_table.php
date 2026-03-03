<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('academic_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_name')->unique();
            $table->string('academic_year');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('course_id');
            $table->string('course_name');
            $table->string('target_semester');
            $table->string('description');
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('academic_sessions');
    }
};