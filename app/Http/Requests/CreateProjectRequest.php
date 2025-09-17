<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isChefProjet();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'chef_projet_id' => 'required|exists:users,id',
            'team_members' => 'nullable|array',
            'team_members.*' => 'exists:users,id',
            'stages' => 'nullable|array',
            'stages.*.name' => 'required|string|max:255',
            'stages.*.description' => 'nullable|string|max:1000',
            'stages.*.estimated_duration' => 'required|integer|min:1|max:365',
            'stages.*.depends_on' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le titre du projet est obligatoire.',
            'description.required' => 'La description du projet est obligatoire.',
            'start_date.after_or_equal' => 'La date de début ne peut pas être antérieure à aujourd\'hui.',
            'end_date.after' => 'La date de fin doit être postérieure à la date de début.',
            'chef_projet_id.exists' => 'Le chef de projet sélectionné n\'existe pas.',
            'team_members.*.exists' => 'Un ou plusieurs membres de l\'équipe n\'existent pas.',
        ];
    }
}