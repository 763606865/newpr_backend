<?php

namespace App\Rc\Controllers;

use App\Enums\RcReportReasonType;
use App\Enums\RcReportStatus;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\Rc\Report;
use App\Models\Rc\Resume;
use App\Models\Rc\UserIdentity;
use App\Rc\Requests\ReportStoreRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    /**
     * 提交举报
     *
     * POST /rc/reports
     */
    public function store(ReportStoreRequest $request): JsonResponse
    {
        $identity = $this->currentIdentity();

        if (! $identity instanceof UserIdentity) {
            return $this->error('请先选择用户身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $request->validated();
        $reportable = $this->resolveReportable(
            (string) $validated['reportable_type'],
            (int) $validated['reportable_id'],
        );

        if (! $reportable instanceof Model) {
            return $this->error('举报对象不存在。', Response::HTTP_NOT_FOUND);
        }

        $report = new Report([
            'user_id' => $this->user()->id,
            'creator_user_identity_id' => $identity->id,
            'reason_type' => RcReportReasonType::from((int) $validated['reason_type']),
            'reason' => $validated['reason'] ?? null,
            'description' => $validated['description'] ?? null,
            'attachments' => $validated['attachments'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'extra' => $validated['extra'] ?? null,
        ]);
        $report->reportable()->associate($reportable);
        $report->save();

        return $this->success([
            'report' => [
                'id' => $report->id,
                'status' => $report->status instanceof RcReportStatus ? $report->status->value : $report->status,
                'reportable_type' => $report->reportable_type,
                'reportable_id' => $report->reportable_id,
                'created_at' => $report->created_at,
            ],
        ]);
    }

    private function resolveReportable(string $type, int $id): ?Model
    {
        return match ($type) {
            'job' => Job::query()->find($id),
            'company' => Company::query()->find($id),
            'resume' => Resume::query()->find($id),
            default => null,
        };
    }
}
