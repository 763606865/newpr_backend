<?php

namespace App\Api\Controllers;

use App\Api\Requests\AttendanceClockRequest;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttendanceController extends Controller
{
    /**
     * 考勤首页（预留）
     *
     * GET /api/attendance
     *
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        return $this->success();
    }

    /**
     * 今日考勤概览
     *
     * GET /api/attendance/today
     *
     * @throws \Exception
     */
    public function today(Request $request): JsonResponse
    {
        $result = AttendanceService::make()->today($this->employee());

        return $this->success($result);
    }

    /**
     * 考勤打卡
     *
     * POST /api/attendance/clock
     *
     * @throws \Exception
     */
    public function clock(AttendanceClockRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['punch_time'] = Carbon::now();
        try {
            $result = AttendanceService::make()->clock(
                $this->employee(),
                $payload,
                [
                    'ip' => (string) $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                    'raw_payload' => $request->all(),
                ],
            );
        } catch (\DomainException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success($result);
    }

    /**
     * 月度考勤记录
     *
     * GET /api/attendance/records
     *
     * @throws \Exception
     */
    public function records(Request $request): JsonResponse
    {
        $result = AttendanceService::make()->records($this->employee(), [
            'month' => $request->input('month'),
            'per_page' => (int) $request->input('per_page', 20),
        ]);

        return $this->success($result);
    }

    /**
     * 单日考勤详情
     *
     * GET /api/attendance/records/{date}
     *
     * @throws \Exception
     */
    public function show(string $date, Request $request): JsonResponse
    {
        try {
            $result = AttendanceService::make()->show($this->employee(), $date);
        } catch (\DomainException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success($result);
    }

    /**
     * 月度考勤统计
     *
     * GET /api/attendance/statistics
     *
     * @throws \Exception
     */
    public function statistics(Request $request): JsonResponse
    {
        $result = AttendanceService::make()->statistics($this->employee(), [
            'month' => $request->input('month'),
        ]);

        return $this->success($result);
    }
}
