<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectService $projectService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Project::with(['chefProjet', 'stages'])
                        ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('chef_projet_id')) {
            $query->where('chef_projet_id', $request->chef_projet_id);
        }

        // Permissions : les employés ne voient que leurs projets
        $user = $request->user();
        if ($user->role === 'employe') {
            $query->where(function($q) use ($user) {
                $q->where('chef_projet_id', $user->id)
                  ->orWhereJsonContains('team_members', $user->id);
            });
        }

        $projects = $query->paginate($request->per_page ?? 15);

        // Ajouter les stats pour chaque projet
        $projects->getCollection()->transform(function ($project) {
            $project->stats = $this->projectService->getProjectStats($project);
            return $project;
        });

        return response()->json($projects);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'chef_projet_id' => 'required|exists:users,id',
            'team_members' => 'nullable|array',
            'team_members.*' => 'exists:users,id',
            'stages' => 'nullable|array',
            'stages.*.name' => 'required|string|max:255',
            'stages.*.estimated_duration' => 'required|integer|min:1',
        ]);

        $project = $this->projectService->createProject($request->all());

        return response()->json([
            'project' => $project,
            'message' => 'Projet créé avec succès'
        ], 201);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        // Vérifier les permissions
        if (!$request->user()->canManageProject($project) && 
            !in_array($request->user()->id, $project->team_members ?? [])) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $project->load(['chefProjet', 'stages.tasks.assignedUser', 'tasks']);
        $project->stats = $this->projectService->getProjectStats($project);

        return response()->json(['project' => $project]);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        if (!$request->user()->canManageProject($project)) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $request->validate([
            'title' => 'string|max:255',
            'description' => 'string',
            'start_date' => 'date',
            'end_date' => 'date|after:start_date',
            'status' => 'in:planifie,en_cours,termine,suspendu',
            'team_members' => 'array',
            'team_members.*' => 'exists:users,id',
        ]);

        $project = $this->projectService->updateProject($project, $request->all());

        return response()->json([
            'project' => $project,
            'message' => 'Projet mis à jour avec succès'
        ]);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        if (!$request->user()->canManageProject($project)) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $this->projectService->deleteProject($project);

        return response()->json([
            'message' => 'Projet supprimé avec succès'
        ]);
    }

    public function stages(Request $request, Project $project): JsonResponse
    {
        if (!$request->user()->canManageProject($project) && 
            !in_array($request->user()->id, $project->team_members ?? [])) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $stages = $project->stages()->with(['tasks.assignedUser'])->get();

        return response()->json(['stages' => $stages]);
    }

    public function tasks(Request $request, Project $project): JsonResponse
    {
        if (!$request->user()->canManageProject($project) && 
            !in_array($request->user()->id, $project->team_members ?? [])) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $query = $project->tasks()->with(['assignedUser', 'stage']);

        // Filtres
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $tasks = $query->orderBy('due_date', 'asc')->get();

        return response()->json(['tasks' => $tasks]);
    }
}