# AI 简历解析

## 说明

- 路由前缀：`/rc`
- 鉴权：均需 `Bearer Token`，并使用 `auth:rc`
- 当前身份：必须为求职者身份
- 成功响应：`api_response()`，结构为 `code` + `data` + `meta`
- 业务错误：`error()`，结构为 `code` + `message` + `meta`
- AI 简历解析采用异步任务模式，创建任务后前端轮询查询任务状态

### 任务状态

| 状态 | 状态值 | 含义 |
|------|--------|------|
| `Pending` | `0` | 等待解析 |
| `Processing` | `1` | 解析中 |
| `Succeeded` | `2` | 解析成功 |
| `Failed` | `3` | 解析失败 |

### 任务对象

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int | 解析任务 ID |
| `file_url` | string | 附件简历地址 |
| `provider` | string\|null | AI 服务商标识 |
| `status` | string | 状态枚举名称：`Pending` / `Processing` / `Succeeded` / `Failed` |
| `status_value` | int | 状态值 |
| `status_label` | string | 状态中文 |
| `parsed_resume` | object\|null | AI 解析后的简历信息，成功后返回 |
| `error_message` | string\|null | 失败原因 |
| `token_cost` | int | 消耗 token 数，当前为预留字段 |
| `started_at` | string\|null | 开始解析时间 |
| `finished_at` | string\|null | 完成解析时间 |
| `created_at` | string\|null | 创建时间 |
| `updated_at` | string\|null | 更新时间 |

---

## 1) 创建 AI 简历解析任务

- 接口：`POST /rc/ai/resume-parses`
- 描述：求职者提交附件简历地址，服务端创建解析任务并派发后台队列执行 AI 解析

### 请求参数

| 字段 | 是否必填 | 类型/规则 | 说明 |
|------|----------|-----------|------|
| `file_url` | 是 | `url`, `max:2048` | 附件简历地址 |

### 请求示例

```json
{
  "file_url": "https://files.example.com/resumes/resume.pdf"
}
```

```bash
curl -X POST "https://example.com/rc/ai/resume-parses" \
  -H "Authorization: Bearer <access-token>" \
  -H "Content-Type: application/json" \
  -d '{"file_url":"https://files.example.com/resumes/resume.pdf"}'
```

### 成功响应示例

```json
{
  "code": 200,
  "data": {
    "id": 1001,
    "file_url": "https://files.example.com/resumes/resume.pdf",
    "provider": "custom",
    "status": "Pending",
    "status_value": 0,
    "status_label": "等待解析",
    "parsed_resume": null,
    "error_message": null,
    "token_cost": 0,
    "started_at": null,
    "finished_at": null,
    "created_at": "2026-07-18T10:00:00.000000Z",
    "updated_at": "2026-07-18T10:00:00.000000Z"
  },
  "meta": {
    "timestamp": 1784340000.1234,
    "response_time": 0.0123
  }
}
```

### 业务失败响应示例

当前身份不是求职者：

```json
{
  "code": 422,
  "message": "请先切换为求职者身份。",
  "meta": {
    "timestamp": 1784340000.1234,
    "response_time": 0.0123
  }
}
```

---

## 2) 查询 AI 简历解析任务

- 接口：`GET /rc/ai/resume-parses/{id}`
- 描述：查询当前求职者身份创建的 AI 简历解析任务状态和结果

### Path 参数

| 字段 | 是否必填 | 类型/规则 | 说明 |
|------|----------|-----------|------|
| `id` | 是 | int | 解析任务 ID |

### 请求示例

```bash
curl -X GET "https://example.com/rc/ai/resume-parses/1001" \
  -H "Authorization: Bearer <access-token>"
```

### 等待解析响应示例

```json
{
  "code": 200,
  "data": {
    "id": 1001,
    "file_url": "https://files.example.com/resumes/resume.pdf",
    "provider": "custom",
    "status": "Pending",
    "status_value": 0,
    "status_label": "等待解析",
    "parsed_resume": null,
    "error_message": null,
    "token_cost": 0,
    "started_at": null,
    "finished_at": null,
    "created_at": "2026-07-18T10:00:00.000000Z",
    "updated_at": "2026-07-18T10:00:00.000000Z"
  },
  "meta": {
    "timestamp": 1784340000.1234,
    "response_time": 0.0123
  }
}
```

### 解析中响应示例

```json
{
  "code": 200,
  "data": {
    "id": 1001,
    "file_url": "https://files.example.com/resumes/resume.pdf",
    "provider": "custom",
    "status": "Processing",
    "status_value": 1,
    "status_label": "解析中",
    "parsed_resume": null,
    "error_message": null,
    "token_cost": 0,
    "started_at": "2026-07-18T10:00:05.000000Z",
    "finished_at": null,
    "created_at": "2026-07-18T10:00:00.000000Z",
    "updated_at": "2026-07-18T10:00:05.000000Z"
  },
  "meta": {
    "timestamp": 1784340005.1234,
    "response_time": 0.0123
  }
}
```

### 解析成功响应示例

```json
{
  "code": 200,
  "data": {
    "id": 1001,
    "file_url": "https://files.example.com/resumes/resume.pdf",
    "provider": "custom",
    "status": "Succeeded",
    "status_value": 2,
    "status_label": "解析成功",
    "parsed_resume": {
      "name": "张三",
      "phone": "13800138000",
      "email": "zhangsan@example.com",
      "gender": "男",
      "birthday": "2000-01-01",
      "educations": [
        {
          "school": "示例大学",
          "major": "计算机科学与技术",
          "degree": "本科",
          "start_date": "2018-09",
          "end_date": "2022-06"
        }
      ],
      "works": [
        {
          "company": "示例科技有限公司",
          "position": "PHP 开发工程师",
          "start_date": "2022-07",
          "end_date": "2025-06",
          "description": "负责招聘系统后端开发。"
        }
      ],
      "skills": ["PHP", "Laravel", "MySQL"]
    },
    "error_message": null,
    "token_cost": 0,
    "started_at": "2026-07-18T10:00:05.000000Z",
    "finished_at": "2026-07-18T10:00:35.000000Z",
    "created_at": "2026-07-18T10:00:00.000000Z",
    "updated_at": "2026-07-18T10:00:35.000000Z"
  },
  "meta": {
    "timestamp": 1784340035.1234,
    "response_time": 0.0123
  }
}
```

### 解析失败响应示例

```json
{
  "code": 200,
  "data": {
    "id": 1001,
    "file_url": "https://files.example.com/resumes/resume.pdf",
    "provider": "custom",
    "status": "Failed",
    "status_value": 3,
    "status_label": "解析失败",
    "parsed_resume": null,
    "error_message": "AI custom 请求失败：简历文件无法访问。",
    "token_cost": 0,
    "started_at": "2026-07-18T10:00:05.000000Z",
    "finished_at": "2026-07-18T10:00:10.000000Z",
    "created_at": "2026-07-18T10:00:00.000000Z",
    "updated_at": "2026-07-18T10:00:10.000000Z"
  },
  "meta": {
    "timestamp": 1784340010.1234,
    "response_time": 0.0123
  }
}
```

### 业务失败响应示例

任务不存在，或不属于当前求职者身份：

```json
{
  "code": 404,
  "message": "AI 简历解析任务不存在。",
  "meta": {
    "timestamp": 1784340000.1234,
    "response_time": 0.0123
  }
}
```

---

## 前端建议

- 创建任务成功后，每 2-3 秒轮询一次 `GET /rc/ai/resume-parses/{id}`
- `Pending` / `Processing` 时展示解析中状态
- `Succeeded` 时读取 `parsed_resume` 并回填简历表单，由用户确认后保存
- `Failed` 时展示 `error_message`，允许用户重新提交解析任务
