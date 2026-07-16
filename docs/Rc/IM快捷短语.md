# IM 快捷短语

## 说明

- 路由前缀：`/rc`
- 鉴权：均需 `Bearer Token`，并使用 `auth:rc`
- 成功响应：`api_response()`，结构为 `code` + `data` + `meta`
- 业务错误：`error()`，结构为 `code` + `message` + `meta`
- 数据归属：快捷短语绑定当前身份对应的 `rc_user_ims.id`，只能查询、修改、删除自己的快捷短语

---

## 公共数据结构

### 快捷短语对象

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int | 快捷短语 ID |
| `user_im_id` | int | 关联的 `rc_user_ims.id` |
| `title` | string\|null | 短语标题 |
| `content` | string | 快捷短语内容 |
| `sort` | int | 排序值，越大越靠前 |
| `is_enabled` | bool | 是否启用 |
| `used_count` | int | 使用次数 |
| `last_used_at` | string\|null | 最后使用时间 |
| `created_at` | string\|null | 创建时间 |
| `updated_at` | string\|null | 更新时间 |

---

## 1) 快捷短语列表

- 接口：`GET /rc/im/quick-phrases`
- 描述：返回当前身份的快捷短语列表

### Query 参数

| 字段 | 是否必填 | 类型/规则 | 说明 |
|------|----------|-----------|------|
| `page` | 否 | int | 页码 |
| `per_page` | 否 | int | 每页条数，默认 15，最大 100 |
| `keyword` | 否 | string | 按标题或内容模糊检索 |
| `is_enabled` | 否 | bool | 是否只返回启用或禁用的短语 |

### 请求示例

```bash
curl -X GET "https://example.com/rc/im/quick-phrases?is_enabled=1&per_page=15" \
  -H "Authorization: Bearer <access-token>"
```

### 成功响应示例

```json
{
  "code": 200,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_im_id": 10,
        "title": "职位咨询",
        "content": "您好，我想进一步了解一下这个职位。",
        "sort": 100,
        "is_enabled": true,
        "used_count": 3,
        "last_used_at": "2026-07-16T08:00:00.000000Z",
        "created_at": "2026-07-16T07:30:00.000000Z",
        "updated_at": "2026-07-16T08:00:00.000000Z"
      }
    ],
    "per_page": 15,
    "total": 1
  },
  "meta": {
    "timestamp": 1784109600.1234,
    "response_time": 0.0123
  }
}
```

---

## 2) 新增快捷短语

- 接口：`POST /rc/im/quick-phrases`
- 描述：为当前身份新增一条快捷短语

### 请求参数

| 字段 | 是否必填 | 类型/规则 | 说明 |
|------|----------|-----------|------|
| `title` | 否 | nullable, string, max:64 | 短语标题 |
| `content` | 是 | string, max:1000 | 快捷短语内容 |
| `sort` | 否 | integer, min:0 | 排序值，默认 0 |
| `is_enabled` | 否 | bool | 是否启用，默认 true |

### 请求示例

```json
{
  "title": "职位咨询",
  "content": "您好，我想进一步了解一下这个职位。",
  "sort": 100,
  "is_enabled": true
}
```

### 成功响应示例

```json
{
  "code": 200,
  "data": {
    "quick_phrase": {
      "id": 1,
      "user_im_id": 10,
      "title": "职位咨询",
      "content": "您好，我想进一步了解一下这个职位。",
      "sort": 100,
      "is_enabled": true,
      "used_count": 0,
      "last_used_at": null,
      "created_at": "2026-07-16T07:30:00.000000Z",
      "updated_at": "2026-07-16T07:30:00.000000Z"
    }
  },
  "meta": {
    "timestamp": 1784109600.1234,
    "response_time": 0.0123
  }
}
```

---

## 3) 快捷短语详情

- 接口：`GET /rc/im/quick-phrases/{id}`
- 描述：查看当前身份自己的快捷短语

### 成功响应示例

```json
{
  "code": 200,
  "data": {
    "quick_phrase": {
      "id": 1,
      "user_im_id": 10,
      "title": "职位咨询",
      "content": "您好，我想进一步了解一下这个职位。",
      "sort": 100,
      "is_enabled": true,
      "used_count": 0,
      "last_used_at": null,
      "created_at": "2026-07-16T07:30:00.000000Z",
      "updated_at": "2026-07-16T07:30:00.000000Z"
    }
  },
  "meta": {
    "timestamp": 1784109600.1234,
    "response_time": 0.0123
  }
}
```

### 业务失败响应示例

```json
{
  "code": 404,
  "message": "快捷短语不存在。",
  "meta": {
    "timestamp": 1784109600.1234,
    "response_time": 0.0123
  }
}
```

---

## 4) 更新快捷短语

- 接口：`PUT /rc/im/quick-phrases/{id}`
- 接口：`PATCH /rc/im/quick-phrases/{id}`
- 描述：更新当前身份自己的快捷短语

### 请求参数

| 字段 | 是否必填 | 类型/规则 | 说明 |
|------|----------|-----------|------|
| `title` | 否 | nullable, string, max:64 | 短语标题 |
| `content` | 否 | string, max:1000 | 快捷短语内容 |
| `sort` | 否 | integer, min:0 | 排序值 |
| `is_enabled` | 否 | bool | 是否启用 |

### 请求示例

```json
{
  "content": "您好，我对这个岗位很感兴趣，方便进一步沟通吗？",
  "sort": 120
}
```

### 成功响应示例

```json
{
  "code": 200,
  "data": {
    "quick_phrase": {
      "id": 1,
      "user_im_id": 10,
      "title": "职位咨询",
      "content": "您好，我对这个岗位很感兴趣，方便进一步沟通吗？",
      "sort": 120,
      "is_enabled": true,
      "used_count": 0,
      "last_used_at": null,
      "created_at": "2026-07-16T07:30:00.000000Z",
      "updated_at": "2026-07-16T08:10:00.000000Z"
    }
  },
  "meta": {
    "timestamp": 1784109600.1234,
    "response_time": 0.0123
  }
}
```

---

## 5) 删除快捷短语

- 接口：`DELETE /rc/im/quick-phrases/{id}`
- 描述：删除当前身份自己的快捷短语

### 成功响应示例

```json
{
  "code": 200,
  "data": {},
  "meta": {
    "timestamp": 1784109600.1234,
    "response_time": 0.0123
  }
}
```
