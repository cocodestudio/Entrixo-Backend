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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            // User se connect karne ke liye foreign key
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');
                  
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('general'); // e.g., attendance, assignment, etc.
            $table->string('icon')->nullable();         // Icon name string
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            // Performance ke liye indexing
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};