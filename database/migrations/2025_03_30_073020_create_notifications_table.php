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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Notification content
            $table->enum('type', [
                'reaction',
                'message',
                'assessment',
                'training_program',
                'stats_update'
            ]);

            $table->string('title');
            $table->text('body');

            // User mention
            $table->foreignId('sender_id')->constrained('users');

            // Contextual links
            $table->foreignId('related_program_id')->nullable()->constrained('training_programs');
            $table->foreignId('related_assessment_id')->nullable()->constrained('assessment_requests');
            $table->foreignId('related_message_id')->nullable()->constrained('messages');

            // Display control
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
