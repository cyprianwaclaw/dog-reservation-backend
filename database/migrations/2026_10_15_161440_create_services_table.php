<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->timestamps();

            // Indeks do sortowania i wyszukiwania usług użytkownika
            $table->index(['user_id', 'name'], 'idx_services_user_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};