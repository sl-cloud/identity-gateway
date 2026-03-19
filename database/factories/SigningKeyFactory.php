<?php

namespace Database\Factories;

use App\Enums\KeyStatus;
use App\Models\SigningKey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use phpseclib3\Crypt\RSA;

/**
 * @extends Factory<SigningKey>
 */
class SigningKeyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Model>
     */
    protected $model = SigningKey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate a valid RSA key pair for testing
        $privateKey = RSA::createKey(2048);
        $publicKey = $privateKey->getPublicKey();

        return [
            'private_key' => $privateKey->toString('PKCS8'),
            'public_key' => $publicKey->toString('PKCS8'),
            'algorithm' => 'RS256',
            'status' => KeyStatus::ACTIVE->value,
            'activated_at' => now(),
            'retired_at' => null,
            'expires_at' => now()->addDays(90),
        ];
    }

    /**
     * Indicate that the key is retired.
     */
    public function retired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KeyStatus::RETIRED->value,
            'retired_at' => now(),
        ]);
    }

    /**
     * Indicate that the key is revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KeyStatus::REVOKED->value,
        ]);
    }

    /**
     * Indicate that the key is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
