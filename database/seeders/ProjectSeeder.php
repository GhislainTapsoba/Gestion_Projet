<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Stage;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $chefProjet = User::where('role', 'chef_projet')->first();
        $employes = User::where('role', 'employe')->get();

        // Projet exemple 1
        $projet1 = Project::create([
            'title' => 'Développement Application Web',
            'description' => 'Création d\'une application web complète avec interface utilisateur moderne',
            'start_date' => now(),
            'end_date' => now()->addMonths(3),
            'status' => 'en_cours',
            'chef_projet_id' => $chefProjet->id,
            'created_by' => $admin->id,
            'team_members' => $employes->pluck('id')->toArray(),
        ]);

        // Étapes du projet 1
        $etapes1 = [
            ['name' => 'Analyse et spécifications', 'duration' => 14, 'order' => 1],
            ['name' => 'Conception UX/UI', 'duration' => 21, 'order' => 2],
            ['name' => 'Développement Backend', 'duration' => 35, 'order' => 3],
            ['name' => 'Développement Frontend', 'duration' => 28, 'order' => 4],
            ['name' => 'Tests et validation', 'duration' => 14, 'order' => 5],
            ['name' => 'Mise en production', 'duration' => 7, 'order' => 6],
        ];

        $stageIds = [];
        foreach ($etapes1 as $etapeData) {
            $stage = Stage::create([
                'project_id' => $projet1->id,
                'name' => $etapeData['name'],
                'description' => 'Description de l\'étape ' . $etapeData['name'],
                'order_index' => $etapeData['order'],
                'estimated_duration' => $etapeData['duration'],
                'status' => $etapeData['order'] === 1 ? 'en_cours' : 'en_attente',
                'depends_on' => $etapeData['order'] > 1 ? $stageIds[$etapeData['order'] - 2] : null,
                'started_at' => $etapeData['order'] === 1 ? now() : null,
            ]);
            $stageIds[] = $stage->id;
        }

        // Tâches pour la première étape
        $premiereEtape = Stage::where('project_id', $projet1->id)->where('order_index', 1)->first();
        
        $taches1 = [
            [
                'title' => 'Recueil des besoins fonctionnels',
                'description' => 'Analyser et documenter les besoins métier',
                'priority' => 'high',
                'assigned_to' => $employes->first()->id,
            ],
            [
                'title' => 'Rédaction du cahier des charges',
                'description' => 'Formaliser les spécifications techniques',
                'priority' => 'high',
                'assigned_to' => $employes->skip(1)->first()->id,
            ],
            [
                'title' => 'Analyse de l\'existant',
                'description' => 'Étudier les solutions existantes et la concurrence',
                'priority' => 'medium',
                'assigned_to' => $employes->last()->id,
            ],
        ];

        foreach ($taches1 as $tacheData) {
            Task::create([
                'stage_id' => $premiereEtape->id,
                'project_id' => $projet1->id,
                'title' => $tacheData['title'],
                'description' => $tacheData['description'],
                'assigned_to' => $tacheData['assigned_to'],
                'priority' => $tacheData['priority'],
                'status' => 'a_faire',
                'due_date' => now()->addDays(7),
                'created_by' => $chefProjet->id,
            ]);
        }

        // Projet exemple 2
        $projet2 = Project::create([
            'title' => 'Migration Base de Données',
            'description' => 'Migration de l\'ancienne base de données vers PostgreSQL',
            'start_date' => now()->addWeek(),
            'end_date' => now()->addMonths(2),
            'status' => 'planifie',
            'chef_projet_id' => $chefProjet->id,
            'created_by' => $admin->id,
            'team_members' => [$employes->first()->id, $employes->last()->id],
        ]);

        // Étapes du projet 2
        $etapes2 = [
            ['name' => 'Audit de l\'existant', 'duration' => 10],
            ['name' => 'Planification migration', 'duration' => 7],
            ['name' => 'Migration des données', 'duration' => 21],
            ['name' => 'Tests et validation', 'duration' => 14],
        ];

        foreach ($etapes2 as $index => $etapeData) {
            Stage::create([
                'project_id' => $projet2->id,
                'name' => $etapeData['name'],
                'description' => 'Description de l\'étape ' . $etapeData['name'],
                'order_index' => $index + 1,
                'estimated_duration' => $etapeData['duration'],
                'status' => 'en_attente',
            ]);
        }
    }
}
