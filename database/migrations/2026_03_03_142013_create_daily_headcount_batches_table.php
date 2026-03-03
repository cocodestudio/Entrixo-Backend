<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_headcount_batches', function (Blueprint $table) {
            $table->id();
            // Foreign key: Jab main entry delete hogi, toh uske saare batches bhi delete ho jayenge
            $table->foreignId('daily_headcount_id')
                  ->constrained('daily_headcounts')
                  ->onDelete('cascade');
                  
            $table->string('course_id')->nullable();
            $table->string('course_name');
            $table->string('semester');
            $table->integer('total_students');
            $table->integer('pre_lunch');
            $table->integer('post_lunch');
            $table->timestamps();

            // Indexing for relationship performance
            $table->index('daily_headcount_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_headcount_batches');
    }
};