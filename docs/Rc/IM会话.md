# IM 会话

## 说明

- 路由前缀：`/rc`
- 鉴权：均需 `Bearer Token`，并使用 `auth:rc`
- 成功响应：`api_response()`，结构为 `code` + `data` + `meta`
- 业务错误：`error()`，结构为 `code` + `message` + `meta`
- 当前身份：由 Token 绑定的 `rc_user_identities` 记录决定
- 当前身份会自动注册/同步到 IM，并作为会话创建者 `owner_user_id`

### `external_user_id`

前端从推荐职位、推荐简历等接口拿到被动方的 `external_user_id` 后，调用创建会话接口传回。

- `external_user_id` 由 `rc_user_identities.id` 经 Sqids 编码生成
- 当前实现最小长度为 32 位
- 前端不需要解析该字段，只需要原样传回

### 会话类型

| 值 | 含义 | 初始化成员规则 |
|----|------|----------------|
| `single` | 单聊 | `members` 必须且只能传 1 个成员；发起人不在 `members` 中 |
| `group` | 群聊 | `members` 至少传 1 个成员；发起人不在 `members` 中 |
| `chatroom` | 聊天室 | 不允许传初始化成员 |
| `live_room` | 直播间 | 不允许传初始化成员 |

### 会话去重规则

服务端会按会话类型和成员集合生成 `conversation_key`：

- 成员集合包含发起人和 `members` 中的所有成员
- 成员按 `rc_user_im:{id}` 排序后生成稳定 key
- 如果已存在同一 `conversation_key`，直接返回已有会话

---

## 公共数据结构

### 会话对象

创建会话接口返回 `ImConversationResource`。

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int | 本地会话 ID |
| `provider` | string\|null | IM 服务提供商 |
| `app_code` | string\|null | IM 应用编码 |
| `conversation_no` | string | IM 后台返回的会话唯一标识 |
| `conversation_type` | string\|null | 会话类型：`single` / `group` / `chatroom` / `live_room` |
| `conversation_type_label` | string\|null | 会话类型中文 |
| `conversation_key` | string\|null | 业务侧会话去重 key |
| `owner_type` | string | 创建者多态类型，当前为 `rc_user_im` |
| `owner_id` | int | 创建者 `rc_user_ims.id` |
| `scene` | string\|null | 业务场景，手动创建为 `manual` |
| `metadata` | object\|null | 扩展数据 |
| `last_message_at` | string\|null | 最后一条消息时间 |
| `expires_at` | string\|null | 过期时间 |
| `created_at` | string\|null | 创建时间 |
| `updated_at` | string\|null | 更新时间 |
| `members` | array | 会话成员列表，包含成员关系和成员 IM 用户信息 |
| `participants` | array | 会话全部参与者的 IM 用户信息 |
| `other_participants` | array | 除当前身份外，其他参与者的 IM 用户信息；聊天室左侧会话列表优先使用该字段展示头像、姓名等信息 |

### 会话成员对象

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int | 本地会话成员 ID |
| `member_type` | string | 成员多态类型，当前为 `rc_user_im` |
| `member_id` | int | 成员 `rc_user_ims.id` |
| `role` | string\|null | 成员角色：`owner` / `member` |
| `joined_at` | string\|null | 加入时间 |
| `last_read_at` | string\|null | 最后已读时间 |
| `settings` | object\|null | 成员配置 |
| `member` | object\|null | 成员 IM 用户信息 |

### 成员 IM 用户对象

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int | `rc_user_ims.id` |
| `user_id` | int | 用户 ID |
| `user_identity_id` | int | 身份 ID |
| `identity_type` | int/null | 身份类型 |
| `provider` | string | IM 服务提供商 |
| `app_code` | string\|null | IM 应用编码 |
| `external_user_id` | string\|null | 业务侧 IM 用户 ID |
| `im_user_id` | string\|null | IM 后台返回的用户 ID |
| `user` | object\|null | 用户基础信息 |
| `identity` | object\|null | 招聘业务身份信息 |

### 用户基础对象

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int | 用户 ID |
| `name` | string\|null | 用户名称 |
| `nickname` | string\|null | 用户昵称 |
| `mask_name` | string\|null | 脱敏展示名称 |
| `avatar` | string\|null | 原始头像 |
| `display_avatar` | string\|null | 展示头像 |

### 身份对象

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int | `rc_user_identities.id` |
| `identity_type` | int/null | 身份类型 |
| `identity_name` | string\|null | 身份名称 |
| `organization_type` | string\|null | 组织类型 |
| `organization_id` | int/null | 组织 ID |
| `organization_name` | string\|null | 组织名称 |
| `job_title` | string\|null | 职位名称 |

---

## 1) 会话列表

- 接口：`GET /rc/im/conversations`
- 描述：返回当前身份作为成员参与的 IM 会话列表

### Query 参数

| 字段 | 是否必填 | 类型/规则 | 说明 |
|------|----------|-----------|------|
| `per_page` | 否 | int | 每页条数，默认 15，最大 100 |

### 请求示例

```http
GET /rc/im/conversations?per_page=15
Authorization: Bearer {token}
```

### 成功响应示例

> `data.data[*]` 为会话对象。聊天室左侧列表展示对方信息时，建议使用 `other_participants[0].user.display_avatar` 和 `other_participants[0].user.mask_name` / `nickname` / `name`。

```json
{
  "code": 200,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 12,
        "provider": "custom",
        "app_code": "rc",
        "conversation_no": "c_10001",
        "conversation_type": "single",
        "conversation_type_label": "单聊",
        "conversation_key": "single:rc_user_im:10|rc_user_im:28",
        "owner_type": "rc_user_im",
        "owner_id": 10,
        "scene": "manual",
        "metadata": {
          "subject": null
        },
        "last_message_at": null,
        "expires_at": null,
        "created_at": "2026-07-15T10:00:00.000000Z",
        "updated_at": "2026-07-15T10:00:00.000000Z",
        "members": [
          {
            "id": 100,
            "member_type": "rc_user_im",
            "member_id": 10,
            "role": "owner",
            "joined_at": "2026-07-15T10:00:00.000000Z",
            "last_read_at": null,
            "settings": null,
            "member": {
              "id": 10,
              "user_id": 5,
              "user_identity_id": 7,
              "identity_type": 2,
              "provider": "custom",
              "app_code": "rc",
              "external_user_id": "9QzXwErTyUiOpAsDfGhJkLmNbVcCxZa",
              "im_user_id": "u_1001",
              "user": {
                "id": 5,
                "name": "杭州星河科技有限公司",
                "nickname": "星河招聘",
                "mask_name": "星河招聘",
                "avatar": "https://example.com/avatar/recruiter.png",
                "display_avatar": "https://example.com/avatar/recruiter.png"
              },
              "identity": {
                "id": 7,
                "identity_type": 2,
                "identity_name": "招聘方",
                "organization_type": "company",
                "organization_id": 3,
                "organization_name": "杭州星河科技有限公司",
                "job_title": "HR"
              }
            }
          },
          {
            "id": 101,
            "member_type": "rc_user_im",
            "member_id": 28,
            "role": "member",
            "joined_at": "2026-07-15T10:00:00.000000Z",
            "last_read_at": null,
            "settings": null,
            "member": {
              "id": 28,
              "user_id": 18,
              "user_identity_id": 30,
              "identity_type": 1,
              "provider": "custom",
              "app_code": "rc",
              "external_user_id": "8K3mQxYp9aV2nL0sR7tBcD4eFgHiJkLm",
              "im_user_id": "u_1002",
              "user": {
                "id": 18,
                "name": "张三",
                "nickname": "张同学",
                "mask_name": "张*",
                "avatar": "https://example.com/avatar/jobseeker.png",
                "display_avatar": "https://example.com/avatar/jobseeker.png"
              },
              "identity": {
                "id": 30,
                "identity_type": 1,
                "identity_name": "求职者",
                "organization_type": null,
                "organization_id": null,
                "organization_name": null,
                "job_title": null
              }
            }
          }
        ],
        "participants": [
          {
            "id": 10,
            "user_id": 5,
            "user_identity_id": 7,
            "identity_type": 2,
            "provider": "custom",
            "app_code": "rc",
            "external_user_id": "9QzXwErTyUiOpAsDfGhJkLmNbVcCxZa",
            "im_user_id": "u_1001",
            "user": {
              "id": 5,
              "name": "杭州星河科技有限公司",
              "nickname": "星河招聘",
              "mask_name": "星河招聘",
              "avatar": "https://example.com/avatar/recruiter.png",
              "display_avatar": "https://example.com/avatar/recruiter.png"
            },
            "identity": {
              "id": 7,
              "identity_type": 2,
              "identity_name": "招聘方",
              "organization_type": "company",
              "organization_id": 3,
              "organization_name": "杭州星河科技有限公司",
              "job_title": "HR"
            }
          },
          {
            "id": 28,
            "user_id": 18,
            "user_identity_id": 30,
            "identity_type": 1,
            "provider": "custom",
            "app_code": "rc",
            "external_user_id": "8K3mQxYp9aV2nL0sR7tBcD4eFgHiJkLm",
            "im_user_id": "u_1002",
            "user": {
              "id": 18,
              "name": "张三",
              "nickname": "张同学",
              "mask_name": "张*",
              "avatar": "https://example.com/avatar/jobseeker.png",
              "display_avatar": "https://example.com/avatar/jobseeker.png"
            },
            "identity": {
              "id": 30,
              "identity_type": 1,
              "identity_name": "求职者",
              "organization_type": null,
              "organization_id": null,
              "organization_name": null,
              "job_title": null
            }
          }
        ],
        "other_participants": [
          {
            "id": 28,
            "user_id": 18,
            "user_identity_id": 30,
            "identity_type": 1,
            "provider": "custom",
            "app_code": "rc",
            "external_user_id": "8K3mQxYp9aV2nL0sR7tBcD4eFgHiJkLm",
            "im_user_id": "u_1002",
            "user": {
              "id": 18,
              "name": "张三",
              "nickname": "张同学",
              "mask_name": "张*",
              "avatar": "https://example.com/avatar/jobseeker.png",
              "display_avatar": "https://example.com/avatar/jobseeker.png"
            },
            "identity": {
              "id": 30,
              "identity_type": 1,
              "identity_name": "求职者",
              "organization_type": null,
              "organization_id": null,
              "organization_name": null,
              "job_title": null
            }
          }
        ]
      }
    ],
    "first_page_url": "https://example.com/rc/im/conversations?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "https://example.com/rc/im/conversations?page=1",
    "links": [],
    "next_page_url": null,
    "path": "https://example.com/rc/im/conversations",
    "per_page": 15,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  },
  "meta": {
    "timestamp": 1784109600.1234,
    "response_time": 0.0123
  }
}
```

---

## 2) 创建/获取会话

- 接口：`POST /rc/im/conversations`
- 描述：创建 IM 会话；如果相同成员集合的会话已存在，则直接返回已有会话

### 请求参数

| 字段 | 是否必填 | 类型/规则 | 说明 |
|------|----------|-----------|------|
| `type` | 是 | enum | 会话类型：`single` / `group` / `chatroom` / `live_room` |
| `subject` | 否 | string, max:255 | 会话标题，通常群聊使用 |
| `members` | 否 | array | 初始化成员列表，不包含发起人 |
| `members.*.external_user_id` | 是 | string, max:64 | 被动方身份的外部 IM 用户 ID |

### 成员规则

- `single`：`members` 必须包含且只能包含 1 个成员
- `group`：`members` 至少包含 1 个成员
- `chatroom` / `live_room`：不允许传 `members`
- 发起人由当前 Token 身份确定，不需要放入 `members`

### 单聊请求示例

```http
POST /rc/im/conversations
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "type": "single",
  "members": [
    {
      "external_user_id": "8K3mQxYp9aV2nL0sR7tBcD4eFgHiJkLm"
    }
  ]
}
```

### 群聊请求示例

```json
{
  "type": "group",
  "subject": "项目沟通群",
  "members": [
    {
      "external_user_id": "8K3mQxYp9aV2nL0sR7tBcD4eFgHiJkLm"
    },
    {
      "external_user_id": "2bN6cPqRsT9uVwXyZaBcDeFgHiJkLmNo"
    }
  ]
}
```

### 聊天室请求示例

```json
{
  "type": "chatroom",
  "subject": "宣讲会聊天室"
}
```

### 成功响应示例

```json
{
  "code": 200,
  "data": {
    "id": 12,
    "provider": "custom",
    "app_code": "rc",
    "conversation_no": "c_10001",
    "conversation_type": "single",
    "conversation_type_label": "单聊",
    "conversation_key": "single:rc_user_im:10|rc_user_im:28",
    "owner_type": "rc_user_im",
    "owner_id": 10,
    "scene": "manual",
    "metadata": {
      "subject": null,
      "provider_response": {
        "id": "c_10001"
      }
    },
    "last_message_at": null,
    "expires_at": null,
    "created_at": "2026-07-15T10:00:00.000000Z",
    "updated_at": "2026-07-15T10:00:00.000000Z",
    "members": [
      {
        "id": 100,
        "member_type": "rc_user_im",
        "member_id": 10,
        "role": "owner",
        "joined_at": "2026-07-15T10:00:00.000000Z",
        "last_read_at": null,
        "settings": null,
        "member": {
          "id": 10,
          "user_id": 5,
          "user_identity_id": 7,
          "identity_type": 2,
          "provider": "custom",
          "app_code": "rc",
          "external_user_id": "9QzXwErTyUiOpAsDfGhJkLmNbVcCxZa",
          "im_user_id": "u_1001",
          "user": {
            "id": 5,
            "name": "杭州星河科技有限公司",
            "nickname": "星河招聘",
            "mask_name": "星河招聘",
            "avatar": "https://example.com/avatar/recruiter.png",
            "display_avatar": "https://example.com/avatar/recruiter.png"
          },
          "identity": {
            "id": 7,
            "identity_type": 2,
            "identity_name": "招聘方",
            "organization_type": "company",
            "organization_id": 3,
            "organization_name": "杭州星河科技有限公司",
            "job_title": "HR"
          }
        }
      },
      {
        "id": 101,
        "member_type": "rc_user_im",
        "member_id": 28,
        "role": "member",
        "joined_at": "2026-07-15T10:00:00.000000Z",
        "last_read_at": null,
        "settings": null,
        "member": {
          "id": 28,
          "user_id": 18,
          "user_identity_id": 30,
          "identity_type": 1,
          "provider": "custom",
          "app_code": "rc",
          "external_user_id": "8K3mQxYp9aV2nL0sR7tBcD4eFgHiJkLm",
          "im_user_id": "u_1002",
          "user": {
            "id": 18,
            "name": "张三",
            "nickname": "张同学",
            "mask_name": "张*",
            "avatar": "https://example.com/avatar/jobseeker.png",
            "display_avatar": "https://example.com/avatar/jobseeker.png"
          },
          "identity": {
            "id": 30,
            "identity_type": 1,
            "identity_name": "求职者",
            "organization_type": null,
            "organization_id": null,
            "organization_name": null,
            "job_title": null
          }
        }
      }
    ],
    "participants": [
      {
        "id": 10,
        "user_id": 5,
        "user_identity_id": 7,
        "identity_type": 2,
        "provider": "custom",
        "app_code": "rc",
        "external_user_id": "9QzXwErTyUiOpAsDfGhJkLmNbVcCxZa",
        "im_user_id": "u_1001",
        "user": {
          "id": 5,
          "name": "杭州星河科技有限公司",
          "nickname": "星河招聘",
          "mask_name": "星河招聘",
          "avatar": "https://example.com/avatar/recruiter.png",
          "display_avatar": "https://example.com/avatar/recruiter.png"
        },
        "identity": {
          "id": 7,
          "identity_type": 2,
          "identity_name": "招聘方",
          "organization_type": "company",
          "organization_id": 3,
          "organization_name": "杭州星河科技有限公司",
          "job_title": "HR"
        }
      },
      {
        "id": 28,
        "user_id": 18,
        "user_identity_id": 30,
        "identity_type": 1,
        "provider": "custom",
        "app_code": "rc",
        "external_user_id": "8K3mQxYp9aV2nL0sR7tBcD4eFgHiJkLm",
        "im_user_id": "u_1002",
        "user": {
          "id": 18,
          "name": "张三",
          "nickname": "张同学",
          "mask_name": "张*",
          "avatar": "https://example.com/avatar/jobseeker.png",
          "display_avatar": "https://example.com/avatar/jobseeker.png"
        },
        "identity": {
          "id": 30,
          "identity_type": 1,
          "identity_name": "求职者",
          "organization_type": null,
          "organization_id": null,
          "organization_name": null,
          "job_title": null
        }
      }
    ],
    "other_participants": [
      {
        "id": 28,
        "user_id": 18,
        "user_identity_id": 30,
        "identity_type": 1,
        "provider": "custom",
        "app_code": "rc",
        "external_user_id": "8K3mQxYp9aV2nL0sR7tBcD4eFgHiJkLm",
        "im_user_id": "u_1002",
        "user": {
          "id": 18,
          "name": "张三",
          "nickname": "张同学",
          "mask_name": "张*",
          "avatar": "https://example.com/avatar/jobseeker.png",
          "display_avatar": "https://example.com/avatar/jobseeker.png"
        },
        "identity": {
          "id": 30,
          "identity_type": 1,
          "identity_name": "求职者",
          "organization_type": null,
          "organization_id": null,
          "organization_name": null,
          "job_title": null
        }
      }
    ]
  },
  "meta": {
    "timestamp": 1784109600.1234,
    "response_time": 0.0123
  }
}
```

### 业务错误示例

**单聊未传成员或成员数量不为 1**

```json
{
  "code": 422,
  "message": "单聊会话只能初始化一名成员。",
  "meta": {
    "timestamp": 1784109600.1234,
    "response_time": 0.0123
  }
}
```

**聊天室/直播间传了初始化成员**

```json
{
  "code": 422,
  "message": "聊天室和直播间不允许初始化成员。",
  "meta": {
    "timestamp": 1784109600.1234,
    "response_time": 0.0123
  }
}
```

**成员外部 ID 无效**

```json
{
  "code": 422,
  "message": "成员 IM 用户标识无效。",
  "meta": {
    "timestamp": 1784109600.1234,
    "response_time": 0.0123
  }
}
```

---

## 3) 获取会话历史消息

- 接口：`GET /rc/im/conversations/{id}/messages`
- 描述：获取某一个会话的历史消息。`{id}` 为本地会话 ID，即 `im_conversations.id`
- 权限：当前身份必须是该会话成员，否则返回会话不存在
- 数据来源：服务端会使用本地会话的 `conversation_no` 调用 IM 后台 `Im::conversation()->getMessages()`

### Path 参数

| 字段 | 是否必填 | 类型/规则 | 说明 |
|------|----------|-----------|------|
| `id` | 是 | int | 本地会话 ID，来自会话列表接口返回的 `data.data[*].id` |

### Query 参数

> Query 参数会原样透传给 IM 后台历史消息接口，具体分页字段以 IM 后台实现为准。

| 字段 | 是否必填 | 类型/规则 | 说明 |
|------|----------|-----------|------|
| `limit` | 否 | int | 本次拉取条数 |
| `cursor` | 否 | string | 分页游标 |
| `before_message_id` | 否 | string | 拉取指定消息之前的历史消息 |
| `after_message_id` | 否 | string | 拉取指定消息之后的消息 |

### 请求示例

```http
GET /rc/im/conversations/12/messages?limit=20&cursor=eyJpZCI6MTAwM30
Authorization: Bearer {token}
```

### 成功响应示例

> `data` 为 IM 后台历史消息接口返回的数据，业务侧不重新包装消息对象字段，仅外层统一使用 `api_response()`。

```json
{
  "code": 200,
  "data": {
    "data": [
      {
        "id": "m_10003",
        "conversation_id": "c_10001",
        "sender_user_id": "u_1002",
        "message_type": "text",
        "content": {
          "text": "您好，我想了解一下这个岗位。"
        },
        "created_at": "2026-07-15T10:06:00.000000Z"
      },
      {
        "id": "m_10002",
        "conversation_id": "c_10001",
        "sender_user_id": "u_1001",
        "message_type": "text",
        "content": {
          "text": "您好，可以直接在这里沟通。"
        },
        "created_at": "2026-07-15T10:05:00.000000Z"
      }
    ],
    "next_cursor": "eyJpZCI6MTAwMn0",
    "has_more": true
  },
  "meta": {
    "timestamp": 1784109600.1234,
    "response_time": 0.0123
  }
}
```

### 业务错误示例

**会话不存在或当前身份不是该会话成员**

```json
{
  "code": 404,
  "message": "会话不存在。",
  "meta": {
    "timestamp": 1784109600.1234,
    "response_time": 0.0123
  }
}
```

**IM 后台异常或连接失败**

```json
{
  "code": 502,
  "message": "IM API Error: Unknown error",
  "meta": {
    "timestamp": 1784109600.1234,
    "response_time": 0.0123
  }
}
```

---

## 4) 发送业务卡片消息

- 接口：`POST /rc/im/conversations/{id}/card-messages`
- 描述：向指定会话发送业务卡片消息，例如换电话、投递简历、邀请面试、发 Offer、拒绝、举报、不感兴趣等
- 权限：当前身份必须是该会话成员，否则返回会话不存在
- 数据来源：服务端会使用本地会话的 `conversation_no` 调用 IM 后台 `Im::conversation()->postMessage()`

> 该接口只负责把已完成或待展示的业务动作发送为 IM 卡片消息。投递、邀请面试、发 Offer、举报等业务状态变更，应优先调用对应业务接口完成，再调用本接口发送卡片，避免前端伪造业务状态。

### Path 参数

| 字段 | 是否必填 | 类型/规则 | 说明 |
|------|----------|-----------|------|
| `id` | 是 | int | 本地会话 ID，来自会话列表接口返回的 `data.data[*].id` |

### 请求参数

| 字段 | 类型 | 是否必填 | 说明 |
|------|------|----------|------|
| `card_type` | string | 是 | 卡片类型，见 [ImBusinessCardType](#imbusinesscardtype) |
| `title` | string\|null | 否 | 卡片标题；为空时使用卡片类型默认标题 |
| `summary` | string\|null | 否 | 卡片摘要，最大 500 字符 |
| `biz` | object\|null | 否 | 业务引用 ID，用于跳转和刷新详情 |
| `biz.application_id` | int/null | 否 | 投递记录 ID |
| `biz.job_id` | int/null | 否 | 职位 ID |
| `biz.resume_id` | int/null | 否 | 简历 ID |
| `biz.interview_id` | int/null | 否 | 面试记录 ID |
| `biz.offer_id` | int/null | 否 | Offer ID |
| `biz.report_id` | int/null | 否 | 举报记录 ID |
| `snapshot` | object\|null | 否 | 发送时的展示快照，例如职位名称、公司名称、面试时间 |
| `metadata` | object\|null | 否 | 扩展数据，会透传给 IM 后台并追加发送者身份信息 |

### 身份和卡片类型

| 发送方 | `card_type` | 默认标题 |
|--------|-------------|----------|
| 招聘方 | `recruiter_exchange_phone` | 换电话 |
| 招聘方 | `recruiter_invite_interview` | 邀请面试 |
| 招聘方 | `recruiter_send_offer` | 发Offer |
| 招聘方 | `recruiter_reject` | 拒绝 |
| 求职者 | `jobseeker_exchange_phone` | 换电话 |
| 求职者 | `jobseeker_apply_resume` | 投递简历 |
| 求职者 | `jobseeker_report` | 举报 |
| 求职者 | `jobseeker_not_interested` | 不感兴趣 |

### 请求示例：招聘方邀请面试卡片

```http
POST /rc/im/conversations/12/card-messages
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "card_type": "recruiter_invite_interview",
  "summary": "南昌示例科技有限公司邀请你参加后端工程师面试",
  "biz": {
    "application_id": 88,
    "job_id": 12,
    "resume_id": 45,
    "interview_id": 1001
  },
  "snapshot": {
    "company_name": "南昌示例科技有限公司",
    "job_title": "后端工程师",
    "interview_at": "2026-07-22 10:00:00",
    "interview_mode": "视频面试"
  }
}
```

### 请求示例：求职者投递简历卡片

```json
{
  "card_type": "jobseeker_apply_resume",
  "summary": "张同学投递了「后端工程师」职位",
  "biz": {
    "application_id": 88,
    "job_id": 12,
    "resume_id": 45
  },
  "snapshot": {
    "job_title": "后端工程师",
    "resume_title": "张同学的简历",
    "education_level_label": "本科",
    "work_years": 2
  }
}
```

### 后端推送给 IM 的消息结构

业务后端会组装以下 payload 调用 IM 后台：

```json
{
  "user_id": "8K3mQxYp9aV2nL0sR7tBcD4eFgHiJkLm",
  "message_type": "business_card",
  "content": {
    "card_type": "jobseeker_apply_resume",
    "card_type_label": "投递简历",
    "title": "投递简历",
    "summary": "张同学投递了「后端工程师」职位",
    "biz": {
      "application_id": 88,
      "job_id": 12,
      "resume_id": 45
    },
    "snapshot": {
      "job_title": "后端工程师",
      "resume_title": "张同学的简历"
    }
  },
  "metadata": {
    "sender_user_im_id": 28,
    "sender_user_identity_id": 30
  }
}
```

### 成功响应示例

```json
{
  "code": 200,
  "data": {
    "message": {
      "id": "m_10010",
      "conversation_id": "c_10001",
      "message_type": "business_card",
      "created_at": "2026-07-21T12:00:00.000000Z"
    },
    "card": {
      "card_type": "recruiter_invite_interview",
      "card_type_label": "邀请面试",
      "title": "邀请面试",
      "summary": "南昌示例科技有限公司邀请你参加后端工程师面试",
      "biz": {
        "application_id": 88,
        "job_id": 12,
        "resume_id": 45,
        "interview_id": 1001
      },
      "snapshot": {
        "company_name": "南昌示例科技有限公司",
        "job_title": "后端工程师",
        "interview_at": "2026-07-22 10:00:00"
      }
    }
  },
  "meta": {
    "timestamp": 1784616000.1234,
    "response_time": 0.0123
  }
}
```

### 业务错误示例

**会话不存在或当前身份不是该会话成员**

```json
{
  "code": 404,
  "message": "会话不存在。",
  "meta": {
    "timestamp": 1784616000.1234,
    "response_time": 0.0123
  }
}
```

**当前身份不能发送该卡片**

```json
{
  "code": 422,
  "message": "当前身份不可发送该卡片。",
  "meta": {
    "timestamp": 1784616000.1234,
    "response_time": 0.0123
  }
}
```

**IM 后台异常或连接失败**

```json
{
  "code": 502,
  "message": "IM API Error: Unknown error",
  "meta": {
    "timestamp": 1784616000.1234,
    "response_time": 0.0123
  }
}
```

---

## 5) IM 交互请求

- 创建接口：`POST /rc/im/interaction-requests`
- 处理接口：`POST /rc/im/interaction-requests/{id}/respond`
- 描述：用于需要接收方确认的 IM 交互场景，例如交换联系方式。发起后 IM 会发送一条可操作卡片；接收方同意或拒绝后，后端执行业务动作并发送处理结果消息。
- 权限：发起方和接收方都必须是同一个会话成员；只有接收方可以处理请求
- 数据来源：服务端会使用本地会话的 `conversation_no` 调用 IM 后台 `Im::conversation()->postMessage()`

> 交互请求用于“需要对方点击同意 / 拒绝”的场景；普通业务展示仍使用 [发送业务卡片消息](#4-发送业务卡片消息)。交换联系方式时，手机号由服务端从双方用户资料读取并写入结果快照，前端不应自行拼接手机号。

### 交互请求对象

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int | 交互请求 ID |
| `conversation_id` | int | 本地会话 ID |
| `sender_user_im_id` | int | 发起方 IM 用户 ID |
| `receiver_user_im_id` | int | 接收方 IM 用户 ID |
| `type` | string | 请求类型，见 [ImInteractionRequestType](#iminteractionrequesttype) |
| `type_label` | string\|null | 请求类型中文 |
| `status` | string | 请求状态，见 [ImInteractionRequestStatus](#iminteractionrequeststatus) |
| `status_label` | string\|null | 请求状态中文 |
| `payload` | object\|null | 发起时的业务参数快照 |
| `result_payload` | object\|null | 同意 / 拒绝后的结果快照 |
| `responded_at` | string\|null | 处理时间 |
| `expires_at` | string\|null | 过期时间 |
| `created_at` | string\|null | 创建时间 |
| `updated_at` | string\|null | 更新时间 |

### 5.1 创建交互请求

#### 请求参数

| 字段 | 类型/规则 | 是否必填 | 说明 |
|------|-----------|----------|------|
| `conversation_id` | int, exists:`im_conversations.id` | 是 | 本地会话 ID |
| `receiver_user_im_id` | int, exists:`rc_user_ims.id` | 是 | 接收方 IM 用户 ID，必须是该会话成员 |
| `type` | string | 是 | 请求类型，当前支持 `exchange_contact`、`respond_interview_invitation`、`respond_offer` |
| `payload` | object\|null | 否 | 业务扩展参数 |
| `payload.application_id` | int | 条件必填 | `respond_interview_invitation`、`respond_offer` 必填，表示投递记录 ID |
| `expires_at` | datetime, after now | 否 | 过期时间 |

#### 请求示例：交换联系方式

```http
POST /rc/im/interaction-requests
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "conversation_id": 12,
  "receiver_user_im_id": 28,
  "type": "exchange_contact"
}
```

#### 请求示例：处理面试邀请

```json
{
  "conversation_id": 12,
  "receiver_user_im_id": 28,
  "type": "respond_interview_invitation",
  "payload": {
    "application_id": 88,
    "interview_id": 1001
  }
}
```

#### 请求示例：处理 Offer

```json
{
  "conversation_id": 12,
  "receiver_user_im_id": 28,
  "type": "respond_offer",
  "payload": {
    "application_id": 88,
    "offer_id": 2001
  }
}
```

#### 后端推送给 IM 的请求卡片消息结构

```json
{
  "sender_user_id": "8K3mQxYp9aV2nL0sR7tBcD4eFgHiJkLm",
  "message_type": "interaction_request",
  "content": {
    "interaction_request_id": 1001,
    "type": "exchange_contact",
    "type_label": "交换联系方式",
    "title": "交换联系方式",
    "summary": "对方希望与你交换手机号。",
    "status": "pending",
    "actions": ["accept", "reject"],
    "payload": {}
  },
  "metadata": {
    "source": "im_interaction_request",
    "interaction_request_id": 1001,
    "sender_user_im_id": 10,
    "receiver_user_im_id": 28
  }
}
```

#### 成功响应示例

```json
{
  "code": 200,
  "data": {
    "interaction_request": {
      "id": 1001,
      "conversation_id": 12,
      "sender_user_im_id": 10,
      "receiver_user_im_id": 28,
      "type": "exchange_contact",
      "type_label": "交换联系方式",
      "status": "pending",
      "status_label": "待处理",
      "payload": [],
      "result_payload": null,
      "responded_at": null,
      "expires_at": null,
      "created_at": "2026-07-23T11:00:00.000000Z",
      "updated_at": "2026-07-23T11:00:00.000000Z"
    },
    "message": {
      "id": "m_10020",
      "conversation_id": "c_10001",
      "message_type": "interaction_request",
      "created_at": "2026-07-23T11:00:00.000000Z"
    },
    "card": {
      "interaction_request_id": 1001,
      "type": "exchange_contact",
      "type_label": "交换联系方式",
      "title": "交换联系方式",
      "summary": "对方希望与你交换手机号。",
      "status": "pending",
      "actions": ["accept", "reject"],
      "payload": []
    }
  },
  "meta": {
    "timestamp": 1784785200.1234,
    "response_time": 0.0123
  }
}
```

### 5.2 处理交互请求

#### Path 参数

| 字段 | 是否必填 | 类型/规则 | 说明 |
|------|----------|-----------|------|
| `id` | 是 | int | 交互请求 ID |

#### 请求参数

| 字段 | 类型/规则 | 是否必填 | 说明 |
|------|-----------|----------|------|
| `action` | string, `accept` / `reject` | 是 | 处理动作 |
| `reason` | string\|null, max:500 | 否 | 拒绝原因；仅拒绝时使用 |

#### 请求示例：同意

```http
POST /rc/im/interaction-requests/1001/respond
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
  "action": "accept"
}
```

#### 请求示例：拒绝

```json
{
  "action": "reject",
  "reason": "暂不方便"
}
```

#### 返回规则

- 只有 `receiver_user_im_id` 对应的当前身份可以处理请求
- `pending` 状态下点击同意：状态变为 `accepted`，执行对应业务动作；面试邀请会调用接受面试逻辑，Offer 会调用接受 Offer 逻辑
- `pending` 状态下点击拒绝：状态变为 `rejected`，写入拒绝原因；面试邀请会调用拒绝面试逻辑，Offer 会调用拒绝 Offer 逻辑
- 非 `pending` 状态重复点击：接口幂等返回当前请求，不重复发送结果消息
- 已过期请求：状态变为 `expired`，返回业务错误 `422`

#### 交换联系方式同意后的结果快照

| 字段 | 类型 | 说明 |
|------|------|------|
| `contacts` | array | 双方联系方式 |
| `contacts.*.user_im_id` | int | IM 用户 ID |
| `contacts.*.user_identity_id` | int\null | 身份 ID |
| `contacts.*.phone` | string | 手机号 |

#### 面试邀请 / Offer 处理后的结果快照

| 字段 | 类型 | 说明 |
|------|------|------|
| `application` | object | 处理后的投递摘要 |
| `application.id` | int | 投递记录 ID |
| `application.company_id` | int | 企业 ID |
| `application.job_id` | int | 职位 ID |
| `application.resume_id` | int | 简历 ID |
| `application.candidate_user_id` | int | 候选人用户 ID |
| `application.status` | int | 投递状态 |
| `application.status_label` | string\|null | 投递状态中文 |
| `reason` | string\|null | 拒绝原因或备注 |

#### 后端推送给 IM 的结果消息结构

```json
{
  "sender_user_id": "receiver-external-user-id",
  "message_type": "interaction_result",
  "client_msg_id": "im_interaction_request_1001_accepted",
  "content": {
    "interaction_request_id": 1001,
    "type": "exchange_contact",
    "type_label": "交换联系方式",
    "title": "交换联系方式",
    "status": "accepted",
    "status_label": "已同意",
    "result": {
      "contacts": [
        {
          "user_im_id": 10,
          "user_identity_id": 30,
          "phone": "13800000001"
        },
        {
          "user_im_id": 28,
          "user_identity_id": 45,
          "phone": "13900000002"
        }
      ]
    }
  },
  "metadata": {
    "source": "im_interaction_result",
    "interaction_request_id": 1001,
    "sender_user_im_id": 10,
    "receiver_user_im_id": 28,
    "actor_user_im_id": 28
  }
}
```

#### 成功响应示例：同意交换联系方式

```json
{
  "code": 200,
  "data": {
    "interaction_request": {
      "id": 1001,
      "conversation_id": 12,
      "sender_user_im_id": 10,
      "receiver_user_im_id": 28,
      "type": "exchange_contact",
      "type_label": "交换联系方式",
      "status": "accepted",
      "status_label": "已同意",
      "payload": [],
      "result_payload": {
        "contacts": [
          {
            "user_im_id": 10,
            "user_identity_id": 30,
            "phone": "13800000001"
          },
          {
            "user_im_id": 28,
            "user_identity_id": 45,
            "phone": "13900000002"
          }
        ]
      },
      "responded_at": "2026-07-23T11:05:00.000000Z",
      "expires_at": null,
      "created_at": "2026-07-23T11:00:00.000000Z",
      "updated_at": "2026-07-23T11:05:00.000000Z"
    },
    "message": {
      "id": "m_10021",
      "conversation_id": "c_10001",
      "message_type": "interaction_result",
      "created_at": "2026-07-23T11:05:00.000000Z"
    },
    "card": {
      "interaction_request_id": 1001,
      "type": "exchange_contact",
      "type_label": "交换联系方式",
      "title": "交换联系方式",
      "status": "accepted",
      "status_label": "已同意",
      "result": {
        "contacts": [
          {
            "user_im_id": 10,
            "user_identity_id": 30,
            "phone": "13800000001"
          },
          {
            "user_im_id": 28,
            "user_identity_id": 45,
            "phone": "13900000002"
          }
        ]
      }
    }
  },
  "meta": {
    "timestamp": 1784785500.1234,
    "response_time": 0.0123
  }
}
```

#### 成功响应示例：拒绝

```json
{
  "code": 200,
  "data": {
    "interaction_request": {
      "id": 1001,
      "conversation_id": 12,
      "sender_user_im_id": 10,
      "receiver_user_im_id": 28,
      "type": "exchange_contact",
      "type_label": "交换联系方式",
      "status": "rejected",
      "status_label": "已拒绝",
      "payload": [],
      "result_payload": {
        "reason": "暂不方便"
      },
      "responded_at": "2026-07-23T11:05:00.000000Z",
      "expires_at": null,
      "created_at": "2026-07-23T11:00:00.000000Z",
      "updated_at": "2026-07-23T11:05:00.000000Z"
    },
    "message": {
      "id": "m_10022",
      "conversation_id": "c_10001",
      "message_type": "interaction_result"
    },
    "card": {
      "interaction_request_id": 1001,
      "type": "exchange_contact",
      "type_label": "交换联系方式",
      "title": "交换联系方式",
      "status": "rejected",
      "status_label": "已拒绝",
      "result": {
        "reason": "暂不方便"
      }
    }
  },
  "meta": {
    "timestamp": 1784785500.1234,
    "response_time": 0.0123
  }
}
```

### 业务错误示例

**会话不存在或当前身份不是该会话成员**

```json
{
  "code": 422,
  "message": "会话不存在。",
  "meta": {
    "timestamp": 1784785200.1234,
    "response_time": 0.0123
  }
}
```

**接收方不在当前会话中**

```json
{
  "code": 422,
  "message": "接收方不在当前会话中。",
  "meta": {
    "timestamp": 1784785200.1234,
    "response_time": 0.0123
  }
}
```

**只有接收方可以处理请求**

```json
{
  "code": 422,
  "message": "只有接收方可以处理该请求。",
  "meta": {
    "timestamp": 1784785500.1234,
    "response_time": 0.0123
  }
}
```

**双方手机号不完整**

```json
{
  "code": 422,
  "message": "双方手机号不完整，无法交换联系方式。",
  "meta": {
    "timestamp": 1784785500.1234,
    "response_time": 0.0123
  }
}
```

---

## 枚举

### `ImBusinessCardType`

| 值 | 发送方 | 说明 |
|----|--------|------|
| `recruiter_exchange_phone` | 招聘方 | 换电话 |
| `recruiter_invite_interview` | 招聘方 | 邀请面试 |
| `recruiter_send_offer` | 招聘方 | 发Offer |
| `recruiter_reject` | 招聘方 | 拒绝 |
| `jobseeker_exchange_phone` | 求职者 | 换电话 |
| `jobseeker_apply_resume` | 求职者 | 投递简历 |
| `jobseeker_report` | 求职者 | 举报 |
| `jobseeker_not_interested` | 求职者 | 不感兴趣 |

### `ImInteractionRequestType`

| 值 | 说明 |
|----|------|
| `exchange_contact` | 交换联系方式 |
| `respond_interview_invitation` | 处理面试邀请，同意时复用 `POST /rc/applications/{id}/accept-interview` 业务逻辑，拒绝时复用 `POST /rc/applications/{id}/reject-interview` 业务逻辑 |
| `respond_offer` | 处理 Offer，同意时复用 `POST /rc/applications/{id}/accept-offer` 业务逻辑，拒绝时复用 `POST /rc/applications/{id}/reject-offer` 业务逻辑 |

### `ImInteractionRequestStatus`

| 值 | 说明 |
|----|------|
| `pending` | 待处理 |
| `accepted` | 已同意 |
| `rejected` | 已拒绝 |
| `expired` | 已过期 |
| `cancelled` | 已取消 |
