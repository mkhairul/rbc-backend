<?php

namespace Database\Seeders;

use App\Events\ItemCreated;
use App\Models\Item;
use App\Services\ItemEventStore;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $eventStore = app(ItemEventStore::class);

        // Create 10 sample items with events
        $items = Item::factory(10)->create();

        // Create corresponding ItemCreated events for audit trail
        foreach ($items as $item) {
            $event = new ItemCreated(
                $item->id,
                $item->name,
                $item->quantity
            );
            $eventStore->store($event);
        }

        $this->command->info('Created 10 items with corresponding events!');
    }
}
