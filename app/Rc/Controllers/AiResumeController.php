<?php

namespace App\Rc\Controllers;

use App\Models\Rc\UserIdentity;
use App\Rc\Requests\AiResumeParseRequest;
use App\Resources\Rc\AiResumeParseTaskResource;
use App\Services\RcAiResumeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class AiResumeController extends Controller
{
    /**
     * 创建求职者 AI 解析附件简历任务
     *
     * POST /rc/ai/resume-parses
     *
     * @throws \Exception
     */
    public function store(AiResumeParseRequest $request): JsonResponse
    {
        $identity = $this->currentIdentity();

        if (! $identity instanceof UserIdentity) {
            return $this->error('请先切换为求职者身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $task = RcAiResumeService::make()->createParseTask(
                $identity,
                (string) $request->validated('file_url'),
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success((new AiResumeParseTaskResource($task))->resolve($request));
    }

    /**
     * 查询求职者 AI 简历解析任务
     *
     * GET /rc/ai/resume-parses/{id}
     *
     * @throws \Exception
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $identity = $this->currentIdentity();

        if (! $identity instanceof UserIdentity) {
            return $this->error('请先切换为求职者身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $task = RcAiResumeService::make()->findTaskForIdentity($identity, $id);

        if (! $task) {
            return $this->error('AI 简历解析任务不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success((new AiResumeParseTaskResource($task))->resolve($request));
    }
}
