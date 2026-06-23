# Meta 数据

## 说明

- 路由前缀：`/cms`
- 鉴权：无
- 适用场景：门户站点城市切换、高校校园侧专业选择、公告标签筛选、按城市筛选内容（如 `GET /cms/home?city_code=...`）
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

### 标签对象（Tag）

标签叶子节点统一使用 `SApiTagResource` 输出，字段如下：

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int | 标签 ID，用于 `GET /cms/announcements` 的 `tag_ids` 参数 |
| `category` | string | 标签分类编码，如 `rc`、`exam_recruitment` |
| `name` | string | 标签名称 |
| `slug` | string\|null | 标签别名 |

### 标签分类节点（Tag Group）

标签树按分类分组，每个分组节点字段如下：

| 字段 | 类型 | 说明 |
|------|------|------|
| `category` | string | 分类编码 |
| `category_label` | string | 分类展示名称 |
| `children` | array | 该分类下的标签列表，元素为标签对象 |

### 校园资讯分类对象（Article Category）

分类树节点统一使用 `CmsArticleCategoryResource` 输出，字段如下：

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int | 分类 ID，用于 `GET /cms/articles` 的 `category_id` 参数 |
| `parent_id` | int | 父级分类 ID，根节点为 `0` |
| `name` | string | 分类名称 |
| `slug` | string\|null | 分类别名，用于 `category_slug` 参数 |
| `cover` | string\|null | 封面 OSS 路径 |
| `display_cover` | string\|null | 封面可访问 URL |
| `description` | string\|null | 分类描述 |
| `sort` | int | 排序 |
| `children` | array | 子级分类 |

### 校园资讯标签对象（Article Tag）

标签列表统一使用 `CmsArticleTagResource` 输出，字段如下：

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | int | 标签 ID，用于 `GET /cms/articles` 的 `tag_ids` 参数 |
| `name` | string | 标签名称 |
| `slug` | string\|null | 标签别名 |
| `sort` | int | 排序 |

---

## 1) 汇总元数据

- 接口：`GET /cms/meta`
- 鉴权：无
- Query 参数：无
- 描述：返回全国行政区划树、专业树、标签树及对应字典，供门户前端初始化公共选择器使用

### 成功响应示例

```json
{
  "code": 200,
  "data": {
    "areas": [
      {
        "id": 1,
        "code": "360000",
        "parent_code": "000000",
        "name": "江西省",
        "level": 1,
        "type": null,
        "created_at": "2026-05-29 10:00:00",
        "updated_at": "2026-05-29 10:00:00",
        "children": [
          {
            "id": 2,
            "code": "360100",
            "parent_code": "360000",
            "name": "南昌市",
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
    "tags": [
      {
        "category": "rc",
        "category_label": "招聘类型 (rc)",
        "children": [
          {
            "id": 1,
            "category": "rc",
            "name": "校招",
            "slug": "campus-recruitment"
          }
        ]
      },
      {
        "category": "school_exam",
        "category_label": "学校考试 (school_exam)",
        "children": [
          {
            "id": 2,
            "category": "school_exam",
            "name": "高考",
            "slug": "gaokao"
          }
        ]
      }
    ],
    "major_levels": [
      { "value": 1, "label": "大类" }
    ],
    "major_education_types": [
      { "value": "中职", "label": "中职" }
    ],
    "tag_categories": [
      { "value": "rc", "label": "招聘类型 (rc)" },
      { "value": "exam_recruitment", "label": "招考 (exam_recruitment)" },
      { "value": "school_exam", "label": "学校考试 (school_exam)" },
      { "value": "certificate_exam", "label": "证书考试 (certificate_exam)" }
    ],
    "announcement_publisher_types": [
      { "value": 0, "label": "系统" },
      { "value": 4, "label": "政府机关" },
      { "value": 5, "label": "银行" },
      { "value": 6, "label": "学校" }
    ],
    "article_categories": [
      {
        "id": 1,
        "parent_id": 0,
        "name": "校园资讯",
        "slug": "campus-news",
        "cover": null,
        "display_cover": null,
        "description": null,
        "sort": 1,
        "children": [
          {
            "id": 2,
            "parent_id": 1,
            "name": "就业动态",
            "slug": "employment-news",
            "cover": null,
            "display_cover": null,
            "description": null,
            "sort": 1,
            "children": []
          }
        ]
      }
    ],
    "article_tags": [
      {
        "id": 1,
        "name": "校招",
        "slug": "campus-recruitment",
        "sort": 1
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

- `areas`：来自 `areas` 表；排序 `level asc, code asc`；序列化使用 `RcAreaResource`；通过 `tree()` 按 `parent_code` / `code` 组树，顶层为省级（`level = 1`），子级依次为市、区县
- `areas` 缓存：永久缓存（`Cache::rememberForever`），`areas` 表变更时由 `AreaMetaObserver` 自动失效
- `majors`：来自 `majors` 表，仅返回启用数据；缓存策略同上，由 `MajorMetaObserver` 失效
- `tags`：来自 `cms_tags` 表，仅返回启用数据；按 `category asc, sort asc, id asc` 排序；按分类分组后输出
- `tags` 缓存：永久缓存，`cms_tags` 表变更时由 `TagMetaObserver` 自动失效
- `article_categories`：来自 `cms_article_categories` 表，仅返回启用数据；按 `sort asc, id asc` 排序；通过 `tree()` 按 `parent_id` / `id` 组树
- `article_categories` 缓存：永久缓存，`cms_article_categories` 变更时由 `ArticleCategoryMetaObserver` 自动失效
- `article_tags`：来自 `cms_article_tags` 表，仅返回启用数据；按 `sort asc, id asc` 排序
- `article_tags` 缓存：永久缓存，`cms_article_tags` 变更时由 `ArticleTagMetaObserver` 自动失效
- `tag_categories`：来自 `CmsTagCategory` 枚举，供前端展示分类名称
- `announcement_publisher_types`：来自 `CmsAnnouncementPublisherType` 枚举，供公告列表 `publisher_types` 筛选参数取值

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

## 3) 标签元数据

- 接口：`GET /cms/meta/tags`
- 鉴权：无
- Query 参数：无
- 描述：仅返回按分类分组的标签树及标签分类字典，供公告页标签筛选器使用

### 成功响应示例

```json
{
  "code": 200,
  "data": {
    "tags": [
      {
        "category": "rc",
        "category_label": "招聘类型 (rc)",
        "children": [
          {
            "id": 1,
            "category": "rc",
            "name": "校招",
            "slug": "campus-recruitment"
          },
          {
            "id": 2,
            "category": "rc",
            "name": "社招",
            "slug": "social-recruitment"
          }
        ]
      },
      {
        "category": "exam_recruitment",
        "category_label": "招考 (exam_recruitment)",
        "children": [
          {
            "id": 3,
            "category": "exam_recruitment",
            "name": "国家公务员",
            "slug": "national-civil-service"
          }
        ]
      }
    ],
    "tag_categories": [
      { "value": "rc", "label": "招聘类型 (rc)" },
      { "value": "exam", "label": "考试 (exam)" },
      { "value": "exam_recruitment", "label": "招考 (exam_recruitment)" },
      { "value": "school_exam", "label": "学校考试 (school_exam)" },
      { "value": "certificate_exam", "label": "证书考试 (certificate_exam)" }
    ]
  },
  "meta": {
    "timestamp": 1748865600.1234,
    "response_time": 0.0123
  }
}
```

### 规则

- 数据来源：`cms_tags` 表，仅返回 `status = 启用` 且未软删除的标签
- 序列化：叶子节点使用 `SApiTagResource`
- 树结构：一级为分类节点（`category` + `category_label`），`children` 为该分类下的标签列表
- 缓存：永久缓存，`cms_tags` 变更时由 `TagMetaObserver` 自动失效
- 公告列表按标签筛选时，将选中标签的 `id` 作为 `tag_ids` 传给 `GET /cms/announcements`
- 公告列表按发布人类型筛选时，将选中类型的 `value` 作为 `publisher_types` 传给 `GET /cms/announcements`

---

## 4) 校园资讯元数据

- 接口：`GET /cms/meta/articles`
- 鉴权：无
- Query 参数：无
- 描述：返回校园资讯分类树与标签列表，供资讯栏目筛选器使用

### 成功响应示例

```json
{
  "code": 200,
  "data": {
    "article_categories": [
      {
        "id": 1,
        "parent_id": 0,
        "name": "校园资讯",
        "slug": "campus-news",
        "cover": null,
        "display_cover": null,
        "description": null,
        "sort": 1,
        "children": []
      }
    ],
    "article_tags": [
      {
        "id": 1,
        "name": "校招",
        "slug": "campus-recruitment",
        "sort": 1
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

- 数据来源：`cms_article_categories`、`cms_article_tags`，仅返回 `status = 启用` 且未软删除的记录
- 分类输出树结构，标签输出扁平列表
- 缓存：永久缓存，表变更时由对应 Observer 自动失效
- 资讯列表按分类筛选时，使用分类 `id` 作为 `category_id`，或使用 `slug` 作为 `category_slug` 传给 `GET /cms/articles`
- 资讯列表按标签筛选时，使用标签 `id` 组成 `tag_ids`，详见 [校园资讯.md](./校园资讯.md)

---

## 5) 前端使用建议

- 门户首页、公告页等接口的 `city_code` 参数，建议取 `areas` 树中**市级**节点 `code`
- 首次进入站点时可调用 `GET /cms/meta` 加载地区、专业、标签字典，本地缓存后复用
- 若页面仅需专业字典，可调用 `GET /cms/meta/majors`
- 若页面仅需标签字典（如公告筛选），可调用 `GET /cms/meta/tags`
- 若页面仅需校园资讯分类/标签字典，可调用 `GET /cms/meta/articles`
- 公告列表按标签筛选时，使用标签 `id` 组成 `tag_ids`，详见 [公告页.md](./公告页.md)
- 公告列表按发布人类型筛选时，使用 `announcement_publisher_types` 中的 `value` 组成 `publisher_types`
- 若页面仅需行业或职位字典，可分别调用：
  - `GET /cms/home/rc/industries`
  - `GET /cms/home/rc/positions`
