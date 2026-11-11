<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('request_comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('body', 2000);
            $t->timestamps();
            $t->index(['service_request_id', 'created_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('request_comments');
    }
};
