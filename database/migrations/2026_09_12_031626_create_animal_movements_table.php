<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_paddock_id')
                ->nullable()
                ->constrained('paddocks')
                ->nullOnDelete();
            $table->foreignId('to_paddock_id')
                ->nullable()
                ->constrained('paddocks')
                ->nullOnDelete();
            $table->dateTime('moved_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['animal_id', 'moved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_movements');
    }
};
