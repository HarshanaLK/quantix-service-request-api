<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password');
            $t->enum('role', ['employee', 'manager', 'operations'])->default('employee');
            $t->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('department')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
