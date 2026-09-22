<?php

namespace Database\Factories;

use App\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationStatusHistory>
 */
class ApplicationStatusHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'old_status' => ApplicationStatus::New,
            'new_status' => ApplicationStatus::Applied,
            'changed_by' => User::factory(),
            'note' => null,
        ];
    }
}
