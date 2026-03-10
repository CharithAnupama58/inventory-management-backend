<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit log tracks EVERY change in the system.
     * The document says: "This is critical for evaluation."
     *
     * Stores:
     *  - who did it (user_id)
     *  - what action (e.g. ITEM_BORROWED, USER_CREATED)
     *  - which record (entity_type + entity_id)
     *  - before and after values (JSON)
     *  - exact timestamp
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Who performed the action — nullable for system-generated events
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null'); // keep log even if user is deleted

            // Action type — makes filtering easy
            $table->string('action'); // e.g. ITEM_CREATED, ITEM_BORROWED, USER_CREATED

            // Which model/table was affected
            $table->string('entity_type');  // e.g. Item, Borrow, User, Cupboard, Place
            $table->unsignedBigInteger('entity_id');   // the record's id
            $table->string('entity_name');  // human-readable label e.g. "Soldering Iron"

            // Before/after snapshot — JSON for flexibility
            // previous_value is null for CREATE actions
            $table->json('previous_value')->nullable();
            // new_value is null for DELETE actions
            $table->json('new_value')->nullable();

            // Exact time — no updated_at needed for audit logs
            $table->timestamp('created_at')->useCurrent();

            // Indexes for fast filtering
            $table->index('action');
            $table->index('entity_type');
            $table->index(['entity_type', 'entity_id']);
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
