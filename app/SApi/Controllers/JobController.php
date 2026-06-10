<?php

namespace App\SApi\Controllers;

use App\Models\Rc\Job;
use App\Resources\SApi\SApiJobResource;
use App\SApi\Requests\JobIndexRequest;
use Illuminate\Http\JsonResponse;

class JobController extends Controller
{
    /**
     * 拉取职位列表
     *
     * GET /sapi/jobs
     */
    public function index(JobIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Job::query()
            ->with(['company:id,name', 'position:code,name'])
            ->when(filled($validated['company_id'] ?? null), function ($query) use ($validated): void {
                $query->where('company_id', (int) $validated['company_id']);
            })
            ->when(filled($validated['city_code'] ?? null), function ($query) use ($validated): void {
                $query->where('city_code', (string) $validated['city_code']);
            })
            ->when(isset($validated['status']), function ($query) use ($validated): void {
                $query->where('status', (int) $validated['status']);
            })
            ->tap(fn ($query) => $this->applyCreatedBetween($query, $validated))
            ->tap(fn ($query) => $this->applyUpdatedBetween($query, $validated))
            ->orderByDesc('id');

        return $this->indexDataResponse(
            $request,
            $query,
            fn (Job $job) => (new SApiJobResource($job))->resolve($request),
        );
    }
}
