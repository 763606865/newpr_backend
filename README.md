# 项目准备

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

## 管理员账号说明
> `php artisan make:filament-user` 已弃用（本项目不建议使用）。

该命令默认通过 `Hash::make` 注入密码，在当前项目中可能导致无法登录。

建议直接写入数据库创建管理员，并使用 `bcrypt` 进行密码加密。

## 生成 Passport 客户端 @deprecated 已使用 `php artisan db:seed` 填充了基础数据，其中包括 Passport 客户端的创建，因此不再需要手动执行以下命令。
```bash
php artisan passport:client --name="牛派B端" --provider=b_users --personal
```

## 前端（中台）
### 安装依赖
```bash
npm install
```

### 启动开发模式
```bash
npm run dev
```
