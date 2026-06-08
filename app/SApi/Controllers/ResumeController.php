<?php

namespace App\SApi\Controllers;

use App\Models\Rc\Resume;
use App\Resources\SApi\SApiResumeResource;
use App\SApi\Requests\ResumeIndexRequest;
use Illuminate\Http\JsonResponse;

class ResumeController extends Controller
{
    /**
     * 拉取简历列表（分页）
     *
     * GET /sapi/resumes
     */
    public function index(ResumeIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $resumes = Resume::query()
            ->when(filled($validated['user_id'] ?? null), function ($query) use ($validated): void {
                $query->where('user_id', (int) $validated['user_id']);
            })
            ->when(filled($validated['city_code'] ?? null), function ($query) use ($validated): void {
                $query->where('current_city_code', (string) $validated['city_code']);
            })
            ->when(isset($validated['status']), function ($query) use ($validated): void {
                $query->where('status', (int) $validated['status']);
            })
            ->tap(fn ($query) => $this->applyCreatedBetween($query, $validated))
            ->tap(fn ($query) => $this->applyUpdatedBetween($query, $validated))
            ->orderByDesc('id')
            ->paginate($this->getPerPage($request));

        return $this->success(
            $resumes->through(
                fn (Resume $resume) => (new SApiResumeResource($resume))->resolve($request),
            ),
        );
    }
}
