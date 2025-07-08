<?php
session_start();
require_once 'room.php';

$messages_file = "messages.txt";
$progress_file = "progress.txt";

// 读取视频列表
$videos = file_exists("videos.txt") ? file("videos.txt") : [];
$videos = array_map('trim', $videos);

// 获取指定的视频
$selected_video = isset($_GET['video']) ? $_GET['video'] : '';

// 添加房间ID支持
$room_id = isset($_GET['room']) ? $_GET['room'] : '';
$user_id = session_id();

// 如果没有房间ID，创建新房间
if (!$room_id && $selected_video) {
    $room = new Room();
    $room_id = $room->createRoom($selected_video, $user_id);
    header("Location: watch.php?room=$room_id&video=$selected_video");
    exit;
}

// 加入现有房间
if ($room_id) {
    $room = new Room();
    $room->joinRoom($room_id, $user_id);
}

// 保存进度
if(isset($_POST['progress']) && isset($_POST['video'])) {
    file_put_contents($progress_file, $_POST['video'] . ":" . $_POST['progress']);
    exit;
}

// 获取进度
if(isset($_GET['get_progress'])) {
    echo file_get_contents($progress_file);
    exit;
}

// 保存消息
if(isset($_POST['message']) && isset($_POST['username'])) {
    $message = date('Y-m-d H:i:s') . " " . $_POST['username'] . ": " . $_POST['message'] . "\n";
    file_put_contents($messages_file, $message, FILE_APPEND);
    exit;
}

// 获取消息
if(isset($_GET['get_messages'])) {
    echo file_get_contents($messages_file);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>同步观看</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Microsoft YaHei', Arial, sans-serif;
            background: #f5f7fa;
            color: #2c3e50;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #34495e;
            font-size: 28px;
        }

        .video-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        #videoSelect {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            color: #2c3e50;
            background: #f8f9fa;
        }

        video {
            width: 100%;
            max-height: 600px;
            border-radius: 5px;
            background: #000;
        }

        .chat-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
        }

        .chat-container {
            height: 300px;
            border: 1px solid #ecf0f1;
            border-radius: 5px;
            overflow-y: scroll;
            padding: 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
        }

        .chat-container::-webkit-scrollbar {
            width: 8px;
        }

        .chat-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .chat-container::-webkit-scrollbar-thumb {
            background: #bdc3c7;
            border-radius: 4px;
        }

        .chat-container::-webkit-scrollbar-thumb:hover {
            background: #95a5a6;
        }

        .message {
            margin-bottom: 10px;
            padding: 8px 12px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .message-time {
            color: #7f8c8d;
            font-size: 0.8em;
        }

        .message-user {
            color: #3498db;
            font-weight: bold;
        }

        .message-content {
            color: #2c3e50;
        }

        .input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .message-input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .message-input:focus {
            outline: none;
            border-color: #3498db;
        }

        .nav-link {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
            margin-top: 20px;
        }

        .nav-link:hover {
            background: #2980b9;
        }

        @media (max-width: 768px) {
            .container {
                margin: 20px auto;
            }
            
            .input-group {
                flex-direction: column;
            }
            
            .message-input {
                width: 100%;
            }
        }

        /* 添加房间信息样式 */
        .room-info {
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .room-info button {
            padding: 8px 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .room-info button:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>同步观看</h2>
        <div class="video-container">
            <select id="videoSelect">
                <?php foreach($videos as $video): ?>
                    <option value="<?php echo htmlspecialchars($video); ?>"><?php echo htmlspecialchars($video); ?></option>
                <?php endforeach; ?>
            </select>
            <video id="videoPlayer" controls>
                <source src="" type="video/mp4">
                您的浏览器不支持 HTML5 视频。
            </video>
        </div>
        
        <div class="chat-section">
            <div class="chat-container" id="chatBox"></div>
            <div class="input-group">
                <input type="text" id="username" placeholder="输入用户名" class="message-input">
                <input type="text" id="messageInput" placeholder="输入消息，按回车发送" class="message-input">
            </div>
        </div>
        
        <div class="room-info">
            房间ID: <span id="roomId"><?php echo htmlspecialchars($room_id); ?></span>
            <button onclick="copyRoomLink()">复制邀请链接</button>
        </div>
        
        <a href="upload.php" class="nav-link">← 返回上传页面</a>
    </div>

    <script>
        const video = document.getElementById('videoPlayer');
        const videoSelect = document.getElementById('videoSelect');
        const chatBox = document.getElementById('chatBox');
        const messageInput = document.getElementById('messageInput');
        const username = document.getElementById('username');
        
        // 添加视频选择事件监听
        videoSelect.addEventListener('change', function() {
            video.src = 'uploads/' + this.value;
            video.load();
        });
        
        // 修改初始加载视频的逻辑
        if('<?php echo $selected_video; ?>') {
            // 如果有指定视频，选中它
            const options = Array.from(videoSelect.options);
            const targetOption = options.find(option => option.value === '<?php echo $selected_video; ?>');
            if(targetOption) {
                targetOption.selected = true;
                video.src = 'uploads/<?php echo $selected_video; ?>';
                video.load();
            }
        } else if(videoSelect.value) {
            // 否则加载第一个视频
            video.src = 'uploads/' + videoSelect.value;
            video.load();
        }
        
        // 同步控制器类
        class SyncController {
            constructor(video, videoSelect, roomId, userId) {
                this.video = video;
                this.videoSelect = videoSelect;
                this.roomId = roomId;
                this.userId = userId;
                this.lastUpdateTime = 0;
                this.isSyncing = false;
                this.isDragging = false;
                this.p2pSync = null;
                
                // 初始化 P2P 同步
                if (roomId && userId) {
                    this.p2pSync = new P2PSync(roomId, userId, this, this.handleP2PSync.bind(this));
                }
                
                this.initializeEventListeners();
                this.startServerSync();
            }
            
            initializeEventListeners() {
                // 监听拖动事件
                this.video.addEventListener('seeking', () => {
                    this.isDragging = true;
                });
                
                this.video.addEventListener('seeked', () => {
                    this.isDragging = false;
                    this.syncProgress(this.video.currentTime);
                });
                
                // 监听播放进度
                this.video.addEventListener('timeupdate', () => {
                    if (this.isDragging) return;
                    
                    const now = Date.now();
                    if (now - this.lastUpdateTime > 1000) {
                        this.lastUpdateTime = now;
                        this.syncProgress(this.video.currentTime);
                        
                        // 同时通过 P2P 广播
                        if (this.p2pSync) {
                            this.p2pSync.broadcastTime(this.video.currentTime);
                        }
                    }
                });
            }
            
            syncProgress(time) {
                // 服务器同步
                fetch('watch.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'progress=' + time + '&video=' + this.videoSelect.value
                });
            }
            
            handleP2PSync(targetTime) {
                if (this.isDragging || this.isSyncing) return;
                
                const diff = Math.abs(this.video.currentTime - targetTime);
                if (diff > 3) {
                    this.isSyncing = true;
                    this.video.currentTime = targetTime;
                    setTimeout(() => {
                        this.isSyncing = false;
                    }, 500);
                }
            }
            
            startServerSync() {
                setInterval(() => {
                    if (this.isDragging || this.isSyncing || this.video.paused) return;
                    
                    fetch('watch.php?get_progress')
                        .then(response => response.text())
                        .then(data => {
                            if(data) {
                                const [videoName, progress] = data.split(':');
                                if(videoName === this.videoSelect.value) {
                                    const targetTime = parseFloat(progress);
                                    const diff = Math.abs(this.video.currentTime - targetTime);
                                    
                                    if(diff > 3) {
                                        this.isSyncing = true;
                                        this.video.currentTime = targetTime;
                                        setTimeout(() => {
                                            this.isSyncing = false;
                                        }, 500);
                                    }
                                }
                            }
                        })
                        .catch(() => {
                            this.isSyncing = false;
                        });
                }, 1000);
            }
        }

        // 首先定义卡尔曼滤波器类
        class KalmanFilter {
            constructor() {
                this.Q = 0.1;  // 过程噪声
                this.R = 1;    // 测量噪声
                this.P = 1;    // 估计误差
                this.X = 0;    // 状态估计
            }
            
            update(measurement) {
                // 预测
                const K = this.P / (this.P + this.R);
                // 更新
                this.X = this.X + K * (measurement - this.X);
                this.P = (1 - K) * this.P + this.Q;
                return this.X;
            }
        }

        // 然后定义 P2PSync 类
        class P2PSync {
            constructor(roomId, userId, controller, onSync) {
                this.roomId = roomId;
                this.userId = userId;
                this.controller = controller;
                this.onSync = onSync;
                this.peers = new Map();
                this.dataChannels = new Map();
                this.syncOffset = 0;  // NTP时间偏移
                this.jitterBuffer = new Map();  // 抖动缓冲区
                this.kalmanFilter = new KalmanFilter();  // 卡尔曼滤波器
                
                this.initializeConnections();
                this.startSyncLoop();
            }
            
            // 开始同步循环
            startSyncLoop() {
                setInterval(() => {
                    this.broadcastSyncInfo();
                }, 1000);  // 每秒发送同步信息
            }
            
            // 广播同步信息
            broadcastSyncInfo() {
                const syncInfo = {
                    type: 'sync',
                    timestamp: Date.now(),
                    videoTime: this.controller.video.currentTime,
                    playbackRate: this.controller.video.playbackRate
                };
                
                for (const [_, channel] of this.dataChannels) {
                    if (channel.readyState === 'open') {
                        channel.send(JSON.stringify(syncInfo));
                    }
                }
            }
            
            // 处理接收到的同步信息
            handleSyncInfo(data, senderId) {
                const now = Date.now();
                const roundTripTime = now - data.timestamp;
                const estimatedOffset = roundTripTime / 2;
                
                // 更新时间偏移
                this.syncOffset = this.kalmanFilter.update(estimatedOffset);
                
                // 计算目标时间
                const targetTime = data.videoTime + (now - data.timestamp + this.syncOffset) / 1000;
                
                // 将同步信息添加到抖动缓冲区
                this.jitterBuffer.set(senderId, {
                    targetTime,
                    timestamp: now,
                    playbackRate: data.playbackRate
                });
                
                // 处理抖动缓冲区
                this.processJitterBuffer();
            }
            
            // 处理抖动缓冲区
            processJitterBuffer() {
                // 清理过期的缓冲数据
                const now = Date.now();
                for (const [senderId, data] of this.jitterBuffer) {
                    if (now - data.timestamp > 5000) {  // 5秒后清理
                        this.jitterBuffer.delete(senderId);
                    }
                }
                
                // 计算平均目标时间
                let totalTime = 0;
                let count = 0;
                for (const data of this.jitterBuffer.values()) {
                    totalTime += data.targetTime;
                    count++;
                }
                
                if (count > 0) {
                    const averageTargetTime = totalTime / count;
                    this.adjustPlayback(averageTargetTime);
                }
            }
            
            // 调整播放
            adjustPlayback(targetTime) {
                const video = this.controller.video;
                const currentTime = video.currentTime;
                const diff = targetTime - currentTime;
                
                if (Math.abs(diff) > 0.5) {  // 如果差异大于0.5秒
                    if (Math.abs(diff) > 3) {
                        // 差异太大，直接跳转
                        video.currentTime = targetTime;
                    } else {
                        // 通过调整播放速率来平滑同步
                        const newRate = 1.0 + Math.sign(diff) * Math.min(Math.abs(diff) / 2, 0.25);
                        video.playbackRate = newRate;
                        
                        // 2秒后恢复正常播放速率
                        setTimeout(() => {
                            video.playbackRate = 1.0;
                        }, 2000);
                    }
                }
            }
            
            // 设置数据通道
            setupDataChannel(channel, remoteUserId) {
                channel.onopen = () => {
                    console.log(`P2P connection established with ${remoteUserId}`);
                    this.dataChannels.set(remoteUserId, channel);
                };
                
                channel.onmessage = event => {
                    try {
                        const data = JSON.parse(event.data);
                        if (data.type === 'sync') {
                            this.handleSyncInfo(data, remoteUserId);
                        }
                    } catch (error) {
                        console.error('Error processing P2P message:', error);
                    }
                };
            }
        }

        // 初始化同步控制器
        const syncController = new SyncController(
            video,
            videoSelect,
            '<?php echo $room_id; ?>',
            '<?php echo $user_id; ?>'
        );
        
        // 发送消息
        messageInput.addEventListener('keypress', function(e) {
            if(e.key === 'Enter' && this.value.trim() && username.value.trim()) {
                fetch('watch.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'message=' + encodeURIComponent(this.value) + '&username=' + encodeURIComponent(username.value)
                });
                this.value = '';
            }
        });
        
        // 格式化消息显示
        function formatMessage(text) {
            const lines = text.split('\n');
            return lines.map(line => {
                if (line.trim()) {
                    const parts = line.match(/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) ([^:]+): (.+)$/);
                    if (parts) {
                        return `<div class="message">
                            <span class="message-time">${parts[1]}</span>
                            <span class="message-user">${parts[2]}</span>: 
                            <span class="message-content">${parts[3]}</span>
                        </div>`;
                    }
                }
                return '';
            }).join('');
        }
        
        // 获取消息
        function updateMessages() {
            fetch('watch.php?get_messages')
                .then(response => response.text())
                .then(data => {
                    chatBox.innerHTML = formatMessage(data);
                    chatBox.scrollTop = chatBox.scrollHeight;
                });
        }
        
        setInterval(updateMessages, 1000);
        
        // 保存用户名到本地存储
        if (localStorage.getItem('username')) {
            username.value = localStorage.getItem('username');
        }
        
        username.addEventListener('change', function() {
            localStorage.setItem('username', this.value);
        });

        // 复制房间链接
        function copyRoomLink() {
            const link = window.location.href;
            navigator.clipboard.writeText(link).then(() => {
                alert('邀请链接已复制到剪贴板');
            });
        }
        
        // 在页面关闭时离开房间
        window.addEventListener('beforeunload', function() {
            const roomId = '<?php echo $room_id; ?>';
            const userId = '<?php echo $user_id; ?>';
            if (roomId) {
                fetch('room.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=leave&room=${roomId}&user=${userId}`
                });
            }
        });
    </script>
</body>
</html> 