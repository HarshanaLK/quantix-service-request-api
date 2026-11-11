<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('request_activities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $t->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
            $t->string('from_status', 20)->nullable();
            $t->string('to_status', 20);
            $t->string('comment', 2000)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['service_request_id', 'created_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('request_activities');
    }
};
