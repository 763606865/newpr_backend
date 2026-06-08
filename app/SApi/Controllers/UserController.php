<?php

namespace App\SApi\Controllers;

use App\Models\User;
use App\Resources\SApi\SApiUserResource;
use App\SApi\Requests\UserIndexRequest;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * 拉取用户列表（分页）
     *
     * GET /sapi/users
     */
    public function index(UserIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $users = User::query()
            ->when(filled($validated['status'] ?? null), function ($query) use ($validated): void {
                $query->where('status', (string) $validated['status']);
            })
            ->tap(fn ($query) => $this->applyCreatedBetween($query, $validated))
            ->tap(fn ($query) => $this->applyUpdatedBetween($query, $validated))
            ->orderByDesc('id')
            ->paginate($this->getPerPage($request));

        return $this->success(
            $users->through(
                fn (User $user) => (new SApiUserResource($user))->resolve($request),
            ),
        );
    }
}
