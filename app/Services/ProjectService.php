<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Stage;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    public function createProject(array $data): Project
    {
        return DB::transaction(function () use ($data) {
            $project = Project::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'chef_projet_id' => $data['chef_projet_id'],
                'created_by' => auth()->id(),
                'team_members' => $data['team_members'] ?? [],
            ]);

            // Créer les étapes si fournies
            if (isset($data['stages'])) {
                $this->createStages($project, $data['stages']);
            }

            ActivityLog::logActivity('created', $project, auth()->user(), "Projet '{$project->title}' créé");

            return $project->load(['stages', 'chefProjet']);
        });
    }

    public function updateProject(Project $project, array $data): Project
    {
        $oldValues = $project->toArray();

        $project->update($data);

        ActivityLog::logActivity('updated', $project, auth()->user(), "Projet '{$project->title}' modifié");

        return $project->fresh(['stages', 'chefProjet']);
    }

    public function deleteProject(Project $project): bool
    {
        ActivityLog::logActivity('deleted', $project, auth()->user(), "Projet '{$project->title}' supprimé");
        
        return $project->delete();
    }

    public function createStages(Project $project, array $stages): void
    {
        foreach ($stages as $index => $stageData) {
            Stage::create([
                'project_id' => $project->id,
                'name' => $stageData['name'],
                'description' => $stageData['description'] ?? null,
                'order_index' => $index + 1,
                'estimated_duration' => $stageData['estimated_duration'],
                'depends_on' => $stageData['depends_on'] ?? null,
            ]);
        }
    }

    public function getProjectStats(Project $project): array
    {
        $stats = [
            'total_tasks' => $project->tasks()->count(),
            'completed_tasks' => $project->tasks()->where('status', 'termine')->count(),
            'in_progress_tasks' => $project->tasks()->where('status', 'en_cours')->count(),
            'overdue_tasks' => $project->tasks()->where('due_date', '<', now())->where('status', '!=', 'termine')->count(),
            'total_stages' => $project->stages()->count(),
            'completed_stages' => $project->stages()->where('status', 'termine')->count(),
            'progress_percentage' => $project->getProgressPercentage(),
            'is_overdue' => $project->isOverdue(),
        ];

        return $stats;
    }
}
