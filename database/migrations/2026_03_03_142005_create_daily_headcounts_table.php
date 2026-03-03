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
        Schema::create('daily_headcounts', function (Blueprint $table) {
            $table->id();
            // Date ko unique rakha hai taaki ek din ki do entry na ho sakein
            $table->date('date')->unique(); 
            $table->unsignedBigInteger('last_updated_by')->nullable();
            $table->integer('grand_total')->default(0);
            $table->integer('grand_pre_lunch')->default(0);
            $table->integer('grand_post_lunch')->default(0);
            $table->timestamps();

            // Search fast karne ke liye indexing
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_headcounts');
    }
};