<?php

namespace App\Services;

use App\Models\Task;
use App\Models\EmailNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    public function getTaskAssignmentTemplate(Task $task): string
    {
        return view('emails.task-assignment', compact('task'))->render();
    }

    public function sendEmail(EmailNotification $notification): bool
    {
        try {
            // Logique d'envoi d'email
            // Ici vous pouvez utiliser Mail::send() ou un service externe
            
            $notification->markAsSent();
            
            Log::info("Email envoyé avec succès", [
                'notification_id' => $notification->id,
                'type' => $notification->type,
                'recipients' => $notification->recipients
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
            
            Log::error("Erreur lors de l'envoi de l'email", [
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}
