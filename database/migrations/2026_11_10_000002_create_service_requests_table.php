<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $t->string('title', 150);
            $t->text('description');
            $t->string('category', 40);
            $t->string('priority', 10)->default('medium');
            $t->string('status', 20)->default('draft')->index();
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['employee_id', 'status']);
            $t->index(['assigned_to', 'status']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
