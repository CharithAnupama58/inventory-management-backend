<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cupboards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();   // e.g. CUP-A
            $table->string('description')->nullable();
            $table->string('location')->nullable();  // e.g. Lab Room 101
            $table->string('color', 10)->default('#6366f1');  // hex color for UI
            $table->string('bg_color', 10)->default('#ede9fe'); // background hex
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cupboards');
    }
};
