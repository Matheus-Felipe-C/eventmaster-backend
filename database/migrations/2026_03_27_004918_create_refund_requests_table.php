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
    Schema::create('refund_requests', function (Blueprint $table) {
        $table->id();
        $table->foreignId('id_user')->constrained('users');
        $table->foreignId('id_ticket')->constrained('tickets');
        // Status conforme a definição de feito 
        $table->enum('status', ['pendente', 'aprovado', 'recusado'])->default('pendente');
        $table->text('reason')->nullable(); // Motivo do usuário 
        $table->text('organizer_note')->nullable(); // Nota do organizador 
        $table->string('mercado_pago_refund_id')->nullable(); // ID retornado pela API 
        $table->decimal('amount', 10, 2); // Valor a ser reembolsado 
        $table->timestamp('processed_at')->nullable(); // Auditoria de processamento 
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};
