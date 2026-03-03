<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('link')->nullable();
            $table->text('rules')->nullable(); 
            $table->string('file_url')->nullable(); 
            $table->string('file_name')->nullable();
            $table->string('file_extension', 10)->nullable();
            $table->string('course_id'); 
            $table->string('course_name');
            $table->string('semester'); 
            $table->string('uploaded_by')->default('Admin');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};