<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only. No foreign keys: history must survive any later change to the rows it describes.
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->timestamp('occurred_at', 6);
            $table->char('actor_uuid', 36)->nullable();
            $table->string('actor_label', 160)->nullable();
            $table->string('action', 120);
            $table->string('entity_type', 80);
            $table->char('entity_uuid', 36)->nullable();
            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->char('request_id', 36)->nullable();
            $table->string('profile', 16);

            $table->index(['entity_type', 'entity_uuid']);
            $table->index(['actor_uuid', 'occurred_at']);
            $table->index(['action', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
