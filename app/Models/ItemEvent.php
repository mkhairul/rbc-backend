<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemEvent extends Model
{
    /**
     * Indicates if the model should use updated_at timestamp.
     *
     * @var bool
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'item_id',
        'event_type',
        'payload',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the item that owns this event.
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
