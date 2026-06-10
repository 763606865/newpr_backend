# Meta 数据

## 说明

- 路由前缀：`/cms`
- 鉴权：无
- 适用场景：门户站点城市切换、高校校园侧专业选择、按城市筛选内容（如 `GET /cms/home?city_code=...`）
- 返回格式：
  - 成功：`api_response()`，结构为 `code` + `data` + `meta`
  - 业务错误：`error()`，结构为 `code` + `message` + `meta`

> 求职者端简历填写所需的完整元数据（地区、行业、职位）请使用 `GET /rc/meta`，详见 [Meta.md](../Rc/Meta.md)。

## 公共数据结构

### 地区对象（Area）

地区接口统一使用 `RcAreaResource` 输出，树结构字段如下：

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int | 地区 ID |
| `code` | string | 行政区划代码 |
| `parent_code` | string\|null | 父级行政区划代码 |
| `name` | string | 地区名称 |
| `level` | int | 层级，`1` 省、`2` 市、`3` 区县 |
| `type` | string\|null | 类型 |
| `created_at` | string\|null | 创建时间 |
| `updated_at` | string\|null | 更新时间 |
| `children` | array | 子级地区 |

### 专业对象（Major）

专业接口统一使用 `RcMajorResource` 输出，字段说明见 [Rc/Meta.md](../Rc/Meta.md#专业对象major)。

---

## 1) 地区元数据

- 接口：`GET /cms/meta`
- 鉴权：无
- Query 参数：无
- 描述：返回全国行政区划树及专业树，供门户前端城市选择器、校园侧专业选择器使用

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
        "created_at": "2026-05-29 10:00:00",
        "updated_at": "2026-05-29 10:00:00",
        "children": [
          {
            "id": 2,
            "code": "000001001",
            "parent_code": "000001",
            "name": "City A",
            "level": 2,
            "type": null,
            "created_at": "2026-05-29 10:00:00",
            "updated_at": "2026-05-29 10:00:00",
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
    ]
  },
  "meta": {
    "timestamp": 1748865600.1234,
    "response_time": 0.0123
  }
}
```

### 规则

- 数据来源：`areas` 表
- 排序：按 `level asc, code asc`
- 序列化：使用 `RcAreaResource`
- 树结构：通过 `tree()` 按 `parent_code` / `code` 组树，顶层为省级数据，子级依次为市、区县
- 缓存：地区结果永久缓存（`Cache::rememberForever`），`areas` 表变更时由 `AreaMetaObserver` 自动失效
- `majors`：来自 `majors` 表，仅返回启用数据；缓存策略同上，由 `MajorMetaObserver` 失效

---

## 2) 专业元数据

- 接口：`GET /cms/meta/majors`
- 鉴权：无
- Query 参数：无
- 描述：仅返回专业树及学历类型、层级字典

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
        "children": []
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

- 数据来源与序列化规则同 `GET /rc/meta/majors`
- 无需登录，适合门户与校园侧公共字典加载

---

## 3) 前端使用建议

- 门户首页、公告页等接口的 `city_code` 参数，建议取本接口返回的**市级**节点 `code`
- 首次进入站点时可调用 `GET /cms/meta` 加载城市树与专业树，本地缓存后复用
- 若页面仅需专业字典，可调用 `GET /cms/meta/majors`
- 若页面仅需行业或职位字典，可分别调用：
  - `GET /cms/home/rc/industries`
  - `GET /cms/home/rc/positions`
