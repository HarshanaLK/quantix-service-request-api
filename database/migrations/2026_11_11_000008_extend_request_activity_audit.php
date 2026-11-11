<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_activities', function (Blueprint $table): void {
            // Existing history rows represent transitions.
            $table->string('action', 30)->default('transition')->after('actor_id');
            $table->json('details')->nullable()->after('comment');
        });
    }

    public function down(): void
    {
        Schema::table('request_activities', function (Blueprint $table): void {
            $table->dropColumn(['action', 'details']);
        });
    }
};
