# 视频上传与房间管理系统

## 简介
这是一个基于 PHP 的视频上传与房间管理系统，允许用户上传视频文件，并支持创建、加入和离开房间，同时提供 WebRTC 信令功能。

## 功能特性
1. **视频上传**：支持大文件分片上传，最大文件大小限制为 3GB，支持 MP4 和 WebM 格式。
2. **视频管理**：可以播放和删除已上传的视频。
3. **房间管理**：用户可以创建房间、加入房间和离开房间。
4. **WebRTC 信令**：支持在房间内发送和接收 WebRTC 信令。

## 文件结构
- `upload.php`：处理视频上传和删除请求，提供视频列表展示。
- `room.php`：处理房间的创建、加入、离开和信令相关操作。
- `rooms.txt`：存储房间信息的 JSON 文件。
- `videos.txt`：存储已上传视频文件名的文本文件。
- `messages.txt`：存储聊天消息的文本文件。
- `progress.txt`：存储视频上传进度的文本文件。

## 安装与配置
1. **环境要求**：PHP 环境，建议 PHP 版本 7.0 及以上。
2. **文件部署**：将所有文件上传到服务器的指定目录。
3. **权限设置**：确保 `uploads` 目录有读写权限，可使用以下命令设置：
```bash
chmod -R 777 uploads
```

## 使用说明

### 视频上传
1. 打开 `upload.php` 页面。
2. 点击“选择视频文件”按钮，选择要上传的视频文件。
3. 点击“开始上传”按钮，开始上传视频。上传过程中会显示上传进度。
4. 上传完成后，页面会自动刷新，已上传的视频会显示在列表中。

### 视频管理
- **播放视频**：在视频列表中，点击“播放”按钮，会跳转到 `watch.php` 页面播放视频。
- **删除视频**：在视频列表中，点击“删除”按钮，确认后会删除视频文件并从 `videos.txt` 中移除记录。

### 房间管理
#### 创建房间
向 `room.php` 发送 POST 请求，参数如下：
- `action`：值为 `create`
- `video`：视频 ID
- `user`：用户 ID

示例代码：
```php
$url = 'room.php';
$data = [
    'action' => 'create',
    'video' => '386f68b5-d8f1-4b81-a174-c907a9e11b3b.mp4',
    'user' => '5due0jsj2hf38106kksjef25c5'
];

$options = [
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query($data)
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
$response = json_decode($result, true);
if ($response['success']) {
    echo '房间创建成功，房间 ID 为：' . $response['roomId'];
} else {
    echo '房间创建失败';
}
```

#### 加入房间
向 `room.php` 发送 POST 请求，参数如下：
- `action`：值为 `join`
- `room`：房间 ID
- `user`：用户 ID

#### 离开房间
向 `room.php` 发送 POST 请求，参数如下：
- `action`：值为 `leave`
- `room`：房间 ID
- `user`：用户 ID

#### 发送信令
向 `room.php` 发送 POST 请求，参数如下：
- `action`：值为 `signal`
- `room`：房间 ID
- `user`：用户 ID
- `signal`：信令数据

#### 获取信令
向 `room.php` 发送 POST 请求，参数如下：
- `action`：值为 `get_signals`
- `room`：房间 ID
- `user`：用户 ID
- `last_time`：上次获取信令的时间

#### 获取房间用户列表
向 `room.php` 发送 POST 请求，参数如下：
- `action`：值为 `get_users`
- `room`：房间 ID

## 注意事项
- 请确保服务器配置允许上传大文件，可在 `upload.php` 中设置 `upload_max_filesize`、`post_max_size` 和 `memory_limit` 等参数。
- 视频文件会存储在 `uploads` 目录下。
- 房间信息和视频信息分别存储在 `rooms.txt` 和 `videos.txt` 文件中，请确保这些文件有读写权限。
