<?php

namespace App\Rc\Controllers;

use App\Libs\Facades\Im;
use App\Libs\IM\IMException;
use App\Models\ImConversation;
use App\Models\Rc\UserIm;
use App\Rc\Requests\ImConversationStoreRequest;
use App\Resources\Rc\ImConversationResource;
use App\Services\IMService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class ImConversationController extends Controller
{
    /**
     * 返回会话列表
     *
     * GET /rc/im/conversations
     *
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        /** @var UserIm $userIm */
        $userIm = IMService::make()->resolveUserIm($this->currentIdentity());
        $request->attributes->set('current_user_im_id', $userIm->id);

        $paginator = $userIm->memberConversations()
            ->with([
                'context',
                'members.member.user',
                'members.member.userIdentity',
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($this->getPerPage($request));

        $paginator->getCollection()->transform(
            fn ($conversation): array => (new ImConversationResource($conversation))->resolve($request),
        );

        return $this->success($paginator);
    }

    /**
     * 创建会话
     *
     * POST /rc/im/conversations
     *
     * @throws \Exception
     */
    public function store(ImConversationStoreRequest $request): JsonResponse
    {
        $identity = $this->currentIdentity();
        $imService = IMService::make();

        /** @var UserIm $userIm */
        $userIm = $imService->resolveUserIm($identity);
        $request->attributes->set('current_user_im_id', $userIm->id);

        try {
            $conversation = $imService->resolvedConversation($identity, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success((new ImConversationResource($conversation))->resolve($request));
    }

    /**
     * 获取会话历史消息
     *
     * GET /rc/im/conversations/{id}/messages
     *
     * @throws \Exception
     */
    public function getMessages(Request $request, int $id): JsonResponse
    {
        /** @var UserIm $userIm */
        $userIm = IMService::make()->resolveUserIm($this->currentIdentity());

        $conversation = ImConversation::query()
            ->whereKey($id)
            ->whereHas('members', function ($query) use ($userIm): void {
                $query
                    ->where('member_type', 'rc_user_im')
                    ->where('member_id', $userIm->id);
            })
            ->first();

        if (! $conversation instanceof ImConversation) {
            return $this->error('会话不存在。', Response::HTTP_NOT_FOUND);
        }

        try {
            $messages = Im::conversation()->getMessages($conversation->conversation_no, [
                ...$request->query(),
                'user_id' => $userIm->external_user_id,
            ]);
        } catch (ConnectionException|IMException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_BAD_GATEWAY);
        }

        return $this->success($messages);
    }
}
