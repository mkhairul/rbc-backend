<?php

namespace App\Http\Controllers;

use App\Events\ItemCreated;
use App\Events\ItemDeleted;
use App\Events\ItemUpdated;
use App\Models\Item;
use App\Services\ItemEventStore;
use App\Services\ItemProjector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ItemController extends Controller
{
    public function __construct(
        protected ItemEventStore $eventStore,
        protected ItemProjector $projector
    ) {}

    /**
     * Display a listing of items.
     */
    public function index(): JsonResponse
    {
        $items = Item::orderBy('created_at', 'desc')->get();
        
        return response()->json($items, 200);
    }

    /**
     * Store a newly created item.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(Item::validationRules());

        // Create the item first to get an ID
        $item = Item::create($validated);

        // Dispatch ItemCreated event
        $event = new ItemCreated(
            $item->id,
            $item->name,
            $item->quantity
        );
        $this->eventStore->store($event);

        return response()->json($item, 201);
    }

    /**
     * Display the specified item.
     */
    public function show(string $id): JsonResponse
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json([
                'message' => 'Item not found'
            ], 404);
        }

        return response()->json($item, 200);
    }

    /**
     * Update the specified item.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json([
                'message' => 'Item not found'
            ], 404);
        }

        $validated = $request->validate(Item::validationRules());

        // Capture old values for event
        $oldName = $item->name;
        $oldQuantity = $item->quantity;

        // Update the item
        $item->update($validated);

        // Dispatch ItemUpdated event with changes
        $event = new ItemUpdated(
            $item->id,
            $oldName !== $validated['name'] ? $oldName : null,
            $oldName !== $validated['name'] ? $validated['name'] : null,
            $oldQuantity !== $validated['quantity'] ? $oldQuantity : null,
            $oldQuantity !== $validated['quantity'] ? $validated['quantity'] : null
        );
        $this->eventStore->store($event);

        return response()->json($item, 200);
    }

    /**
     * Remove the specified item.
     */
    public function destroy(string $id): JsonResponse|Response
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json([
                'message' => 'Item not found'
            ], 404);
        }

        // Dispatch ItemDeleted event before deletion
        $event = new ItemDeleted(
            $item->id,
            $item->name,
            $item->quantity
        );
        $this->eventStore->store($event);

        // Delete the item from read model
        $item->delete();

        return response()->noContent();
    }

    /**
     * Get event history for a specific item.
     */
    public function events(string $id): JsonResponse
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json([
                'message' => 'Item not found'
            ], 404);
        }

        $events = $this->eventStore->getEventsForItem((int) $id);

        return response()->json($events, 200);
    }
}
