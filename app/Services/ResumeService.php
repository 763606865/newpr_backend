<?php

namespace App\Services;

use App\Enums\RcResumeSourceType;
use App\Enums\UserGender;
use App\Models\Rc\Resume;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    /**
     * 上传简历附件并绑定到指定简历，供后续 AI 解析回填使用。
     */
    public function attachFile(Resume $resume, UploadedFile $file): Resume
    {
        $path = $this->storeResumeAttachment($file);
        $oldPath = $resume->getAttributes()['file_url'] ?? null;

        $resume->forceFill([
            'file_url' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_ext' => strtolower($file->extension()),
            'source_type' => RcResumeSourceType::Upload,
            'text_content' => null,
            'parsed_data' => null,
        ])->save();

        if (is_string($oldPath) && $oldPath !== '' && $oldPath !== $path) {
            try {
                Storage::disk('oss')->delete($oldPath);
            } catch (\Throwable) {
                // 旧附件清理失败不影响新附件绑定。
            }
        }

        return $resume->fresh();
    }

    private function storeResumeAttachment(UploadedFile $file): string
    {
        $path = sprintf(
            'uploads/rc/resume/%s/%s.%s',
            now()->format('Y/m/d'),
            Str::random(20),
            strtolower($file->extension())
        );

        Storage::disk('oss')->put(
            $path,
            file_get_contents($file->getRealPath()),
            ['ContentType' => $file->getMimeType()]
        );

        return $path;
    }
}
