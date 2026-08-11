<?php

namespace App\Libs\ThirdParty\CJWL\Api;

use App\Libs\Exceptions\BadRequestException;
use App\Libs\ThirdParty\JucaiDT\Api\ApiRequest;
use Illuminate\Contracts\Container\BindingResolutionException;

class Position extends ApiRequest
{
    /**
     * 查询岗位列表
     *
     * @throws BadRequestException
     * @throws BindingResolutionException
     * @throws \JsonException
     */
    public function query(array $params = []): array
    {
        $promise = $this->request('POST', '/positions/query', $params);

        return $this->response($promise);
    }

    /**
     * 查询岗位详情
     *
     * @param array $params
     * @return array
     * @throws BadRequestException
     * @throws BindingResolutionException
     * @throws \JsonException
     */
    public function detail(array $params = []): array
    {
        validator($params, [
            'position_id' => ['required', 'integer']
        ])->validate();

        $promise = $this->request('GET', '/position/'.$params['position_id']);

        return $this->response($promise);
    }
}
