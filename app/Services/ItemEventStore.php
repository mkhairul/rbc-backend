<?php

namespace App\Services;

use App\Events\ItemCreated;
use App\Events\ItemDeleted;
use App\Events\ItemUpdated;
use App\Models\ItemEvent;
use Illuminate\Support\Collection;

class ItemEventStore
{
    /**
     * Store an event in the event store.
     *
     * @param ItemCreated|ItemUpdated|ItemDeleted $event
     * @return ItemEvent
     */
    public function store($event): ItemEvent
    {
        $eventType = $event::eventType();
        $payload = $event->toArray();

        return ItemEvent::create([
            'item_id' => $event->itemId,
            'event_type' => $eventType,
            'payload' => $payload,
        ]);
    }

    /**
     * Get all events for a specific item.
     *
     * @param int $itemId
     * @return Collection<int, ItemEvent>
     */
    public function getEventsForItem(int $itemId): Collection
    {
        return ItemEvent::where('item_id', $itemId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get all events in the store.
     *
     * @return Collection<int, ItemEvent>
     */
    public function getAllEvents(): Collection
    {
        return ItemEvent::orderBy('created_at', 'asc')->get();
    }

    /**
     * Replay events to rebuild state.
     *
     * @param int|null $itemId
     * @return void
     */
    public function replayEvents(?int $itemId = null): void
    {
        $projector = app(ItemProjector::class);
        $projector->rebuildProjection($itemId);
    }
}
