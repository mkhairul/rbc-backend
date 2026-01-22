<?php

namespace App\Events;

class ItemUpdated
{
    public int $itemId;
    public ?string $oldName;
    public ?string $newName;
    public ?int $oldQuantity;
    public ?int $newQuantity;
    public string $timestamp;

    public function __construct(
        int $itemId,
        ?string $oldName = null,
        ?string $newName = null,
        ?int $oldQuantity = null,
        ?int $newQuantity = null
    ) {
        $this->itemId = $itemId;
        $this->oldName = $oldName;
        $this->newName = $newName;
        $this->oldQuantity = $oldQuantity;
        $this->newQuantity = $newQuantity;
        $this->timestamp = now()->toIso8601String();
    }

    /**
     * Convert the event to an array for storage.
     * Only includes fields that changed.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'item_id' => $this->itemId,
            'timestamp' => $this->timestamp,
        ];

        if ($this->oldName !== null || $this->newName !== null) {
            $data['old_name'] = $this->oldName;
            $data['new_name'] = $this->newName;
        }

        if ($this->oldQuantity !== null || $this->newQuantity !== null) {
            $data['old_quantity'] = $this->oldQuantity;
            $data['new_quantity'] = $this->newQuantity;
        }

        return $data;
    }

    /**
     * Get the event type.
     */
    public static function eventType(): string
    {
        return 'ItemUpdated';
    }
}
