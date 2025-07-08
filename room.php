<?php
session_start();

class Room {
    private $roomFile = 'rooms.txt';
    private $userFile = 'users.txt';
    
    // 创建房间
    public function createRoom($videoId, $creator) {
        $roomId = uniqid();
        $room = [
            'id' => $roomId,
            'video' => $videoId,
            'creator' => $creator,
            'created_at' => time(),
            'users' => [$creator]
        ];
        
        $rooms = $this->getRooms();
        $rooms[$roomId] = $room;
        $this->saveRooms($rooms);
        return $roomId;
    }
    
    // 加入房间
    public function joinRoom($roomId, $userId) {
        $rooms = $this->getRooms();
        if (isset($rooms[$roomId])) {
            if (!in_array($userId, $rooms[$roomId]['users'])) {
                $rooms[$roomId]['users'][] = $userId;
                $this->saveRooms($rooms);
            }
            return true;
        }
        return false;
    }
    
    // 离开房间
    public function leaveRoom($roomId, $userId) {
        $rooms = $this->getRooms();
        if (isset($rooms[$roomId])) {
            $rooms[$roomId]['users'] = array_diff($rooms[$roomId]['users'], [$userId]);
            // 如果房间空了，删除房间
            if (empty($rooms[$roomId]['users'])) {
                unset($rooms[$roomId]);
            }
            $this->saveRooms($rooms);
            return true;
        }
        return false;
    }
    
    // 添加 WebRTC 信令方法
    public function sendSignal($roomId, $userId, $signal) {
        $rooms = $this->getRooms();
        if (isset($rooms[$roomId])) {
            if (!isset($rooms[$roomId]['signals'])) {
                $rooms[$roomId]['signals'] = [];
            }
            $rooms[$roomId]['signals'][] = [
                'from' => $userId,
                'data' => $signal,
                'time' => microtime(true)
            ];
            // 只保留最近30秒的信令
            $rooms[$roomId]['signals'] = array_filter($rooms[$roomId]['signals'], function($s) {
                return microtime(true) - $s['time'] < 30;
            });
            $this->saveRooms($rooms);
            return true;
        }
        return false;
    }
    
    public function getSignals($roomId, $userId, $lastTime = 0) {
        $rooms = $this->getRooms();
        if (isset($rooms[$roomId])) {
            $signals = $rooms[$roomId]['signals'] ?? [];
            return array_filter($signals, function($s) use ($userId, $lastTime) {
                return $s['from'] !== $userId && $s['time'] > $lastTime;
            });
        }
        return [];
    }
    
    public function getUsers($roomId) {
        $rooms = $this->getRooms();
        if (isset($rooms[$roomId])) {
            return $rooms[$roomId]['users'];
        }
        return [];
    }
    
    private function getRooms() {
        return file_exists($this->roomFile) ? 
            json_decode(file_get_contents($this->roomFile), true) : [];
    }
    
    private function saveRooms($rooms) {
        file_put_contents($this->roomFile, json_encode($rooms));
    }
}

// 处理API请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room = new Room();
    $action = $_POST['action'] ?? '';
    $response = ['success' => false];
    
    switch ($action) {
        case 'create':
            $videoId = $_POST['video'] ?? '';
            $userId = $_POST['user'] ?? '';
            if ($videoId && $userId) {
                $roomId = $room->createRoom($videoId, $userId);
                $response = ['success' => true, 'roomId' => $roomId];
            }
            break;
            
        case 'join':
            $roomId = $_POST['room'] ?? '';
            $userId = $_POST['user'] ?? '';
            if ($roomId && $userId) {
                $success = $room->joinRoom($roomId, $userId);
                $response = ['success' => $success];
            }
            break;
            
        case 'leave':
            $roomId = $_POST['room'] ?? '';
            $userId = $_POST['user'] ?? '';
            if ($roomId && $userId) {
                $success = $room->leaveRoom($roomId, $userId);
                $response = ['success' => $success];
            }
            break;
            
        case 'signal':
            $roomId = $_POST['room'] ?? '';
            $userId = $_POST['user'] ?? '';
            $signal = $_POST['signal'] ?? '';
            if ($roomId && $userId && $signal) {
                $success = $room->sendSignal($roomId, $userId, $signal);
                $response = ['success' => $success];
            }
            break;
            
        case 'get_signals':
            $roomId = $_POST['room'] ?? '';
            $userId = $_POST['user'] ?? '';
            $lastTime = floatval($_POST['last_time'] ?? 0);
            if ($roomId && $userId) {
                $signals = $room->getSignals($roomId, $userId, $lastTime);
                $response = ['success' => true, 'signals' => $signals];
            }
            break;
            
        case 'get_users':
            $roomId = $_POST['room'] ?? '';
            if ($roomId) {
                $users = $room->getUsers($roomId);
                $response = ['success' => true, 'users' => $users];
            }
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?> 