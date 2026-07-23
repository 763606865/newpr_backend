<?php

namespace App\Rc\Controllers;

use App\Models\ImInteractionRequest;
use App\Models\Rc\UserIdentity;
use App\Models\Rc\UserIm;
use App\Rc\Requests\ImInteractionRequestRespondRequest;
use App\Rc\Requests\ImInteractionRequestStoreRequest;
use App\Resources\Rc\ImInteractionRequestResource;
use App\Services\ImInteractionRequestService;
use App\Services\IMService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use InvalidArgumentException;

class ImInteractionRequestController extends Controller
{
    /**
     * 创建 IM 交互请求
     *
     * POST /rc/im/interaction-requests
     *
     * @throws \Exception
     */
    public function store(ImInteractionRequestStoreRequest $request): JsonResponse
    {
        $identity = $this->currentIdentity();

        if (! $identity instanceof UserIdentity) {
            return $this->error('请先选择用户身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var UserIm $userIm */
        $userIm = IMService::make()->resolveUserIm($identity);

        try {
            $result = ImInteractionRequestService::make()->create($userIm, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success([
            'interaction_request' => (new ImInteractionRequestResource($result['interaction_request']))->resolve($request),
            'message' => $result['message'],
            'card' => $result['card'],
        ]);
    }

    /**
     * 处理 IM 交互请求
     *
     * POST /rc/im/interaction-requests/{id}/respond
     *
     * @throws \Exception
     */
    public function respond(ImInteractionRequestRespondRequest $request, int $id): JsonResponse
    {
        $identity = $this->currentIdentity();

        if (! $identity instanceof UserIdentity) {
            return $this->error('请先选择用户身份。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $interactionRequest = ImInteractionRequest::query()
            ->with(['conversation', 'senderUserIm.user', 'receiverUserIm.user'])
            ->find($id);

        if (! $interactionRequest instanceof ImInteractionRequest) {
            return $this->error('交互请求不存在。', Response::HTTP_NOT_FOUND);
        }

        /** @var UserIm $userIm */
        $userIm = IMService::make()->resolveUserIm($identity);

        try {
            $result = ImInteractionRequestService::make()->respond(
                $userIm,
                $interactionRequest,
                (string) $request->validated('action'),
                $request->validated('reason'),
            );
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success([
            'interaction_request' => (new ImInteractionRequestResource($result['interaction_request']))->resolve($request),
            'message' => $result['message'],
            'card' => $result['card'],
        ]);
    }
}
