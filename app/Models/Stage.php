<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stage extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'order_index',
        'estimated_duration',
        'status',
        'depends_on',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'date',
        'completed_at' => 'date',
    ];

    // Relations
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function dependency()
    {
        return $this->belongsTo(Stage::class, 'depends_on');
    }

    public function dependentStages()
    {
        return $this->hasMany(Stage::class, 'depends_on');
    }

    // Methods
    public function canStart(): bool
    {
        if ($this->depends_on === null) return true;
        
        $dependency = $this->dependency;
        return $dependency && $dependency->status === 'termine';
    }

    public function getProgressPercentage(): float
    {
        $totalTasks = $this->tasks()->count();
        if ($totalTasks === 0) return 0;

        $completedTasks = $this->tasks()->where('status', 'termine')->count();
        return round(($completedTasks / $totalTasks) * 100, 2);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'termine';
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'termine',
            'completed_at' => now(),
        ]);

        // Démarrer automatiquement les étapes dépendantes
        $this->startDependentStages();
    }

    private function startDependentStages(): void
    {
        $this->dependentStages()
            ->where('status', 'en_attente')
            ->each(function (Stage $stage) {
                if ($stage->canStart()) {
                    $stage->update([
                        'status' => 'en_cours',
                        'started_at' => now(),
                    ]);
                }
            });
    }
}
