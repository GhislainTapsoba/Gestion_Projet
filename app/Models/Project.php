<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'status',
        'chef_projet_id',
        'created_by',
        'team_members',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'team_members' => 'array',
    ];

    // Relations
    public function chefProjet()
    {
        return $this->belongsTo(User::class, 'chef_projet_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stages()
    {
        return $this->hasMany(Stage::class)->orderBy('order_index');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    // Methods
    public function getProgressPercentage(): float
    {
        $totalTasks = $this->tasks()->count();
        if ($totalTasks === 0) return 0;

        $completedTasks = $this->tasks()->where('status', 'termine')->count();
        return round(($completedTasks / $totalTasks) * 100, 2);
    }

    public function getCurrentStage(): ?Stage
    {
        return $this->stages()->where('status', 'en_cours')->first();
    }

    public function getTeamMembers()
    {
        if (empty($this->team_members)) return collect();
        
        return User::whereIn('id', $this->team_members)->get();
    }

    public function isOverdue(): bool
    {
        return $this->end_date < now() && $this->status !== 'termine';
    }
}