<?php

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApiKey>
 */
class ApiKeyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Model>
     */
    protected $model = ApiKey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $prefix = config('identity-gateway.api_keys.prefix', 'igw_live_');
        $randomPart = bin2hex(random_bytes(16));
        $plainKey = $prefix.$randomPart;

        return [
            'id' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'name' => $this->faker->words(2, true),
            'key_hash' => hash('sha256', $plainKey),
            'key_prefix' => $prefix,
            'scopes' => ['user:read'],
            'last_used_at' => null,
            'expires_at' => null,
            'revoked_at' => null,
        ];
    }

    /**
     * Indicate that the API key is revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now(),
        ]);
    }

    /**
     * Indicate that the API key is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Indicate that the API key was recently used.
     */
    public function recentlyUsed(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => now(),
        ]);
    }
}
