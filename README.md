# 项目准备

## 环境准备
- nodejs 25.8+
- npm 11.11+
- pnpm 10.33+
- php 8.5+
- composer 2.9+

## 项目初始化
`composer install`

## 生成密钥
`php artisan key:generate`

## 生成passport密钥
`php artisan passport:keys --force`

## 数据库准备
`php artisan migrate`

## 数据库填充
`php artisan db:seed`

## 生成超级管理员
`php artisan make:filament-user`

## 生成客户端
`php artisan passport:client --name=牛派B端 --provider=b_users --personal`

## 中台项目构建
`npm install`

## 中台项目运行
`npm run dev`
