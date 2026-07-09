<?php

namespace App\Libs\ThirdParty\JucaiDT\Api;

use App\Libs\Exceptions\BadRequestException;
use Illuminate\Contracts\Container\BindingResolutionException;

class Resume extends ApiRequest
{
    /**
     * 获取简历列表
     *
     * @throws BadRequestException
     * @throws BindingResolutionException
     * @throws \JsonException
     */
    public function list(array $params = []): array
    {
        $promise = $this->request('POST', '/resume/mylist', $params);

        return $this->response($promise);
    }
}
