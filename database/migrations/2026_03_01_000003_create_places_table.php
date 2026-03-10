<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cupboard_id')
                  ->constrained('cupboards')
                  ->onDelete('restrict');  // prevent deleting cupboard if places exist
            $table->string('name');        // e.g. Shelf 1, Drawer A
            $table->unsignedInteger('capacity')->default(10); // max items
            $table->timestamps();

            // A cupboard cannot have two places with the same name
            $table->unique(['cupboard_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};
