<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('session_id')->constrained('academic_sessions')->onDelete('cascade');
            $table->string('name'); 
            $table->string('code'); 
            $table->string('faculty_name')->nullable();
            $table->integer('semester');
            $table->json('schedule');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('subjects');
    }
};