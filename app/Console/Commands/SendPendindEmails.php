<?php

namespace App\Console\Commands;

use App\Models\EmailNotification;
use App\Services\EmailService;
use Illuminate\Console\Command;

class SendPendingEmails extends Command
{
    protected $signature = 'emails:send-pending';
    protected $description = 'Envoyer les emails en attente';

    public function handle(EmailService $emailService): void
    {
        $pendingEmails = EmailNotification::where('status', 'pending')
                                        ->orderBy('created_at')
                                        ->limit(50)
                                        ->get();

        $sent = 0;
        $failed = 0;

        foreach ($pendingEmails as $email) {
            if ($emailService->sendEmail($email)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        $this->info("Emails traités : {$sent} envoyés, {$failed} échoués");
    }
}