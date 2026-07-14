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

    /**
     * 简历详情
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
            'resume_id' => ['required', 'integer']
        ])->validate();

        $promise = $this->request('POST', '/resume/detail', $params);

        return $this->response($promise);
    }

    /**
     * 解锁简历
     *
     * @param array $params
     * @return array
     * @throws BadRequestException
     * @throws BindingResolutionException
     * @throws \JsonException
     */
    public function unlock(array $params = []): array
    {
        validator($params, [
            'resume_id' => ['required', 'integer']
        ])->validate();

        $promise = $this->request('POST', '/resume/unlock', $params);

        return $this->response($promise);
    }

    /**
     * 获取附件列表
     *
     * @param array $params
     * @return array
     * @throws BadRequestException
     * @throws BindingResolutionException
     * @throws \JsonException
     */
    public function attachments(array $params = []): array
    {
        validator($params, [
            'resume_id' => ['required', 'integer']
        ])->validate();

        $promise = $this->request('POST', '/resume/attachments', $params);

        return $this->response($promise);
    }

    /**
     * 下载附件
     *
     * @param array $params
     * @return array
     * @throws BadRequestException
     * @throws BindingResolutionException
     * @throws \JsonException
     */
    public function download(array $params = []): array
    {
        validator($params, [
            'resume_id' => ['required', 'integer'],
            'attachment_id' => ['required', 'integer'],
        ])->validate();

        $promise = $this->request('POST', '/resume/download', $params);

        return $this->response($promise);
    }

    /**
     * 字典查询
     *
     * @param array $params
     * @return array
     * @throws BadRequestException
     * @throws BindingResolutionException
     * @throws \JsonException
     */
    public function dict(array $params = []): array
    {
        validator($params, [
            'type' => ['required', 'string'],
        ])->validate();

        $promise = $this->request('POST', '/resume/dict', $params);

        return $this->response($promise);
    }
}
