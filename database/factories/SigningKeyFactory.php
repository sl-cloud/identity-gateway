<?php

namespace Database\Factories;

use App\Enums\KeyStatus;
use App\Models\SigningKey;
use Illuminate\Database\Eloquent\Factories\Factory;
use phpseclib3\Crypt\RSA;

class SigningKeyFactory extends Factory
{
    protected $model = SigningKey::class;

    public function definition(): array
    {
        // Generate RSA key pair
        $privateKey = RSA::createKey(2048);
        $publicKey = $privateKey->getPublicKey();

        return [
            'id' => $this->faker->uuid(),
            'private_key' => $privateKey->toString('PKCS8'),
            'public_key' => $publicKey->toString('PKCS8'),
            'algorithm' => 'RS256',
            'status' => KeyStatus::ACTIVE->value,
            'activated_at' => now(),
            'retired_at' => null,
            'expires_at' => now()->addDays(90),
        ];
    }

    public function retired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KeyStatus::RETIRED->value,
            'retired_at' => now()->subDays(7),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => KeyStatus::REVOKED->value,
        ]);
    }
}
