<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\EmailNotification;
use Illuminate\Console\Command;

class CheckOverdueTasks extends Command
{
    protected $signature = 'tasks:check-overdue';
    protected $description = 'Vérifier les tâches en retard et envoyer des alertes';

    public function handle(): void
    {
        $overdueTasks = Task::where('due_date', '<', now())
                           ->where('status', '!=', 'termine')
                           ->with(['assignedUser', 'project.chefProjet'])
                           ->get();

        foreach ($overdueTasks as $task) {
            if ($task->assignedUser) {
                $this->createOverdueNotification($task);
            }
        }

        $this->info("Vérification terminée : {$overdueTasks->count()} tâches en retard trouvées");
    }

    private function createOverdueNotification(Task $task): void
    {
        $recipients = [];
        
        if ($task->assignedUser) {
            $recipients[] = $task->assignedUser->email;
        }
        
        if ($task->project->chefProjet) {
            $recipients[] = $task->project->chefProjet->email;
        }

        EmailNotification::create([
            'type' => 'task_overdue',
            'recipients' => array_unique($recipients),
            'subject' => "Tâche en retard : {$task->title}",
            'body' => "La tâche '{$task->title}' du projet '{$task->project->title}' est en retard.",
            'data' => [
                'task_id' => $task->id,
                'project_id' => $task->project_id,
                'due_date' => $task->due_date,
            ],
        ]);
    }
}
