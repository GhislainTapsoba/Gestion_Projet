<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Stats globales selon le rôle
        if ($user->isAdmin()) {
            $stats = $this->getAdminStats();
        } elseif ($user->isChefProjet()) {
            $stats = $this->getChefProjetStats($user);
        } else {
            $stats = $this->getEmployeStats($user);
        }

        return response()->json($stats);
    }

    public function activities(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = ActivityLog::with(['user'])
                            ->orderBy('created_at', 'desc')
                            ->limit(50);

        // Filtrer selon le rôle
        if (!$user->isAdmin()) {
            $projectIds = $user->isChefProjet() 
                ? $user->managedProjects()->pluck('id')
                : Project::whereJsonContains('team_members', $user->id)->pluck('id');
            
            $query->where(function($q) use ($projectIds, $user) {
                $q->where('user_id', $user->id)
                  ->orWhere(function($subQ) use ($projectIds) {
                      $subQ->where('model_type', Project::class)
                           ->whereIn('model_id', $projectIds);
                  })
                  ->orWhere(function($subQ) use ($projectIds) {
                      $subQ->where('model_type', Task::class)
                           ->whereHas('model', function($taskQ) use ($projectIds) {
                               $taskQ->whereIn('project_id', $projectIds);
                           });
                  });
            });
        }

        $activities = $query->get();

        return response()->json(['activities' => $activities]);
    }

    private function getAdminStats(): array
    {
        return [
            'total_projects' => Project::count(),
            'active_projects' => Project::where('status', 'en_cours')->count(),
            'completed_projects' => Project::where('status', 'termine')->count(),
            'overdue_projects' => Project::where('end_date', '<', now())->where('status', '!=', 'termine')->count(),
            'total_tasks' => Task::count(),
            'completed_tasks' => Task::where('status', 'termine')->count(),
            'overdue_tasks' => Task::where('due_date', '<', now())->where('status', '!=', 'termine')->count(),
            'total_users' => \App\Models\User::count(),
            'projects_by_status' => Project::select('status', DB::raw('count(*) as count'))
                                          ->groupBy('status')
                                          ->pluck('count', 'status'),
            'tasks_by_priority' => Task::select('priority', DB::raw('count(*) as count'))
                                      ->groupBy('priority')
                                      ->pluck('count', 'priority'),
        ];
    }

    private function getChefProjetStats($user): array
    {
        $projectIds = $user->managedProjects()->pluck('id');

        return [
            'my_projects' => $user->managedProjects()->count(),
            'active_projects' => $user->managedProjects()->where('status', 'en_cours')->count(),
            'completed_projects' => $user->managedProjects()->where('status', 'termine')->count(),
            'overdue_projects' => $user->managedProjects()->where('end_date', '<', now())->where('status', '!=', 'termine')->count(),
            'total_tasks' => Task::whereIn('project_id', $projectIds)->count(),
            'completed_tasks' => Task::whereIn('project_id', $projectIds)->where('status', 'termine')->count(),
            'overdue_tasks' => Task::whereIn('project_id', $projectIds)->where('due_date', '<', now())->where('status', '!=', 'termine')->count(),
            'my_tasks' => $user->assignedTasks()->count(),
            'my_pending_tasks' => $user->assignedTasks()->where('status', 'a_faire')->count(),
        ];
    }

    private function getEmployeStats($user): array
    {
        return [
            'my_tasks' => $user->assignedTasks()->count(),
            'pending_tasks' => $user->assignedTasks()->where('status', 'a_faire')->count(),
            'in_progress_tasks' => $user->assignedTasks()->where('status', 'en_cours')->count(),
            'completed_tasks' => $user->assignedTasks()->where('status', 'termine')->count(),
            'overdue_tasks' => $user->assignedTasks()->where('due_date', '<', now())->where('status', '!=', 'termine')->count(),
            'tasks_by_priority' => $user->assignedTasks()
                                       ->select('priority', DB::raw('count(*) as count'))
                                       ->groupBy('priority')
                                       ->pluck('count', 'priority'),
        ];
    }
}
