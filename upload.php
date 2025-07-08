<?php
// 设置上传限制
ini_set('upload_max_filesize', '3G');
ini_set('post_max_size', '3G');
ini_set('memory_limit', '3G');
ini_set('max_execution_time', '3600');
ini_set('max_input_time', '3600');

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    if (isset($_POST['chunk']) && isset($_POST['chunks'])) {
        $fileName = isset($_POST['name']) ? $_POST['name'] : '';
        $chunk = isset($_POST['chunk']) ? intval($_POST['chunk']) : 0;
        $chunks = isset($_POST['chunks']) ? intval($_POST['chunks']) : 0;
        
        $tempFile = $target_dir . $fileName . '.part' . $chunk;
        $finalFile = $target_dir . $fileName;
        
        // 保存分片
        move_uploaded_file($_FILES['file']['tmp_name'], $tempFile);
        
        // 检查是否所有分片都上传完成
        $done = true;
        for ($i = 0; $i < $chunks; $i++) {
            if (!file_exists($target_dir . $fileName . '.part' . $i)) {
                $done = false;
                break;
            }
        }
        
        // 如果所有分片都上传完成，合并文件
        if ($done) {
            $out = fopen($finalFile, "wb");
            
            if ($out) {
                for ($i = 0; $i < $chunks; $i++) {
                    $partFile = $target_dir . $fileName . '.part' . $i;
                    if (($in = fopen($partFile, "rb")) !== false) {
                        while ($buff = fread($in, 4096)) {
                            fwrite($out, $buff);
                        }
                        fclose($in);
                        unlink($partFile);
                    }
                }
                fclose($out);
                
                // 保存视频信息到 videos.txt
                $video_info = $fileName . "\n";
                file_put_contents("videos.txt", $video_info, FILE_APPEND);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => '无法创建最终文件']);
            }
        } else {
            echo json_encode(['success' => true, 'chunk' => $chunk]);
        }
        exit;
    }

    // 处理删除请求
    if(isset($_POST['delete_file'])) {
        $file_to_delete = $_POST['delete_file'];
        $file_path = "uploads/" . $file_to_delete;
        
        // 删除文件
        if(file_exists($file_path) && unlink($file_path)) {
            // 从 videos.txt 中移除记录
            $videos = file("videos.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $videos = array_filter($videos, function($video) use ($file_to_delete) {
                return trim($video) !== $file_to_delete;
            });
            file_put_contents("videos.txt", implode("\n", $videos) . "\n");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => '删除失败']);
        }
        exit;
    }
}

// 获取视频列表
$videos = file_exists("videos.txt") ? file("videos.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
$videos = array_filter($videos);
?>

<!DOCTYPE html>
<html>
<head>
    <title>视频上传</title>
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
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #34495e;
            font-size: 28px;
        }

        .upload-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
        }

        .file-input-container {
            position: relative;
            margin-bottom: 20px;
            text-align: center;
        }

        .file-input-label {
            display: inline-block;
            padding: 12px 24px;
            background: #3498db;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .file-input-label:hover {
            background: #2980b9;
        }

        #videoFile {
            display: none;
        }

        .upload-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: #2ecc71;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }

        .upload-btn:hover {
            background: #27ae60;
        }

        .upload-btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }

        .progress-container {
            margin-top: 20px;
        }

        .progress {
            width: 100%;
            height: 10px;
            background: #ecf0f1;
            border-radius: 5px;
            overflow: hidden;
            display: none;
        }

        .progress-bar {
            width: 0%;
            height: 100%;
            background: #3498db;
            transition: width 0.3s ease;
        }

        .file-info {
            margin-top: 10px;
            text-align: center;
            color: #7f8c8d;
        }

        .size-limit {
            text-align: center;
            color: #95a5a6;
            margin-top: 15px;
            font-size: 14px;
        }

        .nav-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #3498db;
            text-decoration: none;
            transition: color 0.3s;
        }

        .nav-link:hover {
            color: #2980b9;
        }

        .selected-file {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            display: none;
        }

        .upload-status {
            margin-top: 15px;
            text-align: center;
            font-weight: bold;
        }

        .video-list {
            margin-top: 30px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
        }
        
        .video-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
            transition: background 0.3s;
        }
        
        .video-item:last-child {
            border-bottom: none;
        }
        
        .video-item:hover {
            background: #f8f9fa;
        }
        
        .video-name {
            flex: 1;
            margin-right: 15px;
            color: #2c3e50;
        }
        
        .video-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            font-size: 14px;
        }
        
        .play-btn {
            background: #3498db;
            color: white;
        }
        
        .play-btn:hover {
            background: #2980b9;
        }
        
        .delete-btn {
            background: #e74c3c;
            color: white;
        }
        
        .delete-btn:hover {
            background: #c0392b;
        }
        
        .empty-list {
            text-align: center;
            padding: 20px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>视频上传</h2>
        <div class="upload-form">
            <div class="file-input-container">
                <label class="file-input-label" for="videoFile">
                    选择视频文件
                </label>
                <input type="file" id="videoFile" accept="video/*">
                <div class="selected-file" id="selectedFile"></div>
            </div>
            
            <button class="upload-btn" onclick="startUpload()" id="uploadBtn" disabled>
                开始上传
            </button>

            <div class="progress-container">
                <div class="progress">
                    <div class="progress-bar"></div>
                </div>
                <div class="upload-status" id="uploadStatus"></div>
            </div>

            <p class="size-limit">支持格式：MP4、WebM | 最大文件大小：3GB</p>
        </div>
        
        <div class="video-list">
            <h3>已上传的视频</h3>
            <?php if(empty($videos)): ?>
                <div class="empty-list">暂无上传的视频</div>
            <?php else: ?>
                <?php foreach($videos as $video): ?>
                    <div class="video-item" data-file="<?php echo htmlspecialchars($video); ?>">
                        <div class="video-name"><?php echo htmlspecialchars($video); ?></div>
                        <div class="video-actions">
                            <button class="action-btn play-btn" onclick="playVideo('<?php echo htmlspecialchars($video); ?>')">播放</button>
                            <button class="action-btn delete-btn" onclick="deleteVideo('<?php echo htmlspecialchars($video); ?>')">删除</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const CHUNK_SIZE = 1024 * 1024; // 1MB per chunk
        const videoFile = document.getElementById('videoFile');
        const uploadBtn = document.getElementById('uploadBtn');
        const selectedFile = document.getElementById('selectedFile');
        const uploadStatus = document.getElementById('uploadStatus');

        videoFile.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const size = (file.size / (1024 * 1024)).toFixed(2);
                selectedFile.style.display = 'block';
                selectedFile.textContent = `已选择: ${file.name} (${size}MB)`;
                uploadBtn.disabled = false;
            } else {
                selectedFile.style.display = 'none';
                uploadBtn.disabled = true;
            }
        });

        async function startUpload() {
            const file = videoFile.files[0];
            if (!file) {
                alert('请选择文件');
                return;
            }

            uploadBtn.disabled = true;
            const progress = document.querySelector('.progress');
            const progressBar = document.querySelector('.progress-bar');
            progress.style.display = 'block';
            uploadStatus.textContent = '准备上传...';

            const chunks = Math.ceil(file.size / CHUNK_SIZE);
            
            try {
                for (let chunk = 0; chunk < chunks; chunk++) {
                    const start = chunk * CHUNK_SIZE;
                    const end = Math.min(start + CHUNK_SIZE, file.size);
                    const blob = file.slice(start, end);

                    const formData = new FormData();
                    formData.append('file', blob);
                    formData.append('name', file.name);
                    formData.append('chunk', chunk);
                    formData.append('chunks', chunks);

                    const response = await fetch('upload.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    
                    if (!result.success) {
                        throw new Error(result.error || '上传失败');
                    }

                    const percent = ((chunk + 1) / chunks) * 100;
                    progressBar.style.width = percent + '%';
                    uploadStatus.textContent = `上传进度: ${Math.round(percent)}%`;
                }

                uploadStatus.textContent = '上传完成！';
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } catch (error) {
                uploadStatus.textContent = '上传失败：' + error.message;
                uploadBtn.disabled = false;
            }
        }

        function playVideo(filename) {
            window.location.href = 'watch.php?video=' + encodeURIComponent(filename);
        }
        
        async function deleteVideo(filename) {
            if(!confirm('确定要删除这个视频吗？')) {
                return;
            }
            
            try {
                const response = await fetch('upload.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'delete_file=' + encodeURIComponent(filename)
                });
                
                const result = await response.json();
                if(result.success) {
                    const videoItem = document.querySelector(`.video-item[data-file="${filename}"]`);
                    if(videoItem) {
                        videoItem.remove();
                    }
                    
                    // 如果没有视频了，显示空列表提示
                    const videoList = document.querySelector('.video-list');
                    if(!videoList.querySelector('.video-item')) {
                        videoList.innerHTML = '<h3>已上传的视频</h3><div class="empty-list">暂无上传的视频</div>';
                    }
                } else {
                    alert('删除失败：' + (result.error || '未知错误'));
                }
            } catch(error) {
                alert('删除失败：' + error.message);
            }
        }
    </script>
</body>
</html> 