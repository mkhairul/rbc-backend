<?php

namespace App\Events;

class ItemDeleted
{
    public int $itemId;
    public string $name;
    public int $quantity;
    public string $timestamp;

    public function __construct(int $itemId, string $name, int $quantity)
    {
        $this->itemId = $itemId;
        $this->name = $name;
        $this->quantity = $quantity;
        $this->timestamp = now()->toIso8601String();
    }

    /**
     * Convert the event to an array for storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'item_id' => $this->itemId,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'timestamp' => $this->timestamp,
        ];
    }

    /**
     * Get the event type.
     */
    public static function eventType(): string
    {
        return 'ItemDeleted';
    }
}
