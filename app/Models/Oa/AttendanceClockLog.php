<?php

namespace App\Models\Oa;

use App\Enums\AttendanceClockLogClockMethod;
use App\Enums\AttendanceClockLogClockResult;
use App\Enums\AttendanceClockLogPunchType;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 打卡日志表
 *
 * @property int $id 主键ID
 * @property int $company_id 公司ID
 * @property int $department_id 部门ID
 * @property int $employee_id 员工ID
 * @property int|null $attendance_rule_id 考勤规则ID
 * @property int|null $attendance_schedule_id 考勤记录ID
 * @property Carbon $date 考勤归属日期
 * @property Carbon $punch_time 实际打卡时间
 * @property AttendanceClockLogPunchType $punch_type 打卡类型
 * @property AttendanceClockLogClockMethod $clock_method 打卡方式
 * @property AttendanceClockLogClockResult $clock_result 打卡结果
 * @property bool $is_overnight 是否跨天班次
 * @property string|null $timezone 时区标识
 * @property string|null $device_id 设备ID
 * @property string|null $device_name 设备名称
 * @property string|null $ip 客户端IP
 * @property string|null $user_agent 客户端UA
 * @property string|null $lng 经度
 * @property string|null $lat 纬度
 * @property string|null $address 打卡地址
 * @property int|null $location_accuracy 定位精度(米)
 * @property string|null $wifi_ssid WiFi名称
 * @property string|null $wifi_bssid WiFi BSSID
 * @property string|null $remark 备注
 * @property string|null $idempotency_key 幂等键
 * @property array<string, mixed>|null $raw_payload 原始请求快照
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read Company $company 所属公司
 * @property-read Department $department 所属部门
 * @property-read Employee $employee 所属员工
 * @property-read AttendanceRule|null $attendanceRule 关联考勤规则
 * @property-read AttendanceSchedule|null $attendanceSchedule 关联考勤记录
 */
#[Table('oa_attendance_clock_logs')]
#[Fillable([
    'company_id',
    'department_id',
    'employee_id',
    'attendance_rule_id',
    'attendance_schedule_id',
    'date',
    'punch_time',
    'punch_type',
    'clock_method',
    'clock_result',
    'is_overnight',
    'timezone',
    'device_id',
    'device_name',
    'ip',
    'user_agent',
    'lng',
    'lat',
    'address',
    'location_accuracy',
    'wifi_ssid',
    'wifi_bssid',
    'remark',
    'idempotency_key',
    'raw_payload',
    'extra',
])]
class AttendanceClockLog extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'punch_type' => AttendanceClockLogPunchType::ClockIn,
        'clock_method' => AttendanceClockLogClockMethod::App,
        'clock_result' => AttendanceClockLogClockResult::Valid,
        'is_overnight' => 0,
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'punch_time' => 'datetime',
            'punch_type' => AttendanceClockLogPunchType::class,
            'clock_method' => AttendanceClockLogClockMethod::class,
            'clock_result' => AttendanceClockLogClockResult::class,
            'is_overnight' => 'boolean',
            'lng' => 'decimal:7',
            'lat' => 'decimal:7',
            'location_accuracy' => 'integer',
            'raw_payload' => 'array',
            'extra' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function attendanceRule(): BelongsTo
    {
        return $this->belongsTo(AttendanceRule::class, 'attendance_rule_id');
    }

    public function attendanceSchedule(): BelongsTo
    {
        return $this->belongsTo(AttendanceSchedule::class, 'attendance_schedule_id');
    }
}
