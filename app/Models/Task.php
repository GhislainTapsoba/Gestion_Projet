<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'stage_id',
        'project_id',
        'title',
        'description',
        'assigned_to',
        'priority',
        'status',
        'due_date',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relations
    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Methods
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date < now() && $this->status !== 'termine';
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'termine',
            'completed_at' => now(),
        ]);

        // Vérifier si l'étape peut être marquée comme terminée
        $this->stage->checkCompletion();
    }

    public function getPriorityColor(): string
    {
        return match($this->priority) {
            'high' => 'red',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray'
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'a_faire' => 'À faire',
            'en_cours' => 'En cours',
            'termine' => 'Terminé',
            default => 'Inconnu'
        };
    }
}