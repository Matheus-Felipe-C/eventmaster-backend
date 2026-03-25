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
        Schema::create('mercado_pago_checkout_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_user')->constrained('users')->cascadeOnDelete();
            $table->string('external_reference')->unique();
            $table->string('preference_id')->nullable()->unique();
            $table->json('cart_snapshot');
            $table->decimal('total_amount', 12, 2);
            $table->string('currency_id', 3)->default('BRL');
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mercado_pago_checkout_sessions');
    }
};
