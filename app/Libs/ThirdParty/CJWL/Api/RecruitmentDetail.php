<?php

namespace App\Libs\ThirdParty\CJWL\Api;

use App\Libs\Exceptions\BadRequestException;
use Illuminate\Contracts\Container\BindingResolutionException;

class RecruitmentDetail extends ApiRequest
{
    /**
     * 查询公告列表
     *
     * @throws BadRequestException
     * @throws BindingResolutionException
     * @throws \JsonException
     */
    public function query(array $params = []): array
    {
        $promise = $this->request('POST', '/recruitment-details/query', $params);

        return $this->response($promise);
    }

    /**
     * 查询公告详情
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
            'detail_id' => ['required', 'integer']
        ])->validate();

        $promise = $this->request('GET', '/recruitment-details/'.$params['detail_id']);

        return $this->response($promise);
    }
}
