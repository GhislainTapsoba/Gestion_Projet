<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\ActivityLog;
use App\Jobs\SendTaskAssignmentEmail;

class TaskObserver
{
    public function created(Task $task): void
    {
        ActivityLog::logActivity('created', $task);
        
        if ($task->assigned_to) {
            SendTaskAssignmentEmail::dispatch($task);
        }
    }

    public function updated(Task $task): void
    {
        ActivityLog::logActivity('updated', $task);

        // Si la tâche vient d'être assignée
        if ($task->isDirty('assigned_to') && $task->assigned_to) {
            SendTaskAssignmentEmail::dispatch($task);
        }

        // Si le statut change vers "terminé"
        if ($task->isDirty('status') && $task->status === 'termine') {
            $this->checkStageCompletion($task);
        }
    }

    public function deleted(Task $task): void
    {
        ActivityLog::logActivity('deleted', $task);
    }

    private function checkStageCompletion(Task $task): void
    {
        $stage = $task->stage;
        
        if (!$stage) return;

        $totalTasks = $stage->tasks()->count();
        $completedTasks = $stage->tasks()->where('status', 'termine')->count();

        if ($totalTasks > 0 && $totalTasks === $completedTasks && $stage->status !== 'termine') {
            $stage->update([
                'status' => 'termine',
                'completed_at' => now(),
            ]);

            ActivityLog::logActivity('auto_completed', $stage, null, 
                "Étape '{$stage->name}' automatiquement marquée comme terminée"
            );

            // Démarrer les étapes dépendantes
            $this->startDependentStages($stage);
        }
    }

    private function startDependentStages($stage): void
    {
        $stage->dependentStages()
            ->where('status', 'en_attente')
            ->each(function ($dependentStage) {
                if ($dependentStage->canStart()) {
                    $dependentStage->update([
                        'status' => 'en_cours',
                        'started_at' => now(),
                    ]);

                    ActivityLog::logActivity('auto_started', $dependentStage, null,
                        "Étape '{$dependentStage->name}' automatiquement démarrée"
                    );
                }
            });
    }
}
