<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nouvelle tâche assignée</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4f46e5; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .task-details { background-color: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .priority-high { border-left: 4px solid #ef4444; }
        .priority-medium { border-left: 4px solid #f59e0b; }
        .priority-low { border-left: 4px solid #10b981; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #4f46e5; color: white; text-decoration: none; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Nouvelle tâche assignée</h1>
        </div>
        
        <div class="content">
            <p>Bonjour,</p>
            
            <p>Une nouvelle tâche vous a été assignée dans le projet <strong>{{ $task->project->title }}</strong>.</p>
            
            <div class="task-details priority-{{ $task->priority }}">
                <h3>{{ $task->title }}</h3>
                <p><strong>Projet :</strong> {{ $task->project->title }}</p>
                <p><strong>Étape :</strong> {{ $task->stage->name }}</p>
                <p><strong>Priorité :</strong> {{ ucfirst($task->priority) }}</p>
                @if($task->due_date)
                <p><strong>Échéance :</strong> {{ $task->due_date->format('d/m/Y à H:i') }}</p>
                @endif
                @if($task->description)
                <p><strong>Description :</strong></p>
                <p>{{ $task->description }}</p>
                @endif
            </div>
            
            <p>
                <a href="{{ config('app.frontend_url') }}/tasks/{{ $task->id }}" class="btn">
                    Voir la tâche
                </a>
            </p>
            
            <p>Bonne journée !</p>
        </div>
        
        <div class="footer">
            <p>Cet email a été envoyé automatiquement par le système de gestion de projets.</p>
        </div>
    </div>
</body>
</html>
