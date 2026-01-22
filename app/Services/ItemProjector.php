<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemEvent;
use Illuminate\Support\Facades\DB;

class ItemProjector
{
    /**
     * Project an ItemCreated event.
     *
     * @param array<string, mixed> $payload
     * @return Item
     */
    public function projectItemCreated(array $payload): Item
    {
        return Item::create([
            'id' => $payload['item_id'],
            'name' => $payload['name'],
            'quantity' => $payload['quantity'],
        ]);
    }

    /**
     * Project an ItemUpdated event.
     *
     * @param array<string, mixed> $payload
     * @return Item|null
     */
    public function projectItemUpdated(array $payload): ?Item
    {
        $item = Item::find($payload['item_id']);
        
        if (!$item) {
            return null;
        }

        $updates = [];
        if (isset($payload['new_name'])) {
            $updates['name'] = $payload['new_name'];
        }
        if (isset($payload['new_quantity'])) {
            $updates['quantity'] = $payload['new_quantity'];
        }

        if (!empty($updates)) {
            $item->update($updates);
        }

        return $item;
    }

    /**
     * Project an ItemDeleted event.
     *
     * @param array<string, mixed> $payload
     * @return bool
     */
    public function projectItemDeleted(array $payload): bool
    {
        $item = Item::find($payload['item_id']);
        
        if (!$item) {
            return false;
        }

        return (bool) $item->delete();
    }

    /**
     * Rebuild the entire projection or a single item from events.
     *
     * @param int|null $itemId
     * @return void
     */
    public function rebuildProjection(?int $itemId = null): void
    {
        DB::transaction(function () use ($itemId) {
            // Clear existing projection
            if ($itemId) {
                Item::where('id', $itemId)->delete();
                $events = ItemEvent::where('item_id', $itemId)
                    ->orderBy('created_at', 'asc')
                    ->get();
            } else {
                Item::truncate();
                $events = ItemEvent::orderBy('created_at', 'asc')->get();
            }

            // Replay all events
            foreach ($events as $event) {
                $this->projectEvent($event);
            }
        });
    }

    /**
     * Project a single event based on its type.
     *
     * @param ItemEvent $event
     * @return void
     */
    protected function projectEvent(ItemEvent $event): void
    {
        match ($event->event_type) {
            'ItemCreated' => $this->projectItemCreated($event->payload),
            'ItemUpdated' => $this->projectItemUpdated($event->payload),
            'ItemDeleted' => $this->projectItemDeleted($event->payload),
            default => null,
        };
    }
}
