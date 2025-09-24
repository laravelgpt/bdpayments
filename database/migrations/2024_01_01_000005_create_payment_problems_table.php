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
        Schema::create('payment_problems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_history_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('problem_type');
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->string('title');
            $table->text('description');
            $table->enum('status', [
                'open',
                'in_progress',
                'pending_customer',
                'pending_gateway',
                'resolved',
                'closed',
                'duplicate'
            ])->default('open');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('reported_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->json('attachments')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'status']);
            $table->index(['problem_type', 'status']);
            $table->index(['severity', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index(['reported_by']);
            $table->index(['resolved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_problems');
    }
};
