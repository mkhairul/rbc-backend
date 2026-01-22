<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemEvent;
use App\Services\ItemProjector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test retrieving empty list of items.
     */
    public function test_can_get_empty_items_list(): void
    {
        $response = $this->getJson('/api/items');

        $response->assertStatus(200)
            ->assertJson([]);
    }

    /**
     * Test retrieving list of items.
     */
    public function test_can_get_items_list(): void
    {
        Item::factory(3)->create();

        $response = $this->getJson('/api/items');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    /**
     * Test items are ordered by creation date (newest first).
     */
    public function test_items_are_ordered_by_created_at_desc(): void
    {
        $item1 = Item::factory()->create(['created_at' => now()->subHours(2)]);
        $item2 = Item::factory()->create(['created_at' => now()->subHour()]);
        $item3 = Item::factory()->create(['created_at' => now()]);

        $response = $this->getJson('/api/items');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertEquals($item3->id, $data[0]['id']);
        $this->assertEquals($item2->id, $data[1]['id']);
        $this->assertEquals($item1->id, $data[2]['id']);
    }

    /**
     * Test creating an item with valid data.
     */
    public function test_can_create_item_with_valid_data(): void
    {
        $data = [
            'name' => 'Laptop',
            'quantity' => 10,
        ];

        $response = $this->postJson('/api/items', $data);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'name', 'quantity', 'created_at', 'updated_at'])
            ->assertJson([
                'name' => 'Laptop',
                'quantity' => 10,
            ]);

        $this->assertDatabaseHas('items', $data);
    }

    /**
     * Test ItemCreated event is stored when creating item.
     */
    public function test_item_created_event_is_stored(): void
    {
        $data = [
            'name' => 'Mouse',
            'quantity' => 25,
        ];

        $response = $this->postJson('/api/items', $data);
        $response->assertStatus(201);

        $item = Item::where('name', 'Mouse')->first();
        
        $this->assertDatabaseHas('item_events', [
            'item_id' => $item->id,
            'event_type' => 'ItemCreated',
        ]);

        $event = ItemEvent::where('item_id', $item->id)->first();
        $this->assertEquals('Mouse', $event->payload['name']);
        $this->assertEquals(25, $event->payload['quantity']);
    }

    /**
     * Test validation: missing name.
     */
    public function test_cannot_create_item_without_name(): void
    {
        $data = [
            'quantity' => 10,
        ];

        $response = $this->postJson('/api/items', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test validation: missing quantity.
     */
    public function test_cannot_create_item_without_quantity(): void
    {
        $data = [
            'name' => 'Keyboard',
        ];

        $response = $this->postJson('/api/items', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    /**
     * Test validation: negative quantity.
     */
    public function test_cannot_create_item_with_negative_quantity(): void
    {
        $data = [
            'name' => 'Monitor',
            'quantity' => -5,
        ];

        $response = $this->postJson('/api/items', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    /**
     * Test validation: invalid data types.
     */
    public function test_cannot_create_item_with_invalid_types(): void
    {
        $data = [
            'name' => 'Test',
            'quantity' => 'invalid',
        ];

        $response = $this->postJson('/api/items', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    /**
     * Test retrieving a single item.
     */
    public function test_can_get_single_item(): void
    {
        $item = Item::factory()->create([
            'name' => 'Desk',
            'quantity' => 5,
        ]);

        $response = $this->getJson("/api/items/{$item->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $item->id,
                'name' => 'Desk',
                'quantity' => 5,
            ]);
    }

    /**
     * Test retrieving non-existent item returns 404.
     */
    public function test_get_nonexistent_item_returns_404(): void
    {
        $response = $this->getJson('/api/items/99999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Item not found',
            ]);
    }

    /**
     * Test updating an item with valid data.
     */
    public function test_can_update_item_with_valid_data(): void
    {
        $item = Item::factory()->create([
            'name' => 'Chair',
            'quantity' => 10,
        ]);

        $data = [
            'name' => 'Office Chair',
            'quantity' => 15,
        ];

        $response = $this->putJson("/api/items/{$item->id}", $data);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $item->id,
                'name' => 'Office Chair',
                'quantity' => 15,
            ]);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'Office Chair',
            'quantity' => 15,
        ]);
    }

    /**
     * Test ItemUpdated event is stored when updating item.
     */
    public function test_item_updated_event_is_stored(): void
    {
        $item = Item::factory()->create([
            'name' => 'Lamp',
            'quantity' => 20,
        ]);

        $data = [
            'name' => 'LED Lamp',
            'quantity' => 25,
        ];

        $response = $this->putJson("/api/items/{$item->id}", $data);
        $response->assertStatus(200);

        $this->assertDatabaseHas('item_events', [
            'item_id' => $item->id,
            'event_type' => 'ItemUpdated',
        ]);

        $event = ItemEvent::where(['item_id' => $item->id, 'event_type' => 'ItemUpdated'])->first();
        $this->assertEquals('Lamp', $event->payload['old_name']);
        $this->assertEquals('LED Lamp', $event->payload['new_name']);
        $this->assertEquals(20, $event->payload['old_quantity']);
        $this->assertEquals(25, $event->payload['new_quantity']);
    }

    /**
     * Test updating non-existent item returns 404.
     */
    public function test_update_nonexistent_item_returns_404(): void
    {
        $data = [
            'name' => 'Test',
            'quantity' => 10,
        ];

        $response = $this->putJson('/api/items/99999', $data);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Item not found',
            ]);
    }

    /**
     * Test update validation errors return 422.
     */
    public function test_update_with_invalid_data_returns_422(): void
    {
        $item = Item::factory()->create();

        $data = [
            'name' => '',
            'quantity' => -10,
        ];

        $response = $this->putJson("/api/items/{$item->id}", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'quantity']);
    }

    /**
     * Test deleting an item.
     */
    public function test_can_delete_item(): void
    {
        $item = Item::factory()->create();

        $response = $this->deleteJson("/api/items/{$item->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }

    /**
     * Test ItemDeleted event is stored when deleting item.
     */
    public function test_item_deleted_event_is_stored(): void
    {
        $item = Item::factory()->create([
            'name' => 'Printer',
            'quantity' => 3,
        ]);

        $itemId = $item->id;

        $response = $this->deleteJson("/api/items/{$item->id}");
        $response->assertStatus(204);

        $this->assertDatabaseHas('item_events', [
            'item_id' => $itemId,
            'event_type' => 'ItemDeleted',
        ]);

        $event = ItemEvent::where(['item_id' => $itemId, 'event_type' => 'ItemDeleted'])->first();
        $this->assertEquals('Printer', $event->payload['name']);
        $this->assertEquals(3, $event->payload['quantity']);
    }

    /**
     * Test deleting non-existent item returns 404.
     */
    public function test_delete_nonexistent_item_returns_404(): void
    {
        $response = $this->deleteJson('/api/items/99999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Item not found',
            ]);
    }

    /**
     * Test retrieving event history for an item.
     */
    public function test_can_get_item_event_history(): void
    {
        $item = Item::factory()->create(['name' => 'Scanner', 'quantity' => 5]);

        // Create some events manually
        ItemEvent::create([
            'item_id' => $item->id,
            'event_type' => 'ItemCreated',
            'payload' => ['item_id' => $item->id, 'name' => 'Scanner', 'quantity' => 5],
        ]);

        ItemEvent::create([
            'item_id' => $item->id,
            'event_type' => 'ItemUpdated',
            'payload' => ['item_id' => $item->id, 'old_quantity' => 5, 'new_quantity' => 10],
        ]);

        $response = $this->getJson("/api/items/{$item->id}/events");

        $response->assertStatus(200)
            ->assertJsonCount(2);

        $events = $response->json();
        $this->assertEquals('ItemCreated', $events[0]['event_type']);
        $this->assertEquals('ItemUpdated', $events[1]['event_type']);
    }

    /**
     * Test event history for non-existent item returns 404.
     */
    public function test_get_events_for_nonexistent_item_returns_404(): void
    {
        $response = $this->getJson('/api/items/99999/events');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Item not found',
            ]);
    }

    /**
     * Test events are in chronological order.
     */
    public function test_events_are_in_chronological_order(): void
    {
        $item = Item::factory()->create();

        $event1 = ItemEvent::create([
            'item_id' => $item->id,
            'event_type' => 'ItemCreated',
            'payload' => ['item_id' => $item->id],
        ]);

        // Ensure timestamps are different
        sleep(1);

        $event2 = ItemEvent::create([
            'item_id' => $item->id,
            'event_type' => 'ItemUpdated',
            'payload' => ['item_id' => $item->id],
        ]);

        $response = $this->getJson("/api/items/{$item->id}/events");

        $events = $response->json();
        
        // First event should be older than second event
        $this->assertTrue(
            strtotime($events[0]['created_at']) < strtotime($events[1]['created_at']),
            'Events should be in chronological order'
        );
    }

    /**
     * Test event replay can rebuild state correctly.
     */
    public function test_event_replay_rebuilds_state_correctly(): void
    {
        // Create events without items (simulating event store only)
        ItemEvent::create([
            'item_id' => 1,
            'event_type' => 'ItemCreated',
            'payload' => ['item_id' => 1, 'name' => 'Test Item', 'quantity' => 100],
        ]);

        ItemEvent::create([
            'item_id' => 1,
            'event_type' => 'ItemUpdated',
            'payload' => ['item_id' => 1, 'new_quantity' => 150],
        ]);

        // Rebuild projection
        $projector = app(ItemProjector::class);
        $projector->rebuildProjection();

        // Check if item was recreated correctly
        $item = Item::find(1);
        $this->assertNotNull($item);
        $this->assertEquals('Test Item', $item->name);
        $this->assertEquals(150, $item->quantity);
    }

    /**
     * Test multiple operations create correct event sequence.
     */
    public function test_multiple_operations_create_correct_event_sequence(): void
    {
        // Create
        $response = $this->postJson('/api/items', ['name' => 'Widget', 'quantity' => 50]);
        $item = Item::where('name', 'Widget')->first();

        // Update
        $this->putJson("/api/items/{$item->id}", ['name' => 'Super Widget', 'quantity' => 75]);

        // Delete
        $this->deleteJson("/api/items/{$item->id}");

        // Check event sequence
        $events = ItemEvent::where('item_id', $item->id)->orderBy('created_at')->get();
        
        $this->assertCount(3, $events);
        $this->assertEquals('ItemCreated', $events[0]->event_type);
        $this->assertEquals('ItemUpdated', $events[1]->event_type);
        $this->assertEquals('ItemDeleted', $events[2]->event_type);
    }

    /**
     * Test event store is append-only (immutable).
     */
    public function test_event_store_is_immutable(): void
    {
        $item = Item::factory()->create();
        
        $event = ItemEvent::create([
            'item_id' => $item->id,
            'event_type' => 'ItemCreated',
            'payload' => ['item_id' => $item->id, 'name' => 'Original', 'quantity' => 10],
        ]);

        // Verify event has created_at (for tracking)
        $this->assertNotNull($event->created_at);
        
        // Verify event model doesn't use updated_at (immutable)
        $this->assertNull($event::UPDATED_AT);
        
        // Verify fresh event doesn't have updated_at
        $freshEvent = ItemEvent::find($event->id);
        $this->assertNull($freshEvent->updated_at);
    }
}
