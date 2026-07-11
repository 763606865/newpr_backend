<?php

namespace App\Rc\Controllers;

use App\Models\Rc\Notification;
use App\Models\Rc\UserIdentity;
use App\Resources\Rc\RcNotificationResource;
use App\Services\RcIdentityOrganizationService;
use App\Services\RcNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class NotificationController extends Controller
{
    /**
     * 通知列表
     *
     * GET /rc/notifications
     */
    public function index(Request $request): JsonResponse
    {
        $identity = $this->currentIdentity();
        $paginator = RcNotificationService::make()->paginateForIdentity(
            $this->user(),
            $identity,
            $this->getPerPage($request),
            $request->only(['is_read', 'type']),
        );

        $paginator->getCollection()->transform(
            static fn (Notification $notification): array => (new RcNotificationResource($notification))->resolve($request),
        );

        return $this->success($paginator);
    }

    /**
     * 未读通知数量
     *
     * GET /rc/notifications/unread-count
     */
    public function unreadCount(): JsonResponse
    {
        $identity = $this->currentIdentity();

        return $this->success([
            'unread_count' => RcNotificationService::make()->countUnread($this->user(), $identity),
        ]);
    }

    /**
     * 通知详情（查看时自动标记已读）
     *
     * GET /rc/notifications/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $service = RcNotificationService::make();
        $identity = $this->currentIdentity();
        $notification = $service->findForIdentity($this->user(), $identity, $id);

        if (! $notification instanceof Notification) {
            return $this->error('通知不存在。', Response::HTTP_NOT_FOUND);
        }

        $notification = $service->markAsRead($this->user(), $notification);

        return $this->success((new RcNotificationResource($notification))->resolve($request));
    }

    /**
     * 标记单条通知为已读
     *
     * POST /rc/notifications/{id}/read
     */
    public function markRead(Request $request, int $id): JsonResponse
    {
        $service = RcNotificationService::make();
        $identity = $this->currentIdentity();
        $notification = $service->findForIdentity($this->user(), $identity, $id);

        if (! $notification instanceof Notification) {
            return $this->error('通知不存在。', Response::HTTP_NOT_FOUND);
        }

        try {
            $notification = $service->markAsRead($this->user(), $notification);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success((new RcNotificationResource($notification))->resolve($request));
    }

    /**
     * 全部标记为已读
     *
     * POST /rc/notifications/read-all
     */
    public function markAllRead(): JsonResponse
    {
        $identity = $this->currentIdentity();
        $updatedCount = RcNotificationService::make()->markAllAsRead($this->user(), $identity);

        return $this->success([
            'updated_count' => $updatedCount,
            'unread_count' => 0,
        ]);
    }
}
