<?php

namespace App\Rc\Controllers;

use App\Models\ImQuickPhrase;
use App\Models\Rc\UserIm;
use App\Rc\Requests\ImQuickPhraseStoreRequest;
use App\Rc\Requests\ImQuickPhraseUpdateRequest;
use App\Resources\Rc\ImQuickPhraseResource;
use App\Services\IMService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ImQuickPhraseController extends Controller
{
    /**
     * 常用快捷短语列表
     *
     * GET /rc/im/quick-phrases
     *
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        /** @var UserIm $userIm */
        $userIm = IMService::make()->resolveUserIm($this->currentIdentity());
        $keyword = trim((string) $request->input('keyword', ''));

        $paginator = ImQuickPhrase::query()
            ->where('user_im_id', $userIm->id)
            ->when($request->has('is_enabled'), function ($query) use ($request): void {
                $query->where('is_enabled', $request->boolean('is_enabled'));
            })
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($query) use ($keyword): void {
                    $query->where('title', 'like', "%{$keyword}%")
                        ->orWhere('content', 'like', "%{$keyword}%");
                });
            })
            ->orderByDesc('sort')
            ->latest('id')
            ->paginate($this->getPerPage($request));

        $paginator->getCollection()->transform(
            fn (ImQuickPhrase $phrase): array => (new ImQuickPhraseResource($phrase))->resolve($request),
        );

        return $this->success($paginator);
    }

    /**
     * 新增常用快捷短语
     *
     * POST /rc/im/quick-phrases
     *
     * @throws \Exception
     */
    public function store(ImQuickPhraseStoreRequest $request): JsonResponse
    {
        /** @var UserIm $userIm */
        $userIm = IMService::make()->resolveUserIm($this->currentIdentity());
        $validated = $request->validated();

        $phrase = ImQuickPhrase::query()->create([
            'user_im_id' => $userIm->id,
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
            'sort' => (int) ($validated['sort'] ?? 0),
            'is_enabled' => (bool) ($validated['is_enabled'] ?? true),
        ]);

        return $this->success([
            'quick_phrase' => (new ImQuickPhraseResource($phrase))->resolve($request),
        ]);
    }

    /**
     * 常用快捷短语详情
     *
     * GET /rc/im/quick-phrases/{id}
     *
     * @throws \Exception
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $phrase = $this->findPhraseForCurrentUserIm($id);

        if (! $phrase instanceof ImQuickPhrase) {
            return $this->error('快捷短语不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success([
            'quick_phrase' => (new ImQuickPhraseResource($phrase))->resolve($request),
        ]);
    }

    /**
     * 更新常用快捷短语
     *
     * PUT/PATCH /rc/im/quick-phrases/{id}
     *
     * @throws \Exception
     */
    public function update(ImQuickPhraseUpdateRequest $request, int $id): JsonResponse
    {
        $phrase = $this->findPhraseForCurrentUserIm($id);

        if (! $phrase instanceof ImQuickPhrase) {
            return $this->error('快捷短语不存在。', Response::HTTP_NOT_FOUND);
        }

        $phrase->update($request->validated());

        return $this->success([
            'quick_phrase' => (new ImQuickPhraseResource($phrase))->resolve($request),
        ]);
    }

    /**
     * 删除常用快捷短语
     *
     * DELETE /rc/im/quick-phrases/{id}
     *
     * @throws \Exception
     */
    public function destroy(int $id): JsonResponse
    {
        $phrase = $this->findPhraseForCurrentUserIm($id);

        if (! $phrase instanceof ImQuickPhrase) {
            return $this->error('快捷短语不存在。', Response::HTTP_NOT_FOUND);
        }

        $phrase->delete();

        return $this->success();
    }

    /**
     * @throws \Exception
     */
    private function findPhraseForCurrentUserIm(int $id): ?ImQuickPhrase
    {
        /** @var UserIm $userIm */
        $userIm = IMService::make()->resolveUserIm($this->currentIdentity());

        return ImQuickPhrase::query()
            ->where('user_im_id', $userIm->id)
            ->find($id);
    }
}
