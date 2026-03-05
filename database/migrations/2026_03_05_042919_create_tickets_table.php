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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->constrained('users')->onDelete('cascade');
            $table->foreignId('id_event')->constrained('events')->onDelete('cascade');
            $table->foreignId('id_ticket_type')->constrained('ticket_types');
            $table->foreignId('id_batch')->constrained('batches');
            $table->enum('status', ['available', 'reserved', 'paid', 'cancelled'])->default('available');
            $table->smallInteger('seat_number');
            $table->boolean('is_validated')->default(false);
            $table->uuid('ticket_code')->unique(); // QR Code
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
