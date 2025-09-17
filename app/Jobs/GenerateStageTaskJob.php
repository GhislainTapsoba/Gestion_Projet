<?php

namespace App\Jobs;

use App\Models\Stage;
use App\Services\TaskService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateStageTasksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Stage $stage,
        private array $taskTemplates
    ) {}

    public function handle(TaskService $taskService): void
    {
        foreach ($this->taskTemplates as $template) {
            $taskService->createTask([
                'stage_id' => $this->stage->id,
                'project_id' => $this->stage->project_id,
                'title' => $template['title'],
                'description' => $template['description'] ?? null,
                'priority' => $template['priority'] ?? 'medium',
                'due_date' => $template['due_date'] ?? null,
                'assigned_to' => $template['assigned_to'] ?? null,
            ]);
        }
    }
}
