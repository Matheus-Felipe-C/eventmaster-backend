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
        Schema::table('mercado_pago_checkout_sessions', function (Blueprint $table) {
            $table->timestamp('refunded_at')->nullable()->after('payment_id');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('id_mercado_pago_checkout_session')
                ->nullable()
                ->after('id_batch')
                ->constrained('mercado_pago_checkout_sessions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['id_mercado_pago_checkout_session']);
            $table->dropColumn('id_mercado_pago_checkout_session');
        });

        Schema::table('mercado_pago_checkout_sessions', function (Blueprint $table) {
            $table->dropColumn('refunded_at');
        });
    }
};
