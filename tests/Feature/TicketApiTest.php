<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ticket;
use App\Models\Role;
use App\Models\Event;
use App\Models\Local;
use App\Models\EventCategory;
use App\Models\Batch;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_so_ve_o_qrcode_se_o_ticket_estiver_pago()
    {
        // 1. Criar a estrutura básica que o banco exige
        $role = Role::create(['name' => 'user']);
        $user = User::factory()->create(['id_role' => $role->id]);
        $category = EventCategory::create(['name' => 'Show']);
        
        $local = Local::create([
            'name' => 'Arena', 'street' => 'Rua 1', 'number_street' => '10', 
            'neighborhood' => 'Bairro', 'max_people' => 1000
        ]);

        $event = Event::create([
            'id_category' => $category->id,
            'id_local' => $local->id,
            'name' => 'Evento Teste',
            'description' => 'Esta é uma descrição obrigatória do evento.',
            'date' => now()->addDays(5),
            'time' => '20:00:00',
            'status' => 'active',
            'max_tickets_per_cpf' => 2
        ]);

        $batch = Batch::create([
            'id_event' => $event->id,
            'name' => 'Lote 1',
            'price' => 100,
            'quantity' => 100,
            'initial_date' => now(),
            'end_date' => now()->addDays(5)
        ]);

        $type = TicketType::create(['name' => 'Inteira']);

        // 2. Criar um ticket PAGO (Deve mostrar o ticket_code)
        $paidTicket = Ticket::create([
            'id_user' => $user->id,
            'id_event' => $event->id,
            'id_ticket_type' => $type->id,
            'id_batch' => $batch->id,
            'status' => 'paid',
            'seat_number' => 1,
            'is_validated' => false
        ]);

        // 3. Criar um ticket RESERVADO (NÃO deve mostrar o ticket_code)
        $reservedTicket = Ticket::create([
            'id_user' => $user->id,
            'id_event' => $event->id,
            'id_ticket_type' => $type->id,
            'id_batch' => $batch->id,
            'status' => 'reserved',
            'seat_number' => 2,
            'is_validated' => false
        ]);

        // 4. Executar a chamada
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/tickets');

        // 5. Assertions
        $response->assertStatus(200);
        
        // Verifica se o UUID do pago aparece
        $response->assertJsonFragment(['ticket_code' => $paidTicket->ticket_code]);
        
        // Verifica se o UUID do reservado sumiu (Regra do Resource)
        $response->assertJsonMissing(['ticket_code' => $reservedTicket->ticket_code]);
    }

    public function um_organizador_pode_validar_um_ingresso_pago()
{
    // Reutilizando a lógica de criação de cenário que você já tem...
    $role = Role::first() ?: Role::create(['name' => 'user']);
    $user = User::factory()->create(['id_role' => $role->id]);
    
    // Criando um ticket PAGO para teste
    $ticket = Ticket::create([
        'id_user' => $user->id,
        'id_event' => 1, // Ajuste para o ID do evento criado no método anterior ou crie um novo
        'id_ticket_type' => 1,
        'id_batch' => 1,
        'status' => 'paid',
        'ticket_code' => \Illuminate\Support\Str::uuid(),
        'seat_number' => 99,
        'is_validated' => false
    ]);

    // 1. Tentar validar o ingresso via POST
    $response = $this->actingAs($user, 'sanctum')
                     ->postJson('/api/tickets/validate', [
                         'ticket_code' => $ticket->ticket_code
                     ]);

    $response->assertStatus(200)
             ->assertJsonFragment(['message' => 'Entrada autorizada com sucesso!']);

    // 2. Verificar se no banco o status mudou para validado
    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'is_validated' => true
    ]);

    // 3. Tentar validar o MESMO ingresso de novo (deve falhar - Anti-fraude)
    $secondResponse = $this->postJson('/api/tickets/validate', [
        'ticket_code' => $ticket->ticket_code
    ]);

    $secondResponse->assertStatus(400)
                   ->assertJsonFragment(['message' => 'Este ingresso já foi utilizado.']);
}
}