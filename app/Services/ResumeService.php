<?php

namespace App\Services;

use App\Enums\UserGender;
use App\Models\Rc\Resume;
use App\Models\User;

class ResumeService extends Service
{
    /**
     * 将指定简历设为主简历，并取消其用户下其它主简历标记。
     */
    public function promote(User $user, Resume $primaryResume): void
    {
        Resume::query()
            ->where('user_id', $user->id)
            ->whereKeyNot($primaryResume->id)
            ->where('is_primary', 1)
            ->update(['is_primary' => 0]);

        $this->syncUserProfileFromResume($user, $primaryResume);
    }

    /**
     * 用主简历信息补全用户资料中仍为空的字段，不覆盖已有值。
     */
    public function syncUserProfileFromResume(User $user, Resume $resume): void
    {
        $updates = [];

        $fullName = trim((string) $resume->full_name);
        if ($fullName !== '') {
            if (blank($user->name)) {
                $updates['name'] = $fullName;
            }

            if (blank($user->nickname)) {
                $updates['nickname'] = $fullName;
            }
        }

        $avatar = trim((string) ($resume->getAttributes()['avatar'] ?? ''));
        if ($avatar !== '' && blank($user->avatar)) {
            $updates['avatar'] = $avatar;
        }

        $resumeGender = $resume->gender;
        if (
            $this->userGenderIsEmpty($user)
            && $resumeGender instanceof UserGender
            && $resumeGender !== UserGender::Unknown
        ) {
            $updates['gender'] = $resumeGender;
        }

        if ($updates === []) {
            return;
        }

        $user->forceFill($updates)->save();
    }

    private function userGenderIsEmpty(User $user): bool
    {
        $gender = $user->gender;

        return $gender === null || $gender === UserGender::Unknown;
    }
}
