<?php

namespace App\Libs\ThirdParty\Jucai\Api;

use App\Libs\ThirdParty\ApiRequest;
use Illuminate\Contracts\Container\BindingResolutionException;

class Sms extends ApiRequest
{
    /**
     * 发送短信验证码
     *
     * @throws BindingResolutionException
     */
    public function send(array $config = [], array $data = []): array
    {
        if (! isset($data['mobile'])) {
            throw new \InvalidArgumentException('缺少参数: mobile');
        }
        $params = [
            'query' => $this->makeQueryParams($config),
            'json' => $data,
        ];
        $promise = $this->request('POST', '/index/sms/sendv2', $params);

        return $this->response($promise);
    }

    private function makeQueryParams(array $config = [])
    {
        return [
            'username' => $config['username'],
            'accesstoken' => md5($config['username'].$config['password'].$this->app->config['accesskey']),
        ];
    }
}
