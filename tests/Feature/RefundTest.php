<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Local;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $category;
    protected $local;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 1. Criar Role e Usuário
        Role::create(['name' => 'user']);
        $this->user = User::factory()->create(['id_role' => 1]);
        
        // 2. Criar dependências obrigatórias para o Evento
        $this->category = EventCategory::create(['name' => 'Tecnologia']);
        $this->local = Local::create([
            'name' => 'Centro de Convenções IFBA',
            'street' => 'Rua do Conhecimento',
            'neighborhood' => 'Centro',
            'number_street' => '123',
            'max_people' => 500
        ]);
        
        // 3. Criar Tipo de Ingresso
        TicketType::create(['name' => 'Inteira']);
    }

    #[Test]
    public function usuario_pode_solicitar_reembolso_dentro_do_prazo()
    {
        $event = Event::factory()->create([
            'name' => 'Workshop de Laravel',
            'description' => 'Descrição do workshop de Laravel',
            'id_category' => $this->category->id,
            'id_local' => $this->local->id,
            'date' => Carbon::now()->addDays(5)->format('Y-m-d'),
            'time' => '20:00:00',
            'max_tickets_per_cpf' => 5,
            'refund_enabled' => true,
            'refund_deadline_hours' => 48
        ]);

        $batch = Batch::factory()->create(['id_event' => $event->id, 'price' => 100]);
        
        $ticket = Ticket::create([
            'id_user' => $this->user->id,
            'id_event' => $event->id,
            'id_batch' => $batch->id,
            'id_ticket_type' => 1,
            'status' => 'paid',
            'seat_number' => 'A1',
            'is_validated' => false,
            'payment_id' => 'MP-123456789'
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/refund-requests', [
                'id_ticket' => $ticket->id,
                'reason' => 'Não poderei comparecer na data.'
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('refund_requests', [
            'id_ticket' => $ticket->id, 
            'status' => 'pendente'
        ]);
    }

    #[Test]
    public function nao_permite_reembolso_fora_do_prazo()
    {
        $event = Event::factory()->create([
            'name' => 'Show de Encerramento',
            'description' => 'Descrição do show de encerramento',
            'id_category' => $this->category->id,
            'id_local' => $this->local->id,
            'date' => Carbon::now()->addDay()->format('Y-m-d'),
            'time' => '20:00:00',
            'max_tickets_per_cpf' => 5,
            'refund_deadline_hours' => 48
        ]);

        $batch = Batch::factory()->create(['id_event' => $event->id]);
        
        $ticket = Ticket::create([
            'id_user' => $this->user->id,
            'id_event' => $event->id,
            'id_batch' => $batch->id,
            'id_ticket_type' => 1,
            'status' => 'paid',
            'seat_number' => 'A1'
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/refund-requests', ['id_ticket' => $ticket->id]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'O prazo para solicitação de reembolso expirou.']);
    }

    #[Test]
    public function nao_permite_reembolso_de_ingresso_ja_utilizado()
    {
        $event = Event::factory()->create([
            'name' => 'Conferência de Sistemas',
            'description' => 'Descrição da conferência de sistemas',
            'id_category' => $this->category->id,
            'id_local' => $this->local->id,
            'date' => Carbon::now()->addDays(10)->format('Y-m-d'),
            'time' => '08:00:00',
            'max_tickets_per_cpf' => 5
        ]);

        $batch = Batch::factory()->create(['id_event' => $event->id]);
        
        $ticket = Ticket::create([
            'id_user' => $this->user->id,
            'id_event' => $event->id,
            'id_batch' => $batch->id,
            'id_ticket_type' => 1,
            'status' => 'paid',
            'seat_number' => 'A1',
            'is_validated' => true // Simula ingresso já usado
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/refund-requests', ['id_ticket' => $ticket->id]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Ingressos já validados não podem ser reembolsados.']);
    }
}