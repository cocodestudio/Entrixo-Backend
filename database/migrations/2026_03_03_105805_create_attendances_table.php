<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_id')->nullable();
            $table->string('device_name')->nullable();
            $table->string('ip_address')->nullable();
            $table->integer('session_id'); 
            $table->integer('subject_id'); 
            $table->string('unique_session_key')->unique();
            $table->date('date_key');
            $table->string('status')->default('Present');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('device_info')->nullable();
            $table->string('method')->default('QR_SCAN');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('attendances');
    }
};