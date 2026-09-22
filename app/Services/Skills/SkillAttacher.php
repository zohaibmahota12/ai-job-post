<?php

namespace App\Services\Skills;

use App\Models\Opportunity;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SkillAttacher
{
    public function attachToUser(User $user, string $name): Skill
    {
        $skill = $this->findOrCreate($name);
        $user->skills()->syncWithoutDetaching([$skill->id]);

        return $skill;
    }

    /**
     * @param  list<string>  $names
     */
    public function attachNamesToOpportunity(Opportunity $opportunity, array $names): void
    {
        $ids = [];

        foreach ($names as $name) {
            if (! is_string($name) || trim($name) === '') {
                continue;
            }

            $ids[] = $this->findOrCreate($name)->id;
        }

        if ($ids === []) {
            return;
        }

        $opportunity->skills()->syncWithoutDetaching(array_values(array_unique($ids)));
    }

    public function findOrCreate(string $name): Skill
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        $slug = Str::slug($name);

        if ($name === '' || $slug === '') {
            throw new InvalidArgumentException('Skill name must contain letters or numbers.');
        }

        if (Str::length($name) > 80) {
            throw new InvalidArgumentException('Skill name is too long.');
        }

        return Skill::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $name],
        );
    }
}
