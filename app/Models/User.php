<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Concerns\FilamentUser;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, FilamentUser;

    protected $fillable = [
        'name',           // Add this line
        'email',
        'password',
        'role',
        'status'
    ];

    // Add this method for Filament
    public function getFilamentName(): string
    {
        return $this->name ?? $this->email ?? 'User';
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class, 'user_id')
            ->orWhere('participant_id', $this->id);
    }

    public function participatedConversations()
    {
        return $this->hasMany(Conversation::class, 'participant_id');
    }

    // Fix the receivedMessages relationship (it's using receiver_id instead of recipient_id)
    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    // Update sendMessage method to work with conversations
    public function sendMessage(User $recipient, array $data): Message
    {
        $conversation = Conversation::firstOrCreate([
            'user_id' => min($this->id, $recipient->id),
            'participant_id' => max($this->id, $recipient->id)
        ]);

        return $conversation->messages()->create([
            'sender_id' => $this->id,
            'recipient_id' => $recipient->id,
            'type' => $data['type'],
            'content' => $data['type'] === 'text' ? $data['content'] : null,
            'file_url' => $data['file_url'] ?? null
        ]);
    }

    public function trainingPrograms()
    {
        return $this->hasMany(TrainingProgram::class, 'player_id');
    }

    public function assessmentRequests()
    {
        return $this->hasMany(AssessmentRequest::class, 'player_id');
    }

    public function assignedAssessments()
    {
        return $this->hasMany(AssessmentRequest::class, 'doctor_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function sentNotifications()
    {
        return $this->hasMany(Notification::class, 'sender_id');
    }

    public function metrics()
    {
        return $this->hasMany(PlayerMetric::class, 'player_id');
    }

    public function scopePlayers($query)
    {
        return $query->where('role', 'player');
    }

    public function scopeDoctors($query)
    {
        return $query->where('role', 'doctor');
    }

    public function scopeCoaches($query)
    {
        return $query->where('role', 'coach');
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function($user) {
            // Delete only from existing tables
            if (Schema::hasTable('profiles')) {
                $user->profile()->delete();
            }
            if (Schema::hasTable('messages')) {
                $user->sentMessages()->delete();
                $user->receivedMessages()->delete();
            }
            if (Schema::hasTable('conversations')) {
                $user->conversations()->delete();
                $user->participatedConversations()->delete();
            }
            if (Schema::hasTable('training_programs')) {
                $user->trainingPrograms()->delete();
            }
            if (Schema::hasTable('assessment_requests')) {
                $user->assessmentRequests()->delete();
                $user->assignedAssessments()->delete();
            }
            if (Schema::hasTable('notifications')) {
                $user->notifications()->delete();
                $user->sentNotifications()->delete();
            }
            if (Schema::hasTable('player_metrics')) {
                $user->metrics()->delete();
            }
        });
    }

    public function requestAssessment(User $doctor, string $issue, string $message): AssessmentRequest
    {
        return AssessmentRequest::create([
            'player_id' => $this->id,
            'doctor_id' => $doctor->id,
            'issue_type' => $issue,
            'message' => $message,
            'requested_at' => now()
        ]);
    }

    // Add this method to your User model
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_player');
    }

    public const ROLES = [
        'player' => 'Player',
        'coach' => 'Coach',
        'doctor' => 'Doctor',
        'admin' => 'Admin'
    ];

    public function players()
{
    return $this->hasMany(User::class, 'coach_id');
}

public function coach()
{
    return $this->belongsTo(User::class, 'coach_id');
}

public function notifyCoach($title, $body, $type, $metadata = [])
{
    // Find the coach
    $coach = User::where('role', 'coach')->first();

    if ($coach) {
        return Notification::create([
            'user_id' => $coach->id,
            'type' => 'assessment',  // Updated to match enum in migration
            'title' => $title,
            'body' => $body,
            'sender_id' => $this->id,
            'related_program_id' => $metadata['program_id'] ?? null,
            'related_assessment_id' => $metadata['assessment_id'] ?? null,
            'related_message_id' => $metadata['message_id'] ?? null,
            'is_pinned' => false,
            'read_at' => null
        ]);
    }
    return null;
}
}
