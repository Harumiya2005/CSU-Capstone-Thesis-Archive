<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
        public function up(): void
            {
                Schema::create('audit_logs', function (Blueprint $table) {
                    $table->id();
                    // Connects the log to the Admin who did the action
                    $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
                    // Stores the description of what they did
                    $table->string('action');
                    $table->timestamps();
                });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
