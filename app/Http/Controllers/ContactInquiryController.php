<?php

namespace App\Http\Controllers;

use App\Enums\RcContactInquiryStatus;
use App\Http\Requests\ContactInquiryStoreRequest;
use App\Models\ContactInquiry;
use Illuminate\Http\JsonResponse;

class ContactInquiryController extends Controller
{
    /**
     * 提交联系我们申请。
     *
     * POST /cms/contact-inquiries
     */
    public function store(ContactInquiryStoreRequest $request): JsonResponse
    {
        $inquiry = ContactInquiry::query()->create([
            ...$request->validated(),
            'status' => RcContactInquiryStatus::Pending,
            'submitted_at' => now(),
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);

        return $this->success([
            'id' => $inquiry->id,
            'status' => $inquiry->status->value,
            'submitted_at' => $inquiry->submitted_at?->toDateTimeString(),
        ]);
    }
}
