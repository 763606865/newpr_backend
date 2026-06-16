# Meta 数据

## 说明

- 路由前缀：`/rc`
- 鉴权：均需 `Bearer Token`，并且使用 `auth:rc`
- 适用场景：求职者填写简历、编辑简历、前端下拉/树形字典复用
- 返回格式：
  - 成功：`api_response()`，结构为 `code` + `data` + `meta`
  - 业务错误：`error()`，结构为 `code` + `message` + `meta`

## 公共数据结构

### 地区对象（Area）

地区接口统一使用 `RcAreaResource` 输出，树结构字段如下：

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int | 地区 ID |
| `code` | string | 行政区划代码 |
| `parent_code` | string\|null | 父级行政区划代码 |
| `name` | string | 地区名称 |
| `level` | int | 层级，`1`省、`2`市、`3`区县 |
| `type` | string\|null | 类型 |
| `children` | array | 子级地区 |

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

### 专业对象（Major）

专业接口统一使用 `RcMajorResource` 输出：

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int | 专业 ID |
| `full_code` | string | 专业国标编码 |
| `name` | string | 专业名称 |
| `level` | int | 层级，`1`大类、`2`专业类、`3`专业 |
| `level_label` | string | 层级中文标签 |
| `parent_code` | string\|null | 父级国标编码，顶级为 `null` |
| `type` | string | 学历类型：`中职` / `高职专科` / `职教本科` |
| `type_label` | string | 学历类型中文标签 |
| `tag` | string | 扩展标签 |
| `sort` | int | 排序 |
| `children` | array | 子级专业 |

### 学校字典项（School）

学校接口统一输出扁平 `{ value, label }` 结构，用于简历教育经历中的毕业院校选择：

| 字段 | 类型 | 说明 |
|------|------|------|
| `value` | string | 学校代码（`school_code`） |
| `label` | string | 学校名称 |

---

## 1) Meta 汇总

- 接口：`GET /rc/meta`
- 鉴权：Bearer Token（`auth:rc`）
- 描述：一次性返回简历填写所需的地区、行业、职位、专业、学校元数据，以及企业资料字典

### 成功响应示例

```json
{
  "code": 200,
  "data": {
    "areas": [
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
    ],
    "majors": [
      {
        "id": 1,
        "full_code": "55",
        "name": "装备制造大类",
        "level": 1,
        "level_label": "大类",
        "parent_code": null,
        "type": "中职",
        "type_label": "中职",
        "tag": "",
        "sort": 1,
        "children": []
      }
    ],
    "major_levels": [
      { "value": 1, "label": "大类" }
    ],
    "major_education_types": [
      { "value": "中职", "label": "中职" }
    ],
    "company_scales": [
      { "value": 1, "label": "0-20人" }
    ],
    "company_natures": [
      { "value": 1, "label": "民营企业" }
    ],
    "company_funding_stages": [
      { "value": 1, "label": "未融资" }
    ],
    "company_benefit_tags": [
      { "value": "social_insurance", "label": "五险一金" }
    ],
    "schools": [
      { "value": "4111010002", "label": "中国人民大学" },
      { "value": "4111010001", "label": "北京大学" },
      { "value": "4131010003", "label": "复旦大学" }
    ]
  },
  "meta": {
    "timestamp": 1748865600.1234,
    "response_time": 0.0123
  }
}
```

### 规则

- `areas`：来自 `areas` 表
- `industries`：来自 `rc_industries` 表
- `positions`：来自 `rc_positions` 表
- `majors`：来自 `majors` 表，仅返回 `status=1` 的启用数据
- `major_levels` / `major_education_types`：来自 PHP Enum，结构为 `{ value, label }`
- `company_scales` / `company_natures` / `company_funding_stages` / `company_benefit_tags`：来自 PHP Enum，结构为 `{ value, label }`
- `schools`：来自 `schools` 表，仅返回 `school_code` 不为空的记录，结构为 `{ value, label }`
- 地区、行业、职位、专业树结构均通过 `tree()` 生成
- `schools` 为扁平列表，按学校名称升序排列

---

## 2) 地区元数据

- 接口：`GET /rc/meta/areas`
- 鉴权：Bearer Token（`auth:rc`）
- 描述：仅返回地区树，用于简历居住城市、户口所在地等选择

### 成功响应示例

```json
{
  "code": 200,
  "data": {
    "areas": [
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
- 顶层节点为省级数据，子级依次为市、区县（按 `parent_code`/`code` 组树）

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

## 5) 企业资料字典

- 接口：`GET /rc/meta/companies`
- 鉴权：Bearer Token（`auth:rc`）
- 描述：返回企业招聘资料表单所需的规模、性质、融资阶段、福利标签字典

### 成功响应示例

```json
{
  "code": 200,
  "data": {
    "company_scales": [
      { "value": 1, "label": "0-20人" },
      { "value": 2, "label": "20-99人" }
    ],
    "company_natures": [
      { "value": 1, "label": "民营企业" },
      { "value": 2, "label": "国有企业" }
    ],
    "company_funding_stages": [
      { "value": 1, "label": "未融资" },
      { "value": 2, "label": "天使轮" }
    ],
    "company_benefit_tags": [
      { "value": "social_insurance", "label": "五险一金" },
      { "value": "weekend_off", "label": "双休" }
    ]
  },
  "meta": {
    "timestamp": 1748865600.1234,
    "response_time": 0.0123
  }
}
```

### 规则

- 字典项来自 Enum，非数据库表
- `company_scales` / `company_natures` / `company_funding_stages` 的 `value` 为 int
- `company_benefit_tags` 的 `value` 为 string code

---

## 6) 专业元数据

- 接口：`GET /rc/meta/majors`
- 鉴权：Bearer Token（`auth:rc`）
- 描述：返回专业树及学历类型、层级字典，用于简历教育经历中的专业选择

### 成功响应示例

```json
{
  "code": 200,
  "data": {
    "majors": [
      {
        "id": 1,
        "full_code": "55",
        "name": "装备制造大类",
        "level": 1,
        "level_label": "大类",
        "parent_code": null,
        "type": "中职",
        "type_label": "中职",
        "tag": "",
        "sort": 1,
        "children": [
          {
            "id": 2,
            "full_code": "5501",
            "name": "机械设计制造类",
            "level": 2,
            "level_label": "专业类",
            "parent_code": "55",
            "type": "中职",
            "type_label": "中职",
            "tag": "",
            "sort": 1,
            "children": []
          }
        ]
      }
    ],
    "major_levels": [
      { "value": 1, "label": "大类" },
      { "value": 2, "label": "专业类" },
      { "value": 3, "label": "专业" }
    ],
    "major_education_types": [
      { "value": "中职", "label": "中职" },
      { "value": "高职专科", "label": "高职专科" },
      { "value": "职教本科", "label": "职教本科" }
    ]
  },
  "meta": {
    "timestamp": 1748865600.1234,
    "response_time": 0.0123
  }
}
```

### 规则

- 数据来源：`majors` 表
- 仅返回 `status=1` 的启用记录
- 排序：`level asc, sort asc, full_code asc`
- 使用 `RcMajorResource` 序列化
- 树结构通过 `tree()` 按 `parent_code` / `full_code` 组树，顶层为大类（`level=1`）
- 缓存：结果永久缓存，`majors` 表变更时由 `MajorMetaObserver` 自动失效

---

## 7) 学校元数据

- 接口：`GET /rc/meta/schools`
- 鉴权：Bearer Token（`auth:rc`）
- 描述：返回学校扁平字典，用于简历教育经历中的毕业院校选择

### 成功响应示例

```json
{
  "code": 200,
  "data": {
    "schools": [
      { "value": "4111010002", "label": "中国人民大学" },
      { "value": "4111010001", "label": "北京大学" },
      { "value": "4131010003", "label": "复旦大学" }
    ]
  },
  "meta": {
    "timestamp": 1748865600.1234,
    "response_time": 0.0123
  }
}
```

### 规则

- 数据来源：`schools` 表
- 仅返回 `school_code` 不为空的记录
- 排序：按 `name asc`
- 结构为扁平数组，`value` 为学校代码（string），`label` 为学校名称
- 缓存：结果永久缓存

---

## 8) 前端使用建议

- 简历创建页建议优先调用 `GET /rc/meta`
- 企业资料编辑页可调用 `GET /rc/meta/companies` 或直接使用 `GET /rc/meta` 中的企业字典
- 教育经历中的毕业院校选择可调用 `GET /rc/meta/schools` 或直接使用 `GET /rc/meta` 中的 `schools`
- 如果页面只需要单一字典，可分别调用：
  - `GET /rc/meta/areas`
  - `GET /rc/meta/industries`
  - `GET /rc/meta/positions`
  - `GET /rc/meta/majors`
  - `GET /rc/meta/schools`
- 后续“填写简历”接口也可复用这份元数据结构，避免前端重复实现树转换逻辑

