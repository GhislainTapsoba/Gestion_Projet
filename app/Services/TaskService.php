<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Project;
use App\Models\Stage;
use App\Models\ActivityLog;
use App\Jobs\SendTaskAssignmentEmail;

class TaskService
{
    public function createTask(array $data): Task
    {
        $task = Task::create([
            'stage_id' => $data['stage_id'],
            'project_id' => $data['project_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'due_date' => $data['due_date'] ?? null,
            'created_by' => auth()->id(),
        ]);

        ActivityLog::logActivity('created', $task, auth()->user(), "Tâche '{$task->title}' créée");

        // Envoyer notification d'assignation si la tâche est assignée
        if ($task->assigned_to) {
            SendTaskAssignmentEmail::dispatch($task);
        }

        return $task->load(['assignedUser', 'stage', 'project']);
    }

    public function updateTask(Task $task, array $data): Task
    {
        $wasAssigned = $task->assigned_to;
        $task->update($data);

        ActivityLog::logActivity('updated', $task, auth()->user(), "Tâche '{$task->title}' modifiée");

        // Envoyer notification si nouvelle assignation
        if (!$wasAssigned && $task->assigned_to) {
            SendTaskAssignmentEmail::dispatch($task);
        }

        return $task->fresh(['assignedUser', 'stage', 'project']);
    }

    public function changeTaskStatus(Task $task, string $status): Task
    {
        $oldStatus = $task->status;
        
        $task->update([
            'status' => $status,
            'completed_at' => $status === 'termine' ? now() : null,
        ]);

        ActivityLog::logActivity('status_changed', $task, auth()->user(), 
            "Statut de la tâche '{$task->title}' changé de '{$oldStatus}' à '{$status}'"
        );

        // Vérifier si l'étape peut être marquée comme terminée
        if ($status === 'termine') {
            $this->checkStageCompletion($task->stage);
        }

        return $task;
    }

    public function assignTask(Task $task, int $userId): Task
    {
        $task->update(['assigned_to' => $userId]);

        ActivityLog::logActivity('assigned', $task, auth()->user(), 
            "Tâche '{$task->title}' assignée à {$task->assignedUser->name}"
        );

        SendTaskAssignmentEmail::dispatch($task);

        return $task->fresh(['assignedUser']);
    }

    public function deleteTask(Task $task): bool
    {
        ActivityLog::logActivity('deleted', $task, auth()->user(), "Tâche '{$task->title}' supprimée");
        
        return $task->delete();
    }

    private function checkStageCompletion(Stage $stage): void
    {
        $totalTasks = $stage->tasks()->count();
        $completedTasks = $stage->tasks()->where('status', 'termine')->count();

        if ($totalTasks > 0 && $totalTasks === $completedTasks && $stage->status !== 'termine') {
            $stage->markAsCompleted();
            
            ActivityLog::logActivity('completed', $stage, auth()->user(), 
                "Étape '{$stage->name}' automatiquement marquée comme terminée"
            );
        }
    }

    public function getTasksByUser(int $userId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Task::where('assigned_to', $userId)
            ->with(['project', 'stage']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        return $query->orderBy('due_date', 'asc')
                    ->orderBy('priority', 'desc')
                    ->get();
    }
}