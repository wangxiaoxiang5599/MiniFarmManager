<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->date('recorded_on');
            $table->string('type');
            $table->string('description');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['animal_id', 'recorded_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_records');
    }
};
