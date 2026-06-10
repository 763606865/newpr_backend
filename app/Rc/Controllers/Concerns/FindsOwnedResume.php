<?php

namespace App\Rc\Controllers\Concerns;

use App\Models\Rc\Resume;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait FindsOwnedResume
{
    private function findOwnedResume(int $resumeId): ?Resume
    {
        /** @var User $user */
        $user = $this->user();

        return Resume::query()
            ->where('user_id', $user->id)
            ->find($resumeId);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function findOwnedResumeItem(
        string $modelClass,
        int $resumeId,
        int $itemId,
    ): ?Model {
        /** @var User $user */
        $user = $this->user();

        $item = $modelClass::query()
            ->whereKey($itemId)
            ->where('resume_id', $resumeId)
            ->where('user_id', $user->id)
            ->first();

        return $item instanceof Model ? $item : null;
    }
}
