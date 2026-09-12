<?php

use App\Enums\AnimalStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->string('tag_number')->unique();
            $table->string('name')->nullable();
            $table->string('species');
            $table->string('sex');
            $table->date('date_of_birth');
            $table->string('breed')->nullable();
            $table->string('status')->default(AnimalStatus::Active->value);
            $table->text('notes')->nullable();
            $table->foreignId('current_paddock_id')
                ->nullable()
                ->constrained('paddocks')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'current_paddock_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
