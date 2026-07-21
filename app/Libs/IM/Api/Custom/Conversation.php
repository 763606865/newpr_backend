<?php

namespace App\Libs\IM\Api\Custom;

use App\Libs\IM\Api\AbstractApi;
use App\Libs\IM\IMException;
use Illuminate\Http\Client\ConnectionException;

/**
 * 会话
 */
class Conversation extends AbstractApi
{
    /**
     * 创建会话
     *
     *
     * @throws ConnectionException
     * @throws IMException {
     *                     "type": "group",
     *                     "subject": "项目群",
     *                     "owner_user_id": "u_1001",
     *                     "member_user_ids": ["u_1002", "u_1003"]
     *                     }
     */
    public function store(array $params = []): array
    {
        $payload = $params;
        $json = $this->driver->post('/api/conversations', ['json' => $payload, 'timeout' => 5])->json();

        return $this->handleResponse($json);
    }

    /**
     * 查询历史消息
     *
     *
     * @throws ConnectionException
     * @throws IMException
     */
    public function getMessages(int|string $conversationId, array $params = []): array
    {
        $payload = $params;
        $json = $this->driver->get("/api/conversations/{$conversationId}/messages", ['query' => $payload, 'timeout' => 5])->json();

        return $this->handleResponse($json);
    }

    /**
     * 成员信息
     *
     *
     * @throws ConnectionException
     * @throws IMException
     */
    public function getMembers(int $conversationId, array $params = []): array
    {
        $payload = $params;
        $json = $this->driver->get("/api/conversations/{$conversationId}/members", ['query' => $payload, 'timeout' => 5])->json();

        return $this->handleResponse($json);
    }

    /**
     * 发送消息
     *
     * @throws ConnectionException
     * @throws IMException
     */
    public function postMessage(int|string $conversationId, array $params = []): array
    {
        $payload = $params;
        $json = $this->driver->post("/api/conversations/{$conversationId}/messages", ['json' => $payload, 'timeout' => 5])->json();

        return $this->handleResponse($json);
    }
}
