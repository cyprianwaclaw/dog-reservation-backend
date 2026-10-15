<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('dog_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();

            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->decimal('price', 8, 2);
            $table->text('description')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();

            // Szybkie wyszukiwanie wizyt w przedziale czasowym dla konkretnego użytkownika (kalendarz)
            $table->index(['user_id', 'start_time', 'end_time']);
            // Pobieranie historii wizyt dla konkretnego psa
            $table->index(['dog_id', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
