<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name')->unique();
            $table->timestamps();

            // Indeks do wyszukiwania ras autouzupełniania dla konkretnego użytkownika
            $table->index(['user_id', 'name'], 'idx_breeds_user_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breeds');
    }
};