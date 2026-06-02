# Meta 数据

## 说明

- 路由前缀：`/rc`
- 鉴权：均需 `Bearer Token`，并且使用 `auth:rc`
- 适用场景：求职者填写简历、编辑简历、前端下拉/树形字典复用
- 返回格式：
  - 成功：`api_response()`，结构为 `code` + `data` + `meta`
  - 业务错误：`error()`，结构为 `code` + `message` + `meta`

## 公共数据结构

### 城市对象（Area）

城市接口统一使用 `RcAreaResource` 输出，树结构字段如下：

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int | 城市 ID |
| `code` | string | 行政区划代码 |
| `parent_code` | string\|null | 父级行政区划代码 |
| `name` | string | 城市名称 |
| `level` | int | 层级，`1`省、`2`市、`3`区县 |
| `type` | string\|null | 类型 |
| `children` | array | 子级城市 |

### 常用行业对象（Industry）

行业接口统一使用 `RcIndustryResource` 输出：

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int | 行业 ID |
| `parent_id` | int\|null | 父级行业 ID |
| `name` | string | 行业名称 |
| `code` | string | 行业代码 |
| `sort` | int | 排序 |
| `extra` | object\|null | 扩展字段 |
| `children` | array | 子级行业 |

### 常用职位对象（Position）

职位接口统一使用 `RcPositionResource` 输出：

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int | 职位 ID |
| `parent_id` | int\|null | 父级职位 ID |
| `name` | string | 职位名称 |
| `code` | string | 职位代码 |
| `sort` | int | 排序 |
| `extra` | object\|null | 扩展字段 |
| `children` | array | 子级职位 |

---

## 1) Meta 汇总

- 接口：`GET /rc/meta`
- 鉴权：Bearer Token（`auth:rc`）
- 描述：一次性返回简历填写所需的城市、行业、职位元数据

### 成功响应示例

```json
{
  "code": 200,
  "data": {
    "cities": [
      {
        "id": 1,
        "code": "000001",
        "parent_code": null,
        "name": "Province A",
        "level": 1,
        "type": null,
        "children": [
          {
            "id": 2,
            "code": "000001001",
            "parent_code": "000001",
            "name": "City A",
            "level": 2,
            "type": null,
            "children": []
          }
        ]
      }
    ],
    "industries": [
      {
        "id": 10,
        "parent_id": null,
        "name": "Internet/IT",
        "code": "it",
        "sort": 1,
        "extra": null,
        "children": [
          {
            "id": 11,
            "parent_id": 10,
            "name": "E-commerce",
            "code": "ecommerce",
            "sort": 1,
            "extra": null,
            "children": []
          }
        ]
      }
    ],
    "positions": [
      {
        "id": 20,
        "parent_id": null,
        "name": "Engineering",
        "code": "engineering",
        "sort": 1,
        "extra": null,
        "children": [
          {
            "id": 21,
            "parent_id": 20,
            "name": "Backend Developer",
            "code": "backend-dev",
            "sort": 1,
            "extra": null,
            "children": []
          }
        ]
      }
    ]
  },
  "meta": {
    "timestamp": 1748865600.1234,
    "response_time": 0.0123
  }
}
```

### 规则

- `cities`：来自 `areas` 表
- `industries`：来自 `rc_industries` 表
- `positions`：来自 `rc_positions` 表
- 所有树结构均通过 `tree()` 生成

---

## 2) 城市元数据

- 接口：`GET /rc/meta/cities`
- 鉴权：Bearer Token（`auth:rc`）
- 描述：仅返回城市树，用于简历居住城市、户口所在地等选择

### 成功响应示例

```json
{
  "code": 200,
  "data": {
    "cities": [
      {
        "id": 1,
        "code": "000001",
        "parent_code": null,
        "name": "Province A",
        "level": 1,
        "type": null,
        "children": [
          {
            "id": 2,
            "code": "000001001",
            "parent_code": "000001",
            "name": "City A",
            "level": 2,
            "type": null,
            "children": []
          }
        ]
      }
    ]
  },
  "meta": {
    "timestamp": 1748865600.1234,
    "response_time": 0.0123
  }
}
```

### 规则

- 数据来源：`areas`
- 排序：按 `level asc, code asc`
- 使用 `RcAreaResource` 序列化
- 顶层节点为省级数据，子级依次为市、区县

---

## 3) 常用行业元数据

- 接口：`GET /rc/meta/industries`
- 鉴权：Bearer Token（`auth:rc`）
- 描述：仅返回行业树，用于简历期望行业选择

### 成功响应示例

```json
{
  "code": 200,
  "data": {
    "industries": [
      {
        "id": 10,
        "parent_id": null,
        "name": "Internet/IT",
        "code": "it",
        "sort": 1,
        "extra": null,
        "children": [
          {
            "id": 11,
            "parent_id": 10,
            "name": "E-commerce",
            "code": "ecommerce",
            "sort": 1,
            "extra": null,
            "children": []
          }
        ]
      }
    ]
  },
  "meta": {
    "timestamp": 1748865600.1234,
    "response_time": 0.0123
  }
}
```

### 规则

- 数据来源：`rc_industries`
- 排序：`sort asc, id asc`
- 使用 `RcIndustryResource` 序列化
- 返回结构为树状结构

---

## 4) 常用职位元数据

- 接口：`GET /rc/meta/positions`
- 鉴权：Bearer Token（`auth:rc`）
- 描述：仅返回职位树，用于简历期望职位选择

### 成功响应示例

```json
{
  "code": 200,
  "data": {
    "positions": [
      {
        "id": 20,
        "parent_id": null,
        "name": "Engineering",
        "code": "engineering",
        "sort": 1,
        "extra": null,
        "children": [
          {
            "id": 21,
            "parent_id": 20,
            "name": "Backend Developer",
            "code": "backend-dev",
            "sort": 1,
            "extra": null,
            "children": []
          }
        ]
      }
    ]
  },
  "meta": {
    "timestamp": 1748865600.1234,
    "response_time": 0.0123
  }
}
```

### 规则

- 数据来源：`rc_positions`
- 排序：`sort asc, id asc`
- 使用 `RcPositionResource` 序列化
- 返回结构为树状结构

---

## 5) 前端使用建议

- 简历创建页建议优先调用 `GET /rc/meta`
- 如果页面只需要单一字典，可分别调用：
  - `GET /rc/meta/cities`
  - `GET /rc/meta/industries`
  - `GET /rc/meta/positions`
- 后续“填写简历”接口也可复用这份元数据结构，避免前端重复实现树转换逻辑

