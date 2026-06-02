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

        $pageSize = max(1, min(100, $request->integer('page_size', 15)));

        $resumes = Resume::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_primary')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($pageSize);

        return $this->success([
            'resumes' => RcResumeResource::collection($resumes->items())->resolve($request),
            'pagination' => [
                'current_page' => $resumes->currentPage(),
                'per_page' => $resumes->perPage(),
                'total' => $resumes->total(),
                'last_page' => $resumes->lastPage(),
            ],
        ]);
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

        return $this->success([
            'resume' => (new RcResumeResource($resume))->resolve($request),
        ]);
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
            $hasPrimaryResume = Resume::query()
                ->where('user_id', $user->id)
                ->where('is_primary', 1)
                ->exists();

            $resume = Resume::query()->create([
                ...$payload,
                'user_id' => $user->id,
                'resume_no' => $this->generateResumeNo($user->id),
                'source_type' => (int) ($payload['source_type'] ?? RcResumeSourceType::Manual->value),
                'status' => (int) ($payload['status'] ?? RcResumeStatus::Normal->value),
                'is_primary' => ($isPrimary || ! $hasPrimaryResume) ? 1 : 0,
            ]);

            if ($resume->is_primary === 1) {
                Resume::query()
                    ->where('user_id', $user->id)
                    ->whereKeyNot($resume->id)
                    ->where('is_primary', 1)
                    ->update(['is_primary' => 0]);
            }

            return $resume;
        });

        return $this->success([
            'resume' => (new RcResumeResource($resume->fresh()))->resolve($request),
        ]);
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
                $resume->status = (int) $payload['status'];
            }

            if (array_key_exists('source_type', $payload)) {
                $resume->source_type = (int) $payload['source_type'];
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

        return $this->success([
            'resume' => (new RcResumeResource($resume->fresh()))->resolve($request),
        ]);
    }

    private function generateResumeNo(int $userId): string
    {
        do {
            $resumeNo = 'RC'.now()->format('YmdHis').strtoupper(Str::random(6));
        } while (Resume::query()->where('user_id', $userId)->where('resume_no', $resumeNo)->exists());

        return $resumeNo;
    }
}
