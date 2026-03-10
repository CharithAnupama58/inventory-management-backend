<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')
                  ->constrained('places')
                  ->onDelete('restrict'); // prevent deleting a place if items are assigned

            $table->string('name');
            $table->string('code')->unique();       // e.g. EQ-001 — must be unique across system
            $table->unsignedInteger('quantity')->default(0);
            $table->string('serial_number')->nullable(); // optional per document
            $table->string('image_path')->nullable();    // stored file path
            $table->text('description')->nullable();

            $table->enum('status', [
                'instore',   // available
                'borrowed',  // currently out
                'damaged',   // needs attention
                'missing',   // cannot be located
            ])->default('instore');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
