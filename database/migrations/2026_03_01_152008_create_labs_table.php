<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labs', function (Blueprint $table) {
            $table->id();
            $table->string('lab_name'); 
            $table->integer('total_pcs'); 
            $table->decimal('latitude', 10, 8)->nullable()->default(0.0);
            $table->decimal('longitude', 11, 8)->nullable()->default(0.0);
            
            $table->timestamps(); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labs');
    }
};