<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'sender_id',
        'related_program_id',
        'related_assessment_id',
        'related_message_id',
        'read_at',
        'is_pinned'
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'is_pinned' => 'boolean'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function relatedProgram()
    {
        return $this->belongsTo(TrainingProgram::class, 'related_program_id');
    }

    public function relatedAssessment()
    {
        return $this->belongsTo(AssessmentRequest::class, 'related_assessment_id');
    }

    public function relatedMessage()
    {
        return $this->belongsTo(Message::class, 'related_message_id');
    }
    public function pin(): bool
    {
        return $this->update(['is_pinned' => true]);
    }

    public function unpin(): bool
    {
        return $this->update(['is_pinned' => false]);
    }

    public function getNavigateToAttribute(): ?string
    {
        switch ($this->type) {
            case 'message':
            case 'reaction':
            return $this->sender_id ? '/messages/conversation/' . $this->sender_id : null;

            case 'training_program':
                return '/training-program/current';

            default:
                return "No action available for this notification.";
        }
    }
}
