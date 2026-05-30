<?php

namespace App\Services;

use App\Models\Freelancer;
use App\Models\User;

class FreelancerService
{
    public function create(array $data, User $creator): Freelancer
    {
        return Freelancer::query()->create([
            ...$data,
            'created_by_user_id' => $creator->user_id,
            'is_locked' => true,
        ]);
    }

    public function update(Freelancer $freelancer, array $data, User $editor): Freelancer
    {
        if ($freelancer->is_locked && ! $editor->isAdmin()) {
            abort(403, __('crm.freelancers.locked'));
        }

        $freelancer->update($data);

        return $freelancer->fresh();
    }
}
