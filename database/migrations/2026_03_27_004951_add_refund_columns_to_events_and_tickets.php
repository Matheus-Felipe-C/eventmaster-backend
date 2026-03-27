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
    // Adiciona política de reembolso ao evento 
    Schema::table('events', function (Blueprint $table) {
        $table->boolean('refund_enabled')->default(true);
        $table->integer('refund_deadline_hours')->default(48); // Horas antes do evento 
        $table->boolean('refund_requires_approval')->default(false); // Automático ou manual 
    });

    // Vincula o ticket ao pagamento do Mercado Pago 
    Schema::table('tickets', function (Blueprint $table) {
        $table->string('payment_id')->nullable(); // Necessário para estorno na API do MP 
        $table->string('id_checkout_session')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events_and_tickets', function (Blueprint $table) {
            //
        });
    }
};
