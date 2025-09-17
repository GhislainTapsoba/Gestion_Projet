<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('order_index');
            $table->integer('estimated_duration')->comment('Durée en jours');
            $table->enum('status', ['en_attente', 'en_cours', 'termine'])->default('en_attente');
            $table->foreignId('depends_on')->nullable()->constrained('stages')->onDelete('set null');
            $table->date('started_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'order_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stages');
    }
};