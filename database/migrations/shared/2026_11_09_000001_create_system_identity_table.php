<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Single row (id = 1) describing this installation.
        Schema::create('system_identity', function (Blueprint $table): void {
            $table->unsignedTinyInteger('id')->primary();
            $table->char('installation_uuid', 36)->unique();
            $table->string('profile', 16);
            $table->char('school_uuid', 36)->nullable();
            $table->string('schema_version', 32);
            $table->timestamp('installed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_identity');
    }
};
