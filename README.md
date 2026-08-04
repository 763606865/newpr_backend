# 项目准备

## 产品介绍

本项目是面向招聘、人力资源管理与校企协同场景的一体化后端服务，基于 Laravel 13 构建，覆盖门户内容运营、求职招聘业务、企业组织管理、考勤管理、校招活动、站内沟通、开放数据接口与后台运营管理等核心能力。系统同时服务 C 端求职者、招聘方、学校/校招负责人、企业 B 端管理者和平台运营人员，提供从内容触达、用户认证、职位发布、简历管理、投递流转、面试 Offer 到数据同步的完整业务闭环。

### 业务范围

- **门户 CMS**：支持站点配置、导航菜单、首页聚合、轮播图、广告位、公告、校园资讯、校园活动、推荐位与友情链接等内容运营能力，支持按城市、状态、排序和身份可见性进行内容分发。
- **招聘 C 端**：覆盖用户认证、身份切换、企业资料、职位发布与搜索、简历维护、附件上传、职位/企业/简历收藏、投递管理、面试邀约、Offer 流转、通知中心与 IM 会话。
- **校企活动**：支持学校发起活动、企业报名参与、活动职位关联、展位/展区管理、企业邀请、报名审核、活动发布/结束及活动参与数据查询。
- **企业 B 端**：提供企业、部门、岗位、职工、假期类型、考勤规则、考勤记录等组织管理和基础 HR 管理能力，并结合套餐菜单与功能点实现业务权限控制。
- **运营后台**：基于 Filament 构建管理端，覆盖 CMS、招聘业务、企业组织、系统菜单、功能点、套餐、用户与权限等运营配置。
- **开放接口**：提供 SApi 数据服务接口，支持用户、企业、职位、简历、公告等业务数据对外查询，并通过签名、时间戳、Nonce 等机制控制接口访问安全。

### 技术能力

- **多端认证体系**：基于 Laravel Passport 支持不同用户提供方和客户端，覆盖 B 端、招聘端与开放接口场景，配合可选登录中间件实现游客和登录用户的差异化访问。
- **权限与商业化控制**：通过菜单、功能点、套餐快照和策略体系控制企业侧能力边界，支持按业务套餐下发可用菜单与功能权限。
- **搜索与推荐链路**：使用 Laravel Scout 对简历、职位等核心数据建立搜索索引，结合发现页、推荐策略和收藏行为支撑职位、企业、简历与招聘公告的检索和推荐。
- **异步与定时任务**：使用 Redis 队列与 Horizon 管理异步任务，承载消息通知、统计同步、索引更新、数据迁移等后台处理链路，并通过 Laravel Schedule 执行周期任务。
- **文件与外部服务集成**：封装 OSS 上传与访问地址转换，集成 OCR、AI 简历解析、IM、地图和第三方数据服务，降低业务模块对外部供应商的直接耦合。
- **数据建模与接口规范**：按业务域拆分模型、资源、请求校验、策略和服务类，接口响应统一封装，配套 `docs/` 下的 API 文档和枚举说明，便于多端协作和后续扩展。

## 环境要求
- Node.js >= 25.8
- npm >= 11.11
- pnpm >= 10.33
- PHP >= 8.5
- Composer >= 2.9
- MySQL >= 9.6
- Redis >= 8.6
- Laravel >= 13.3
- Supervisor >= 4.2
- ElasticSearch >= 9.0

## 快速初始化
### 1) 安装后端依赖
```bash
composer install
```

### 2) 生成应用密钥
```bash
php artisan key:generate
```

### 3) 生成 Passport 密钥
```bash
php artisan passport:keys --force
```

### 4) 执行数据库迁移
```bash
php artisan migrate
```

### 5) 填充基础数据
```bash
php artisan db:seed
```

## Docker Compose 部署

### 1) 准备环境变量
```bash
cp .env.docker.example .env.docker
```

编辑 `.env.docker`，至少补齐：
- `APP_KEY`：可先用 `docker compose --env-file .env.docker run --rm app php artisan key:generate --show` 生成后写入
- `APP_URL`：服务器对外访问地址
- `DB_PASSWORD`：MySQL root 密码
- `ELASTIC_PASSWORD`：ElasticSearch `elastic` 用户密码
- OSS、短信、IM、AI 等第三方服务配置

### 2) 构建并启动服务
```bash
docker compose --env-file .env.docker up -d --build
```

服务包含：
- `nginx`：Web 入口，默认映射 `${APP_PORT:-80}`
- `app`：Laravel PHP-FPM
- `horizon`：队列消费
- `scheduler`：Laravel 定时任务
- `mysql`：业务数据库
- `redis`：缓存、Session、队列
- `elasticsearch`：Scout 搜索服务

### 3) 初始化应用
```bash
docker compose --env-file .env.docker exec app php artisan migrate --force
docker compose --env-file .env.docker exec app php artisan passport:keys --force
docker compose --env-file .env.docker exec app php artisan storage:link
docker compose --env-file .env.docker exec app php artisan optimize
```

如需初始化搜索索引：
```bash
docker compose --env-file .env.docker exec app php artisan scout:index "App\Models\Rc\Resume"
docker compose --env-file .env.docker exec app php artisan scout:index "App\Models\Rc\Job"
docker compose --env-file .env.docker exec app php artisan scout:import "App\Models\Rc\Resume"
docker compose --env-file .env.docker exec app php artisan scout:import "App\Models\Rc\Job"
```

### 4) 常用运维命令
```bash
docker compose --env-file .env.docker ps
docker compose --env-file .env.docker logs -f app
docker compose --env-file .env.docker logs -f horizon
docker compose --env-file .env.docker restart app horizon scheduler
```

## 管理员账号说明
> `php artisan make:filament-user` 已弃用（本项目不建议使用）。

该命令默认通过 `Hash::make` 注入密码，在当前项目中可能导致无法登录。

建议直接写入数据库创建管理员，并使用 `bcrypt` 进行密码加密。

## 生成 Passport 客户端
```bash
php artisan passport:client --name="牛派B端" --provider=b_users --personal
```

## 生成 Rc 客户端
```bash
php artisan passport:client --name="招聘C端" --provider=rc_users --personal
```

## 前端（中台）
### 安装依赖
```bash
pnpm install
```

### 启动filament
```bash
pnpm build
```

## 队列（Horizon 模式）
### 1) 安装 Horizon（仅首次）
```bash
php artisan horizon:install
php artisan migrate
```

### 2) Supervisor 配置更新后刷新
```bash
supervisorctl reread
supervisorctl update
supervisorctl restart newpr-backend-horizon:
supervisorctl restart newpr-backend-octane:
supervisorctl status
```

### 3) 状态检查
```bash
php artisan horizon:status
```

> 已使用 Horizon 时，不建议再并行运行 `queue:work` 的常驻进程，避免消费链路混用。

### 定时任务配置
```bash
crontab -e
```

```bash
* * * * * cd /Users/zn/workspace/code/newpr_backend && php artisan schedule:run >> /dev/null 2>&1
```

### ElasticSearch 数据初始化索引
```bash
# 1. 创建索引
php artisan scout:index "App\Models\Rc\Resume"
php artisan scout:index "App\Models\Rc\Job"

# 2. 全量导入数据
php artisan scout:import "App\Models\Rc\Resume"
php artisan scout:import "App\Models\Rc\Job"

# 3. 更新索引
php artisan scout:flush "App\Models\Rc\Resume"
php artisan scout:flush "App\Models\Rc\Job"
```

### 生成Filament模块权限
```bash
# 1.全量
php artisan shield:generate --all --panel=admin --no-interaction
# 2.单个资源
php artisan shield:generate --resource=YourResource --panel=admin --no-interaction
# 3.生成超级管理员权限
php artisan shield:super-admin --user=1 --panel=admin
```
