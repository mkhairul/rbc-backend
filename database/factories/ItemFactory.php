<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Item::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $products = [
            'Laptop',
            'Wireless Mouse',
            'Mechanical Keyboard',
            'USB-C Cable',
            'Monitor',
            'Desk Chair',
            'Standing Desk',
            'Headphones',
            'Webcam',
            'External Hard Drive',
            'Phone Charger',
            'HDMI Cable',
            'Printer',
            'Scanner',
            'Notebook',
            'Pen Set',
            'Desk Lamp',
            'Cable Organizer',
            'Power Strip',
            'Document Holder',
        ];

        return [
            'name' => fake()->randomElement($products),
            'quantity' => fake()->numberBetween(0, 1000),
        ];
    }
}
