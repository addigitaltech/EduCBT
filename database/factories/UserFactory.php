<?php

namespace Database\Factories;

use App\Domain\Identity\Enums\UserStatus;
use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\User;
use App\Support\Profile\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public const PASSWORD = 'Example-Passw0rd-1';

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'user_type' => UserType::staffFor(Profile::current()),
            'login_id' => fake()->unique()->userName(),
            'email' => null,
            'name' => fake()->name(),
            'password' => self::PASSWORD,
            'status' => UserStatus::Active,
            'must_change_password' => false,
            'failed_attempts' => 0,
        ];
    }

    public function mustChangePassword(): static
    {
        return $this->state(['must_change_password' => true]);
    }

    public function status(UserStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
