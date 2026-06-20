<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terminal_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by');

            // Command info
            $table->text('command');                       // The executed command
            $table->string('working_directory')->nullable(); // Working directory when command was executed
            $table->string('title')->nullable();           // Optional user-defined title

            // Execution details
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->integer('exit_code')->nullable();      // Command exit code
            $table->float('execution_time')->nullable();   // Execution time in seconds
            $table->timestamp('started_at')->nullable();   // When command execution started
            $table->timestamp('completed_at')->nullable(); // When command execution finished

            // Output
            $table->longText('output')->nullable();        // Command stdout output
            $table->longText('error_output')->nullable();  // Command stderr output
            $table->text('environment')->nullable();       // JSON of environment variables used

            // Metadata
            $table->string('session_id')->nullable();      // Browser session ID for real-time updates
            $table->boolean('is_favorite')->default(false); // User can mark commands as favorites
            $table->text('notes')->nullable();             // User notes about the command

            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['created_by', 'created_at']);
            $table->index('status');
            $table->index('session_id');
            $table->index('is_favorite');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terminal_histories');
    }
};