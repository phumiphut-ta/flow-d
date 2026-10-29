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
        Schema::create('registered_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_token', 64)->unique();
            $table->string('device_name', 150)->nullable();
            $table->string('last_ip_address', 45)->nullable();
            $table->text('last_user_agent')->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->string('route_name', 255)->unique();
            $table->json('steps_json')->nullable();
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('doc_number', 100)->index();
            $table->date('doc_date');
            $table->string('current_status', 20)->default('in_progress');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('document_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('route_id')->constrained('routes');
            $table->integer('round_number')->default(1);
            $table->integer('current_step_index')->default(0);
            $table->string('current_step_name', 100)->nullable();
            $table->dateTime('in_datetime');
            $table->foreignId('in_by_user_id')->constrained('users');
            $table->foreignId('in_device_id')->nullable()->constrained('registered_devices')->nullOnDelete();
            $table->string('in_ip_address', 45)->nullable();
            $table->dateTime('out_datetime')->nullable();
            $table->foreignId('out_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('out_device_id')->nullable()->constrained('registered_devices')->nullOnDelete();
            $table->string('out_ip_address', 45)->nullable();
            $table->boolean('is_returned')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('movement_step_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movement_id')->constrained('document_movements')->cascadeOnDelete();
            $table->integer('step_index');
            $table->string('step_name', 100);
            $table->string('status', 30); // completed, current, pending, rollbacked
            $table->foreignId('action_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('action_datetime');
            $table->string('ip_address', 45)->nullable();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50)->index();
            $table->text('description');
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('device_id')->nullable()->constrained('registered_devices')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('movement_step_logs');
        Schema::dropIfExists('document_movements');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('routes');
        Schema::dropIfExists('registered_devices');
    }
};
