# Alibaba Cloud OSS 配置指南

## 概述

该项目已配置为使用Alibaba Cloud OSS (Object Storage Service) 作为文件存储后端。所有文件上传（包括广告图片等）都将存储到OSS而不是本地服务器。

## 现有配置

### 环境变量配置

`.env` 文件中的以下设置已配置：

```
FILESYSTEM_DISK=oss
OSS_ACCESS_KEY_ID=your_access_key_id
OSS_ACCESS_KEY_SECRET=your_access_key_secret
OSS_DEFAULT_REGION=oss-cn-hangzhou
OSS_BUCKET=newpr-develop
OSS_ENDPOINT=oss-cn-hangzhou.aliyuncs.com
```

### 文件系统配置

`config/filesystems.php` 中已配置OSS磁盘：

```php
'oss' => [
    'driver' => 'oss',
    'access_key_id' => env('OSS_ACCESS_KEY_ID'),
    'access_key_secret' => env('OSS_ACCESS_KEY_SECRET'),
    'region' => env('OSS_DEFAULT_REGION'),
    'bucket' => env('OSS_BUCKET'),
    'endpoint' => env('OSS_ENDPOINT'),
],
```

### Filament 集成

`AdForm.php` 中的 FileUpload 组件已配置使用OSS：

```php
FileUpload::make('image')
    ->label('图片')
    ->image()
    ->disk('oss')
    ->directory('ads')
    ->visibility('public')
    ->maxSize(10240), // 10MB
```

## 解决上传失败

### 问题诊断

如果上传到OSS失败，通常会显示 `AccessDenied` 错误。这意味着OSS连接正常，但用户没有写入权限。

### 解决步骤

1. **登录Aliyun OSS 控制台**
   - 访问 https://oss.console.aliyun.com

2. **检查Bucket权限**
   - 选择 `newpr-develop` bucket
   - 进入"权限设置" → "Bucket Policy"

3. **配置写入权限**
   - 确保您的访问密钥 (Access Key) 具有以下权限：
     - `oss:PutObject` (上传对象)
     - `oss:GetObject` (读取对象)
     - `oss:DeleteObject` (删除对象)

4. **创建正确的访问策略**

   示例策略 (JSON)：
   ```json
   {
     "Version": "1",
     "Statement": [
       {
         "Effect": "Allow",
         "Principal": "*",
         "Action": [
           "oss:GetObject",
           "oss:PutObject",
           "oss:DeleteObject"
         ],
         "Resource": "arn:oss:newpr-develop/*"
       }
     ]
   }
   ```

5. **验证访问密钥权限**
   - 在RAM (Resource Access Management) 控制台检查您的Access Key
   - 确保关联的用户/角色拥有 `AliyunOSSFullAccess` 或相应的自定义策略

### 测试连接

```bash
# 测试OSS连接和写入权限
php artisan tinker
>>> Storage::disk('oss')->put('test.txt', 'Hello OSS');
>>> Storage::disk('oss')->exists('test.txt');
>>> Storage::disk('oss')->get('test.txt');
>>> Storage::disk('oss')->delete('test.txt');
```

## URL 生成

上传的文件可以通过以下方式访问：

### 在代码中获取URL

```php
use Illuminate\Support\Facades\Storage;

// 获取文件URL
$url = Storage::disk('oss')->url('ads/image.jpg');

// 使用自定义方法
$adapter = Storage::disk('oss')->getAdapter();
$url = $adapter->getUrl('ads/image.jpg');
```

### URL 格式

OSS 文件URL格式为：
```
https://{bucket}.{endpoint}/{path}

例: https://newpr-develop.oss-cn-hangzhou.aliyuncs.com/ads/image.jpg
```

## 高级配置

### CDN加速 (可选)

如果配置了CDN加速，可以在 `.env` 中添加：

```
OSS_CDN_URL=your-cdn-domain.com
```

然后在 `app/Libs/Oss/Adapter/OssAdapter.php` 中修改 `getUrl()` 方法来使用CDN URL。

### 自定义目录结构

在Filament中可以指定不同的目录：

```php
FileUpload::make('image')
    ->disk('oss')
    ->directory('ads/' . now()->format('Y/m/d')) // 按日期组织
    ->directory('user-' . auth()->id() . '-uploads') // 按用户组织
```

## 实施细节

### OSS 适配器

项目使用自定义 Flysystem 适配器实现 OSS 支持：

**文件**: `app/Libs/Oss/Adapter/OssAdapter.php`

该适配器实现了 `League\Flysystem\FilesystemAdapter` 接口，支持：
- 文件上传 (`write`, `writeStream`)
- 文件读取 (`read`, `readStream`)
- 文件删除 (`delete`, `deleteDirectory`)
- 文件元数据 (`fileSize`, `mimeType`, `lastModified`)
- URL 生成 (`getUrl`)

### 服务提供者

**文件**: `app/Libs/Oss/ServiceProvider.php`

在 `bootstrap/providers.php` 中已注册，负责：
- 扩展Laravel Storage Facade
- 初始化OSS客户端
- 创建Filesystem实例

## 常见问题

### Q: 如何切换回本地存储?

A: 在 `.env` 中改为：
```
FILESYSTEM_DISK=public
```

### Q: 上传很慢怎么办?

A: 
1. 考虑配置OSS传输加速
2. 使用CDN加速
3. 检查网络连接到OSS endpoint

### Q: 如何监控存储使用情况?

A: 在 Aliyun OSS 控制台查看：
- Bucket 大小
- 流量统计
- 请求统计

### Q: 如何删除旧文件?

```php
// 在代码中删除
Storage::disk('oss')->delete('path/to/file.jpg');

// 使用OSS控制台或Lifecycle Rules自动清理过期文件
```

## 安全建议

1. **不要在代码中存储明文密钥** - 使用 `.env` 文件
2. **定期轮换Access Keys** - 在RAM控制台管理
3. **限制Access Key权限** - 仅授予必要的权限
4. **启用Bucket加密** - 在OSS控制台配置
5. **使用HTTPS** - 所有文件访问都采用HTTPS

## 故障排除

如果遇到问题，检查以下日志文件：

```bash
tail -f storage/logs/laravel.log
```

常见错误：

```
AccessDenied - 没有写入权限
BadRequest - 请求参数错误
NoSuchBucket - Bucket不存在
NoSuchKey - 文件不存在（读取时）
```

## 参考资源

- [Aliyun OSS 官方文档](https://help.aliyun.com/document_detail/31845.html)
- [Aliyun OSS SDK for PHP](https://github.com/aliyun/aliyun-oss-php-sdk)
- [Laravel Filesystem 文档](https://laravel.com/docs/11.x/filesystem)
- [Flysystem 文档](https://flysystem.thephpleague.com/)

