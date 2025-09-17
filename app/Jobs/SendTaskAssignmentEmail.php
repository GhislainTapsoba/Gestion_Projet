<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\EmailNotification;
use App\Services\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTaskAssignmentEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Task $task
    ) {}

    public function handle(EmailService $emailService): void
    {
        $task = $this->task->load(['assignedUser', 'project.chefProjet', 'project.creator']);
        
        // Préparer les destinataires
        $recipients = [];
        
        // Chef de projet
        if ($task->project->chefProjet) {
            $recipients[] = $task->project->chefProjet->email;
        }
        
        // Responsable général (créateur du projet ou admin)
        if ($task->project->creator && $task->project->creator->id !== $task->project->chef_projet_id) {
            $recipients[] = $task->project->creator->email;
        }

        // Utilisateur assigné
        if ($task->assignedUser) {
            $recipients[] = $task->assignedUser->email;
        }

        $recipients = array_unique($recipients);

        if (empty($recipients)) {
            return;
        }

        // Créer la notification email
        $notification = EmailNotification::create([
            'type' => 'task_assigned',
            'recipients' => $recipients,
            'subject' => "Nouvelle tâche assignée : {$task->title}",
            'body' => $emailService->getTaskAssignmentTemplate($task),
            'data' => [
                'task_id' => $task->id,
                'project_id' => $task->project_id,
                'assigned_to' => $task->assigned_to,
            ],
        ]);

        // Envoyer l'email
        $emailService->sendEmail($notification);
    }
}
