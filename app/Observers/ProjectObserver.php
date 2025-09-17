<?php

namespace App\Observers;

use App\Models\Project;
use App\Models\ActivityLog;

class ProjectObserver
{
    public function created(Project $project): void
    {
        ActivityLog::logActivity('created', $project, null, "Projet '{$project->title}' créé");
    }

    public function updated(Project $project): void
    {
        $changes = [];
        
        if ($project->isDirty('status')) {
            $changes[] = "statut changé de '{$project->getOriginal('status')}' à '{$project->status}'";
        }
        
        if ($project->isDirty('title')) {
            $changes[] = "titre modifié";
        }

        if (!empty($changes)) {
            $description = "Projet '{$project->title}' modifié : " . implode(', ', $changes);
            ActivityLog::logActivity('updated', $project, null, $description);
        }
    }

    public function deleted(Project $project): void
    {
        ActivityLog::logActivity('deleted', $project, null, "Projet '{$project->title}' supprimé");
    }
}
