<?php

namespace App\Rc\Controllers;

use App\Enums\RcResumeSourceType;
use App\Enums\RcResumeStatus;
use App\Models\Rc\Resume;
use App\Models\User;
use App\Rc\Requests\ResumeStoreRequest;
use App\Rc\Requests\ResumeUpdateRequest;
use App\Resources\Rc\RcResumeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResumeController extends Controller
{
    /**
     * 简历列表
     *
     * GET /rc/resumes
     *
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        $resumes = $user->resumes()->orderByDesc('is_primary')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($this->getPerPage($request));

        return $this->success($resumes);
    }

    /**
     * 简历详情
     *
     * GET /rc/resumes/{id}
     *
     * @throws \Exception
     */
    public function show(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        $resume = Resume::query()
            ->where('user_id', $user->id)
            ->find($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success((new RcResumeResource($resume))->resolve($request));
    }

    /**
     * 新增简历
     *
     * POST /rc/resumes
     *
     * @throws \Exception
     */
    public function store(ResumeStoreRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        $payload = $request->validated();

        $resume = DB::transaction(function () use ($payload, $user): Resume {
            $isPrimary = (int) ($payload['is_primary'] ?? 0) === 1;
            $sourceType = isset($payload['source_type'])
                ? RcResumeSourceType::from((int) $payload['source_type'])
                : RcResumeSourceType::Manual;
            $status = isset($payload['status'])
                ? RcResumeStatus::from((int) $payload['status'])
                : RcResumeStatus::Normal;
            $hasPrimaryResume = Resume::query()
                ->where('user_id', $user->id)
                ->where('is_primary', 1)
                ->exists();

            $resume = new Resume;
            $resume->fill($payload);
            $resume->user_id = $user->id;
            $resume->resume_no = $this->generateResumeNo($user->id);
            $resume->source_type = $sourceType;
            $resume->status = $status;
            $resume->is_primary = ($isPrimary || ! $hasPrimaryResume) ? 1 : 0;
            $resume->save();

            if ($resume->is_primary === 1) {
                Resume::query()
                    ->where('user_id', $user->id)
                    ->whereKeyNot($resume->id)
                    ->where('is_primary', 1)
                    ->update(['is_primary' => 0]);
            }

            return $resume;
        });

        return $this->success((new RcResumeResource($resume->fresh()))->resolve($request));
    }

    /**
     * 编辑简历
     *
     * PUT /rc/resumes/{id}
     *
     * @throws \Exception
     */
    public function update(ResumeUpdateRequest $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->user();

        $resume = Resume::query()
            ->where('user_id', $user->id)
            ->find($id);

        if (! $resume instanceof Resume) {
            return $this->error('简历不存在。', Response::HTTP_NOT_FOUND);
        }

        $payload = $request->validated();

        DB::transaction(function () use ($payload, $resume, $user): void {
            $resume->fill($payload);

            if (array_key_exists('is_primary', $payload)) {
                $resume->is_primary = (int) $payload['is_primary'];
            }

            if (array_key_exists('status', $payload)) {
                $resume->status = RcResumeStatus::from((int) $payload['status']);
            }

            if (array_key_exists('source_type', $payload)) {
                $resume->source_type = RcResumeSourceType::from((int) $payload['source_type']);
            }

            $resume->save();

            if ($resume->is_primary === 1) {
                Resume::query()
                    ->where('user_id', $user->id)
                    ->whereKeyNot($resume->id)
                    ->where('is_primary', 1)
                    ->update(['is_primary' => 0]);
            }
        });

        return $this->success((new RcResumeResource($resume->fresh()))->resolve($request));
    }

    private function generateResumeNo(int $userId): string
    {
        do {
            $resumeNo = 'RC'.now()->format('YmdHis').strtoupper(Str::random(6));
        } while (Resume::query()->where('user_id', $userId)->where('resume_no', $resumeNo)->exists());

        return $resumeNo;
    }
}
