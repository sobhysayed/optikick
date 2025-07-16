<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('player_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained('users')->cascadeOnDelete();

            $table->unsignedSmallInteger('resting_hr')->comment('bpm');
            $table->unsignedSmallInteger('max_hr')->comment('bpm');
            $table->decimal('hrv', 6, 2)->comment('ms');
            $table->decimal('vo2_max', 5, 2)->comment('ml/kg/min');
            $table->decimal('weight', 5, 2)->comment('kg');
            $table->decimal('reaction_time', 6, 2)->comment('ms');
            $table->decimal('match_consistency', 5, 2)->nullable()->comment('0-100 scale');
            $table->unsignedSmallInteger('minutes_played')->nullable();
            $table->unsignedSmallInteger('training_hours')->nullable()->comment('Weekly training hours');
            $table->decimal('injury_frequency', 4, 2)->nullable()->comment('Injuries per 1000 mins');
            $table->decimal('recovery_time', 5, 2)->nullable()->comment('0-100 scale');
            $table->decimal('fatigue_score', 5, 2)->nullable()->comment('0-100 scale');
            $table->decimal('injury_risk', 5, 2)->nullable()->comment('0-100%');
            $table->decimal('readiness_score', 5, 2)->nullable()->comment('0-100 scale');
            $table->date('recorded_at');
            $table->index(['player_id', 'recorded_at']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_metrics');
    }
};
