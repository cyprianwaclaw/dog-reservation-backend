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

    // Używaj dateTime zamiast timestamp!
    $table->dateTime('start_time');
    $table->dateTime('end_time');

    $table->unsignedInteger('price');
    $table->text('description')->nullable();
    $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
    $table->timestamps();

    $table->index(['user_id', 'start_time', 'end_time'], 'idx_visits_user_dates');
    $table->index(['dog_id', 'start_time'], 'idx_visits_dog_history');
});

    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
