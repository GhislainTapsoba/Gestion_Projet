<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $taskService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Task::with(['assignedUser', 'stage', 'project'])
                     ->orderBy('due_date', 'asc');

        // Filtres
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Permissions : les employés ne voient que leurs tâches ou celles des projets qu'ils gèrent
        $user = $request->user();
        if ($user->role === 'employe') {
            $query->where(function($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhereHas('project', function($projectQuery) use ($user) {
                      $projectQuery->where('chef_projet_id', $user->id);
                  });
            });
        }

        $tasks = $query->paginate($request->per_page ?? 15);

        return response()->json($tasks);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'stage_id' => 'required|exists:stages,id',
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'in:low,medium,high',
            'due_date' => 'nullable|date|after:now',
        ]);

        // Vérifier que l'utilisateur peut créer des tâches pour ce projet
        $project = Project::find($request->project_id);
        if (!$request->user()->canManageProject($project)) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $task = $this->taskService->createTask($request->all());

        return response()->json([
            'task' => $task,
            'message' => 'Tâche créée avec succès'
        ], 201);
    }

    public function show(Request $request, Task $task): JsonResponse
    {
        // Vérifier les permissions
        if (!$this->canAccessTask($request->user(), $task)) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $task->load(['assignedUser', 'stage', 'project', 'creator']);

        return response()->json(['task' => $task]);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        if (!$this->canAccessTask($request->user(), $task)) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'in:low,medium,high',
            'due_date' => 'nullable|date',
        ]);

        $task = $this->taskService->updateTask($task, $request->all());

        return response()->json([
            'task' => $task,
            'message' => 'Tâche mise à jour avec succès'
        ]);
    }

    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        if (!$this->canAccessTask($request->user(), $task)) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $request->validate([
            'status' => 'required|in:a_faire,en_cours,termine'
        ]);

        $task = $this->taskService->changeTaskStatus($task, $request->status);

        return response()->json([
            'task' => $task,
            'message' => 'Statut de la tâche mis à jour avec succès'
        ]);
    }

    public function assign(Request $request, Task $task): JsonResponse
    {
        if (!$request->user()->canManageProject($task->project)) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $request->validate([
            'assigned_to' => 'required|exists:users,id'
        ]);

        $task = $this->taskService->assignTask($task, $request->assigned_to);

        return response()->json([
            'task' => $task,
            'message' => 'Tâche assignée avec succès'
        ]);
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        if (!$request->user()->canManageProject($task->project)) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $this->taskService->deleteTask($task);

        return response()->json([
            'message' => 'Tâche supprimée avec succès'
        ]);
    }

    public function myTasks(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'priority', 'project_id']);
        $tasks = $this->taskService->getTasksByUser($request->user()->id, $filters);

        return response()->json(['tasks' => $tasks]);
    }

    private function canAccessTask($user, Task $task): bool
    {
        return $user->canManageProject($task->project) || 
               $task->assigned_to === $user->id ||
               in_array($user->id, $task->project->team_members ?? []);
    }
}
