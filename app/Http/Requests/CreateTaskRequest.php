<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Vérifier que l'utilisateur peut créer des tâches pour ce projet
        $project = \App\Models\Project::find($this->project_id);
        return $project && $this->user()->canManageProject($project);
    }

    public function rules(): array
    {
        return [
            'stage_id' => 'required|exists:stages,id',
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'in:low,medium,high',
            'due_date' => 'nullable|date|after:now',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le titre de la tâche est obligatoire.',
            'stage_id.exists' => 'L\'étape sélectionnée n\'existe pas.',
            'project_id.exists' => 'Le projet sélectionné n\'existe pas.',
            'assigned_to.exists' => 'L\'utilisateur assigné n\'existe pas.',
            'due_date.after' => 'La date d\'échéance doit être dans le futur.',
        ];
    }
}
