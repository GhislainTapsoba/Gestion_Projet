<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'recipients',
        'subject',
        'body',
        'data',
        'status',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'recipients' => 'array',
        'data' => 'array',
        'sent_at' => 'datetime',
    ];

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }
}
