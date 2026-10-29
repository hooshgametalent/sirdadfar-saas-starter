<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * Mobiles use the unassigned 0900 prefix so factories never produce a
     * real subscriber number.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'mobile' => fake()->unique()->numerify('0900#######'),
            'email' => null,
            'role' => UserRole::Shipper,
            'is_active' => true,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function role(UserRole $role): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => $role,
        ]);
    }

    public function shipper(): static
    {
        return $this->role(UserRole::Shipper);
    }

    public function driver(): static
    {
        return $this->role(UserRole::Driver);
    }

    public function operator(): static
    {
        return $this->role(UserRole::Operator);
    }

    public function admin(): static
    {
        return $this->role(UserRole::Admin);
    }

    public function contentEditor(): static
    {
        return $this->role(UserRole::ContentEditor);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
