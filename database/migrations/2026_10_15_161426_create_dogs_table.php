<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('breed_id')->nullable()->constrained('breeds')->nullOnDelete();
            $table->string('name');
             $table->decimal('age', 5, 1)->nullable();
            $table->decimal('weight', 5, 1)->nullable();
            $table->timestamps();

            // Indeks do wyszukiwania psów danego klienta po imieniu
            $table->index(['client_id', 'name'], 'idx_dogs_client_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dogs');
    }
};
