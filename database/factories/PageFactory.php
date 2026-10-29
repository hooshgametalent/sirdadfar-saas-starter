<?php

namespace Database\Factories;

use App\Enums\PageType;
use App\Enums\PublishStatus;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = fake()->unique()->numberBetween(1, 999999);

        return [
            'title' => 'صفحه نمونه '.$number,
            'slug' => 'page-'.$number,
            'type' => PageType::StaticPage,
            'body' => '<p>متن نمونه صفحه.</p>',
            'status' => PublishStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PublishStatus::Published,
        ]);
    }
}
