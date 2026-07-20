<?php

namespace App\Http\Controllers;

use App\Models\Cms\FriendLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FriendLinkController extends Controller
{
    /**
     * 友链
     *
     * GET /cms/friend-links
     *
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        $cityCode = $this->resolveCityCode($request);

        $friendLinks = FriendLink::query()
            ->forCity($cityCode)
            ->enabled()
            ->orderBy('sort')
            ->get()
            ->setVisible([
                'id',
                'name',
                'url',
                'logo',
                'target',
            ]);

        return api_response([
            'friend_links' => $friendLinks,
        ]);
    }
}
