<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $number = fake()->unique()->numberBetween(1, 999999);

        return [
            'category_id' => null,
            'title' => 'مقاله نمونه '.$number,
            'slug' => 'article-'.$number,
            'excerpt' => 'خلاصه مقاله نمونه درباره حمل بار.',
            'body' => '<p>متن نمونه مقاله درباره حمل و نقل بار جاده‌ای.</p>',
            'status' => PublishStatus::Draft,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PublishStatus::Published,
            'published_at' => now()->subDay(),
        ]);
    }
}
