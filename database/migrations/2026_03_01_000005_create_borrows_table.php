<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrows', function (Blueprint $table) {
            $table->id();

            $table->foreignId('item_id')
                  ->constrained('items')
                  ->onDelete('restrict'); // never delete item if borrows exist

            // Who borrowed it — not a user account, could be any third party
            $table->string('borrower_name');
            $table->string('contact');          // phone or email

            $table->unsignedInteger('quantity'); // how many units borrowed

            $table->date('borrow_date');
            $table->date('expected_return_date');
            $table->date('actual_return_date')->nullable(); // null until returned

            // Condition on return
            $table->enum('return_condition', [
                'good',
                'fair',
                'damaged',
            ])->nullable(); // null until returned

            $table->text('notes')->nullable();

            $table->enum('status', [
                'borrowed',  // currently out
                'returned',  // back in stock
            ])->default('borrowed');

            // Who processed this borrow (logged-in staff/admin)
            $table->foreignId('processed_by')
                  ->constrained('users')
                  ->onDelete('restrict');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrows');
    }
};
