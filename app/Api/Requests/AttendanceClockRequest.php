<?php

namespace App\Api\Requests;

use App\Enums\AttendanceClockLogClockMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceClockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'clock_method' => ['nullable', Rule::enum(AttendanceClockLogClockMethod::class)],
            'timezone' => ['nullable', 'string', 'max:64'],
            'device_id' => ['nullable', 'string', 'max:128'],
            'device_name' => ['nullable', 'string', 'max:128'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'address' => ['nullable', 'string', 'max:255'],
            'location_accuracy' => ['nullable', 'integer', 'min:0'],
            'wifi_ssid' => ['nullable', 'string', 'max:128'],
            'wifi_bssid' => ['nullable', 'string', 'max:64'],
            'remark' => ['nullable', 'string', 'max:255'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'clock_method.enum' => ':attribute 不在可选范围内。',
            'timezone.max' => ':attribute 长度不能超过 :max 个字符。',
            'device_id.max' => ':attribute 长度不能超过 :max 个字符。',
            'device_name.max' => ':attribute 长度不能超过 :max 个字符。',
            'lng.between' => ':attribute 取值范围应在 -180 到 180 之间。',
            'lat.between' => ':attribute 取值范围应在 -90 到 90 之间。',
            'address.max' => ':attribute 长度不能超过 :max 个字符。',
            'location_accuracy.min' => ':attribute 不能小于 :min。',
            'wifi_ssid.max' => ':attribute 长度不能超过 :max 个字符。',
            'wifi_bssid.max' => ':attribute 长度不能超过 :max 个字符。',
            'remark.max' => ':attribute 长度不能超过 :max 个字符。',
            'idempotency_key.max' => ':attribute 长度不能超过 :max 个字符。',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'clock_method' => '打卡方式',
            'timezone' => '时区',
            'device_id' => '设备ID',
            'device_name' => '设备名称',
            'lng' => '经度',
            'lat' => '纬度',
            'address' => '打卡地址',
            'location_accuracy' => '定位精度',
            'wifi_ssid' => 'WiFi名称',
            'wifi_bssid' => 'WiFi BSSID',
            'remark' => '备注',
            'idempotency_key' => '幂等键',
        ];
    }
}
