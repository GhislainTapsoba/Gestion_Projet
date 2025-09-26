<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GenericExport;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\ActivityLog;

class ExportController extends Controller
{
    public function export(Request $request)
    {
        $types = $request->input('types', []);
        $format = $request->input('format', 'csv');
        $dateRange = $request->input('dateRange', 'all');

        // Collecte des données
        $data = [];

        if (in_array('projects', $types) || in_array('all', $types)) {
            $query = Project::query();
            $data['projects'] = $this->applyDateRange($query, $dateRange)->get()->toArray();
        }

        if (in_array('tasks', $types) || in_array('all', $types)) {
            $query = Task::query();
            $data['tasks'] = $this->applyDateRange($query, $dateRange)->get()->toArray();
        }

        if (in_array('users', $types) || in_array('all', $types)) {
            $query = User::query();
            $data['users'] = $this->applyDateRange($query, $dateRange)->get()->toArray();
        }

        if (in_array('activities', $types) || in_array('all', $types)) {
            $query = ActivityLog::query();
            $data['activities'] = $this->applyDateRange($query, $dateRange)->get()->toArray();
        }

        // Retour selon le format
        switch ($format) {
            case 'json':
                return response()->json($data);

            case 'xlsx':
                return Excel::download(new GenericExport($data), 'export.xlsx');

            case 'csv':
            default:
                $response = new StreamedResponse(function () use ($data) {
                    $handle = fopen('php://output', 'w');
                    foreach ($data as $type => $rows) {
                        fputcsv($handle, [$type]); // titre
                        if (count($rows) > 0) {
                            fputcsv($handle, array_keys($rows[0])); // header
                            foreach ($rows as $row) {
                                fputcsv($handle, $row);
                            }
                        }
                        fputcsv($handle, []); // ligne vide
                    }
                    fclose($handle);
                });
                $response->headers->set('Content-Type', 'text/csv');
                $response->headers->set('Content-Disposition', 'attachment; filename="export.csv"');
                return $response;
        }
    }

    private function applyDateRange($query, $range)
    {
        switch ($range) {
            case 'last_month':
                return $query->where('created_at', '>=', now()->subMonth());
            case 'last_3_months':
                return $query->where('created_at', '>=', now()->subMonths(3));
            case 'last_6_months':
                return $query->where('created_at', '>=', now()->subMonths(6));
            case 'last_year':
                return $query->where('created_at', '>=', now()->subYear());
            case 'all':
            default:
                return $query;
        }
    }
}
