<?php

namespace App\Services;

use App\Enums\AttendanceClockLogClockMethod;
use App\Enums\AttendanceClockLogClockResult;
use App\Enums\AttendanceClockLogPunchType;
use App\Enums\AttendanceClockState;
use App\Enums\AttendanceScheduleStatus;
use App\Enums\ClockMode;
use App\Models\Employee;
use App\Models\Oa\AttendanceClockLog;
use App\Models\Oa\AttendanceSchedule;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;

class AttendanceService extends Service
{
    private const MIN_CLOCK_INTERVAL_MINS = 5;

    /**
     * 月度考勤记录
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function records(Employee $employee, array $payload = []): array
    {
        $monthRange = $this->resolveMonthRange($payload);
        $perPage = max(1, (int) ($payload['per_page'] ?? 20));

        $list = $employee->attendanceSchedules()
            ->whereBetween('date', [$monthRange['start_date'], $monthRange['end_date']])
            ->with([
                'attendanceRule:id,name,code',
                'attendanceClockLogs' => fn ($query) => $query->orderByDesc('punch_time')->orderByDesc('id'),
            ])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->through(function (AttendanceSchedule $schedule): array {
                $latestClockLog = $schedule->attendanceClockLogs->first();

                return [
                    'id' => $schedule->id,
                    'date' => $schedule->date?->toDateString(),
                    'status' => (int) $schedule->status,
                    'status_label' => $this->resolveStatusLabel((int) $schedule->status),
                    'is_rest_day' => (bool) $schedule->is_rest_day,
                    'std_start_time' => $schedule->std_start_time?->toDateTimeString(),
                    'std_end_time' => $schedule->std_end_time?->toDateTimeString(),
                    'actual_start_time' => $schedule->actual_start_time?->toDateTimeString(),
                    'actual_end_time' => $schedule->actual_end_time?->toDateTimeString(),
                    'actual_work_hours' => (float) ($schedule->actual_work_hours ?? 0),
                    'late_mins' => (int) $schedule->late_mins,
                    'early_leave_mins' => (int) $schedule->early_leave_mins,
                    'absence_mins' => (int) $schedule->absence_mins,
                    'attendance_rule' => [
                        'id' => $schedule->attendanceRule?->id,
                        'name' => $schedule->attendanceRule?->name,
                        'code' => $schedule->attendanceRule?->code,
                    ],
                    'latest_clock_log' => $latestClockLog ? [
                        'id' => $latestClockLog->id,
                        'punch_type' => is_int($latestClockLog->punch_type) ? $latestClockLog->punch_type : $latestClockLog->punch_type?->value,
                        'punch_type_label' => is_int($latestClockLog->punch_type) ? null : $latestClockLog->punch_type?->getLabel(),
                        'clocked_at' => $latestClockLog->punch_time?->toDateTimeString(),
                    ] : null,
                ];
            });

        return [
            'month' => [
                'month' => $monthRange['month'],
                'start_date' => $monthRange['start_date'],
                'end_date' => $monthRange['end_date'],
            ],
            'list' => $list,
        ];
    }

    /**
     * 当日考勤详情
     *
     * @return array<string, mixed>
     *
     * @throws \DomainException
     */
    public function show(Employee $employee, string $date): array
    {
        try {
            $day = Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            throw new \DomainException('日期参数无效。');
        }

        $schedule = $employee->attendanceSchedules()
            ->whereDate('date', $day)
            ->with([
                'attendanceRule:id,name,code',
                'attendanceClockLogs' => fn ($query) => $query->orderBy('punch_time')->orderBy('id'),
            ])
            ->first();

        if (! $schedule) {
            throw new \DomainException('当日无考勤记录。');
        }

        return [
            'id' => $schedule->id,
            'date' => $schedule->date?->toDateString(),
            'status' => (int) $schedule->status,
            'status_label' => $this->resolveStatusLabel((int) $schedule->status),
            'is_rest_day' => (bool) $schedule->is_rest_day,
            'std_start_time' => $schedule->std_start_time?->toDateTimeString(),
            'std_end_time' => $schedule->std_end_time?->toDateTimeString(),
            'actual_start_time' => $schedule->actual_start_time?->toDateTimeString(),
            'actual_end_time' => $schedule->actual_end_time?->toDateTimeString(),
            'actual_work_hours' => (float) ($schedule->actual_work_hours ?? 0),
            'late_mins' => (int) $schedule->late_mins,
            'early_leave_mins' => (int) $schedule->early_leave_mins,
            'absence_mins' => (int) $schedule->absence_mins,
            'attendance_rule' => [
                'id' => $schedule->attendanceRule?->id,
                'name' => $schedule->attendanceRule?->name,
                'code' => $schedule->attendanceRule?->code,
            ],
            'clock_logs' => $schedule->attendanceClockLogs->map(function (AttendanceClockLog $clockLog): array {
                $punchType = $clockLog->punch_type;
                $clockMethod = $clockLog->clock_method;

                return [
                    'id' => $clockLog->id,
                    'punch_type' => is_int($punchType) ? $punchType : $punchType?->value,
                    'punch_type_label' => is_int($punchType) ? null : $punchType?->getLabel(),
                    'clock_method' => is_int($clockMethod) ? $clockMethod : $clockMethod?->value,
                    'clock_method_label' => is_int($clockMethod) ? null : $clockMethod?->getLabel(),
                    'clocked_at' => $clockLog->punch_time?->toDateTimeString(),
                    'address' => $clockLog->address,
                    'remark' => $clockLog->remark,
                ];
            })->values(),
        ];
    }

    /**
     * 月度统计
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function statistics(Employee $employee, array $payload = []): array
    {
        $monthRange = $this->resolveMonthRange($payload);

        $query = $employee->attendanceSchedules()
            ->whereBetween('date', [$monthRange['start_date'], $monthRange['end_date']]);

        return [
            'month' => [
                'month' => $monthRange['month'],
                'start_date' => $monthRange['start_date'],
                'end_date' => $monthRange['end_date'],
            ],
            'total_days' => (clone $query)->count(),
            'normal_days' => (clone $query)->where('status', AttendanceScheduleStatus::Normal->value)->count(),
            'late_days' => (clone $query)->where('status', AttendanceScheduleStatus::Late->value)->count(),
            'early_days' => (clone $query)->where('status', AttendanceScheduleStatus::Early->value)->count(),
            'missing_card_days' => (clone $query)->where('status', AttendanceScheduleStatus::MissingCard->value)->count(),
            'absence_days' => (clone $query)->where('status', AttendanceScheduleStatus::Absence->value)->count(),
            'rest_days' => (clone $query)->where('is_rest_day', 1)->count(),
            'total_actual_work_hours' => (float) ((clone $query)->sum('actual_work_hours') ?? 0),
            'total_late_mins' => (int) ((clone $query)->sum('late_mins') ?? 0),
            'total_early_leave_mins' => (int) ((clone $query)->sum('early_leave_mins') ?? 0),
            'total_absence_mins' => (int) ((clone $query)->sum('absence_mins') ?? 0),
        ];
    }

    /**
     * 获取今日考勤概览
     *
     * @return array<string, mixed>
     */
    public function today(Employee $employee, ?Carbon $clockAt = null): array
    {
        $clockAt ??= Carbon::now();

        $schedule = $employee->attendanceSchedules()
            ->whereDate('date', $clockAt->toDateString())
            ->with([
                'attendanceRule',
                'attendanceClockLogs' => fn ($query) => $query->orderByDesc('punch_time')->orderByDesc('id'),
            ])
            ->first();

        if (! $schedule) {
            $clockState = AttendanceClockState::Unavailable;

            return [
                'date' => $clockAt->toDateString(),
                'has_schedule' => false,
                'can_clock' => false,
                'clock_state' => $clockState->value,
                'clock_state_label' => $clockState->getLabel(),
                'next_punch_type' => null,
                'next_punch_type_label' => null,
                'schedule' => null,
                'latest_clock_log' => null,
            ];
        }

        $latestClockLog = $schedule->attendanceClockLogs->first();
        /** @var AttendanceClockState $clockState */
        $clockState = $schedule->clock_state;
        $nextPunchType = $schedule->next_punch_type;

        return [
            'date' => $schedule->date?->toDateString(),
            'has_schedule' => true,
            'can_clock' => ! $schedule->is_clock_finished,
            'clock_state' => $clockState->value,
            'clock_state_label' => $clockState->getLabel(),
            'next_punch_type' => $nextPunchType?->value,
            'next_punch_type_label' => $nextPunchType?->getLabel(),
            'schedule' => [
                'id' => $schedule->id,
                'status' => (int) $schedule->status,
                'status_label' => $this->resolveStatusLabel((int) $schedule->status),
                'is_rest_day' => (bool) $schedule->is_rest_day,
                'std_start_time' => $schedule->std_start_time?->toDateTimeString(),
                'std_end_time' => $schedule->std_end_time?->toDateTimeString(),
                'actual_start_time' => $schedule->actual_start_time?->toDateTimeString(),
                'actual_end_time' => $schedule->actual_end_time?->toDateTimeString(),
                'actual_work_hours' => (float) ($schedule->actual_work_hours ?? 0),
                'late_mins' => (int) $schedule->late_mins,
                'early_leave_mins' => (int) $schedule->early_leave_mins,
                'absence_mins' => (int) $schedule->absence_mins,
            ],
            'latest_clock_log' => $latestClockLog ? [
                'id' => $latestClockLog->id,
                'punch_type' => is_int($latestClockLog->punch_type) ? $latestClockLog->punch_type : $latestClockLog->punch_type?->value,
                'punch_type_label' => is_int($latestClockLog->punch_type) ? null : $latestClockLog->punch_type?->getLabel(),
                'clocked_at' => $latestClockLog->punch_time?->toDateTimeString(),
            ] : null,
        ];
    }

    /**
     * 考勤打卡
     *
     * @param  array<string, mixed>  $payload
     * @param  array{ip?:string,user_agent?:string,raw_payload?:array<string,mixed>}  $context
     * @return array<string, mixed>
     *
     * @throws \Throwable
     */
    public function clock(Employee $employee, array $payload, array $context = []): array
    {
        $clockAt = isset($payload['punch_time']) && ! blank($payload['punch_time'])
            ? Carbon::parse((string) $payload['punch_time'])
            : Carbon::now();

        return DB::transaction(function () use ($employee, $payload, $clockAt, $context): array {
            /** @var null|AttendanceSchedule $schedule */
            $schedule = $this->resolveScheduleForClock($employee, $clockAt);

            if (! $schedule) {
                throw new \DomainException('当前时间未匹配到考勤排班。');
            }

            if (! empty($payload['idempotency_key'])) {
                // 同一个幂等键只处理一次，避免重复点击/重试导致重复入库。
                $existingLog = AttendanceClockLog::query()
                    ->where('idempotency_key', (string) $payload['idempotency_key'])
                    ->first();

                if ($existingLog) {
                    return $this->buildClockResponse($schedule->refresh(), $existingLog, true);
                }
            }

            $this->ensureMinClockInterval($schedule, $clockAt);

            $punchType = $schedule->actual_start_time
                ? AttendanceClockLogPunchType::ClockOut
                : AttendanceClockLogPunchType::ClockIn;

            $schedule->clock($clockAt, ClockMode::Normal)
                ->recalculateStatus();
            $schedule->save();

            $clockLog = AttendanceClockLog::query()->create([
                'company_id' => $schedule->company_id,
                'department_id' => $schedule->department_id,
                'employee_id' => $schedule->employee_id,
                'attendance_rule_id' => $schedule->attendance_rule_id,
                'attendance_schedule_id' => $schedule->id,
                'date' => $schedule->date?->toDateString() ?: $clockAt->toDateString(),
                'punch_time' => $clockAt,
                'punch_type' => $punchType->value,
                'clock_method' => (int) ($payload['clock_method'] ?? AttendanceClockLogClockMethod::App->value),
                'clock_result' => AttendanceClockLogClockResult::Valid->value,
                'is_overnight' => (int) $schedule->is_overnight,
                'timezone' => $payload['timezone'] ?? null,
                'device_id' => $payload['device_id'] ?? null,
                'device_name' => $payload['device_name'] ?? null,
                'ip' => $context['ip'] ?? null,
                'user_agent' => $context['user_agent'] ?? null,
                'lng' => isset($payload['lng']) ? (float) $payload['lng'] : null,
                'lat' => isset($payload['lat']) ? (float) $payload['lat'] : null,
                'address' => $payload['address'] ?? null,
                'location_accuracy' => isset($payload['location_accuracy']) ? (int) $payload['location_accuracy'] : null,
                'wifi_ssid' => $payload['wifi_ssid'] ?? null,
                'wifi_bssid' => $payload['wifi_bssid'] ?? null,
                'remark' => $payload['remark'] ?? null,
                'idempotency_key' => $payload['idempotency_key'] ?? null,
                'raw_payload' => $context['raw_payload'] ?? $payload,
                'extra' => [
                    'source' => 'api',
                ],
            ]);

            return $this->buildClockResponse($schedule->refresh(), $clockLog, false);
        });
    }

    private function resolveScheduleForClock(Employee $employee, Carbon $clockAt): ?AttendanceSchedule
    {
        // 先匹配当天排班。
        $todaySchedule = $employee->attendanceSchedules()
            ->whereDate('date', $clockAt->toDateString())
            ->with(['attendanceRule'])
            ->lockForUpdate()
            ->first();

        if ($todaySchedule) {
            return $todaySchedule;
        }

        // 当天无排班时，尝试匹配前一天跨天班。
        return $employee->attendanceSchedules()
            ->whereDate('date', $clockAt->copy()->subDay()->toDateString())
            ->where('is_overnight', 1)
            ->with(['attendanceRule'])
            ->lockForUpdate()
            ->first();
    }

    /**
     * 为避免误操作，上班卡与下班卡之间至少间隔 5 分钟。
     *
     * @throws \DomainException
     */
    private function ensureMinClockInterval(AttendanceSchedule $schedule, Carbon $clockAt): void
    {
        if (! $schedule->has_clocked_in || $schedule->has_clocked_out) {
            return;
        }

        $actualStartTime = $schedule->actual_start_time;

        if (! $actualStartTime) {
            return;
        }

        $minClockOutTime = $actualStartTime->copy()->addMinutes(self::MIN_CLOCK_INTERVAL_MINS);

        if ($clockAt->lt($minClockOutTime)) {
            throw new \DomainException(sprintf(
                '上班卡与下班卡至少间隔 %d 分钟，请稍后再试。',
                self::MIN_CLOCK_INTERVAL_MINS,
            ));
        }
    }

    private function resolveStatusLabel(int $status): string
    {
        $label = AttendanceScheduleStatus::tryFrom($status)?->getLabel();

        if (is_string($label)) {
            return $label;
        }

        if ($label instanceof Htmlable) {
            return $label->toHtml();
        }

        return '未知';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{month:string,start_date:string,end_date:string}
     */
    private function resolveMonthRange(array $payload): array
    {
        $month = trim((string) ($payload['month'] ?? ''));

        try {
            $currentMonth = $month !== ''
                ? Carbon::createFromFormat('Y-m', $month)
                : Carbon::now();
        } catch (\Throwable) {
            $currentMonth = Carbon::now();
        }

        return [
            'month' => $currentMonth->format('Y-m'),
            'start_date' => $currentMonth->copy()->startOfMonth()->toDateString(),
            'end_date' => $currentMonth->copy()->endOfMonth()->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildClockResponse(AttendanceSchedule $schedule, AttendanceClockLog $clockLog, bool $idempotent): array
    {
        $punchType = $clockLog->punch_type;

        return [
            'idempotent' => $idempotent,
            'clock_log_id' => $clockLog->id,
            'punch_type' => is_int($punchType) ? $punchType : $punchType?->value,
            'punch_type_label' => is_int($punchType) ? null : $punchType?->getLabel(),
            'clocked_at' => $clockLog->punch_time?->toDateTimeString(),
            'schedule' => [
                'id' => $schedule->id,
                'date' => $schedule->date?->toDateString(),
                'status' => $schedule->status,
                'status_label' => $this->resolveStatusLabel((int) $schedule->status),
                'actual_start_time' => $schedule->actual_start_time?->toDateTimeString(),
                'actual_end_time' => $schedule->actual_end_time?->toDateTimeString(),
                'actual_work_hours' => (float) ($schedule->actual_work_hours ?? 0),
                'late_mins' => (int) $schedule->late_mins,
                'early_leave_mins' => (int) $schedule->early_leave_mins,
                'absence_mins' => (int) $schedule->absence_mins,
            ],
        ];
    }
}
