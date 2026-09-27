<?php
/* ========================================================================== */
/* 💬 [chat_api.php - 100% 최종 완결본] */
/* - 대화방 나가기 시 flag(deleted_user_ids)를 이용한 논리적 삭제 처리 */
/* - 메시지 조회 및 방 조회 시 flag(deleted_user_ids) 필터링 완비 */
/* - 24시간 이내 본인 메시지만 수정 가능 */
/* - 24시간 이내 삭제 시: 모두에게 '삭제된 메시지입니다'로 표시 */
/* - 24시간 이후 삭제 시: 요청자(본인) 화면에서만 숨김 처리 (상대방은 정상 노출) */
/* ========================================================================== */

@ini_set('display_errors', '0');
error_reporting(0);
date_default_timezone_set('Asia/Seoul');

while (ob_get_level()) {
    @ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once 'db.php';
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('데이터베이스 연결 객체(PDO)를 불러올 수 없습니다.');
    }

    $user_id = (int)$_SESSION['user_id'];
    $user_role = $_SESSION['role'] ?? 'USER';
    $username = $_SESSION['username'] ?? '사용자';
    $action = $_GET['action'] ?? ($_POST['action'] ?? '');

    // [24시간 이후 나에게만 삭제 및 대화방 flag 논리 삭제를 위한 컬럼 자동 생성 트랩]
    try {
        $pdo->exec("ALTER TABLE chat_messages ADD COLUMN hidden_user_ids TEXT NULL DEFAULT NULL");
    } catch (\Throwable $colEx) {}

    try {
        $pdo->exec("ALTER TABLE chat_rooms ADD COLUMN deleted_user_ids TEXT NULL DEFAULT NULL");
    } catch (\Throwable $colEx2) {}

    $upload_chat_dir = __DIR__ . '/uploads/chat/';
    $upload_avatar_dir = __DIR__ . '/uploads/avatars/';
    if (!is_dir($upload_chat_dir)) @mkdir($upload_chat_dir, 0777, true);
    if (!is_dir($upload_avatar_dir)) @mkdir($upload_avatar_dir, 0777, true);

    /* 내 프로필 정보 조회 */
    if ($action === 'get_my_profile') {
        $p_stmt = $pdo->prepare("SELECT nickname, avatar_path, status_msg FROM chat_user_profiles WHERE user_id = ? LIMIT 1");
        $p_stmt->execute([$user_id]);
        $profile = $p_stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'profile' => [
                'nickname' => $profile['nickname'] ?? $username,
                'avatar_path' => $profile['avatar_path'] ?? '',
                'status_msg' => $profile['status_msg'] ?? ''
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* 프로필 수정 */
    if ($action === 'save_profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $nickname = trim($_POST['nickname'] ?? '');
        $status_msg = trim($_POST['status_msg'] ?? '');
        if (empty($nickname)) $nickname = $username;

        $chk = $pdo->prepare("SELECT avatar_path FROM chat_user_profiles WHERE user_id = ? LIMIT 1");
        $chk->execute([$user_id]);
        $existing_avatar = $chk->fetchColumn() ?: '';

        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['avatar_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($ext, $allowed) && $_FILES['avatar_file']['size'] <= 10 * 1024 * 1024) {
                $new_avatar_name = 'avatar_' . $user_id . '_' . date('Ymd_His') . '.' . $ext;
                $target_path = $upload_avatar_dir . $new_avatar_name;

                if (@move_uploaded_file($_FILES['avatar_file']['tmp_name'], $target_path)) {
                    if (!empty($existing_avatar) && file_exists(__DIR__ . '/' . $existing_avatar)) {
                        @unlink(__DIR__ . '/' . $existing_avatar);
                    }
                    $existing_avatar = 'uploads/avatars/' . $new_avatar_name;
                }
            }
        }

        $ins = $pdo->prepare("
            INSERT INTO chat_user_profiles (user_id, nickname, avatar_path, status_msg) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                nickname = VALUES(nickname), 
                avatar_path = VALUES(avatar_path), 
                status_msg = VALUES(status_msg)
        ");
        $ins->execute([$user_id, $nickname, $existing_avatar, $status_msg]);

        echo json_encode(['status' => 'success', 'message' => '프로필이 저장되었습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* 친구 요청 보내기 */
    if ($action === 'send_friend_request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $target_id = (int)($_POST['target_id'] ?? 0);

        if ($target_id <= 0 || $target_id === $user_id) {
            echo json_encode(['status' => 'error', 'message' => '올바른 회원을 선택하세요.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $chk = $pdo->prepare("SELECT status FROM chat_friends WHERE user_id = ? AND friend_id = ? LIMIT 1");
        $chk->execute([$user_id, $target_id]);
        $status = $chk->fetchColumn();

        if ($status === 'ACCEPTED') {
            echo json_encode(['status' => 'error', 'message' => '이미 등록된 친구입니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        } elseif ($status === 'PENDING') {
            echo json_encode(['status' => 'error', 'message' => '이미 요청을 보낸 상태입니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $opp_chk = $pdo->prepare("SELECT id FROM chat_friends WHERE user_id = ? AND friend_id = ? AND status = 'PENDING' LIMIT 1");
        $opp_chk->execute([$target_id, $user_id]);
        if ($opp_chk->fetch()) {
            $pdo->prepare("UPDATE chat_friends SET status = 'ACCEPTED' WHERE user_id = ? AND friend_id = ?")->execute([$target_id, $user_id]);
            $pdo->prepare("INSERT INTO chat_friends (user_id, friend_id, status) VALUES (?, ?, 'ACCEPTED') ON DUPLICATE KEY UPDATE status = 'ACCEPTED'")->execute([$user_id, $target_id]);
            echo json_encode(['status' => 'success', 'message' => '상대방의 요청이 있어 즉시 친구로 연결되었습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $ins = $pdo->prepare("INSERT INTO chat_friends (user_id, friend_id, status) VALUES (?, ?, 'PENDING') ON DUPLICATE KEY UPDATE status = 'PENDING'");
        $ins->execute([$user_id, $target_id]);

        echo json_encode(['status' => 'success', 'message' => '친구 요청을 보냈습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* 친구 요청 응답 */
    if ($action === 'respond_friend_request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $requester_id = (int)($_POST['requester_id'] ?? 0);
        $decision = $_POST['decision'] ?? 'REJECT';

        if ($requester_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => '요청자 식별 오류'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($decision === 'ACCEPT') {
            $pdo->prepare("UPDATE chat_friends SET status = 'ACCEPTED' WHERE user_id = ? AND friend_id = ?")->execute([$requester_id, $user_id]);
            $pdo->prepare("INSERT INTO chat_friends (user_id, friend_id, status) VALUES (?, ?, 'ACCEPTED') ON DUPLICATE KEY UPDATE status = 'ACCEPTED'")->execute([$user_id, $requester_id]);
            echo json_encode(['status' => 'success', 'message' => '친구 요청을 수락했습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            $pdo->prepare("DELETE FROM chat_friends WHERE user_id = ? AND friend_id = ?")->execute([$requester_id, $user_id]);
            echo json_encode(['status' => 'success', 'message' => '친구 요청을 거절했습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /* 친구 삭제 */
    if ($action === 'delete_friend' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $friend_id = (int)($_POST['friend_id'] ?? 0);
        $pdo->prepare("DELETE FROM chat_friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)")
            ->execute([$user_id, $friend_id, $friend_id, $user_id]);

        echo json_encode(['status' => 'success', 'message' => '친구가 삭제되었습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* [메시지 조회 (플래그 삭제된 방 차단 및 나에게 숨김 처리된 메시지 필터링)] */
    if ($action === 'get_messages') {
        $room_id = (int)($_GET['room_id'] ?? 0);
        $last_id = (int)($_GET['last_id'] ?? 0);

        if ($room_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => '유효한 대화방이 아닙니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $r_stmt = $pdo->prepare("SELECT * FROM chat_rooms WHERE id = ? LIMIT 1");
        $r_stmt->execute([$room_id]);
        $room = $r_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            echo json_encode(['status' => 'error', 'message' => '존재하지 않는 대화방입니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        /* [사용자가 나간 방(플래그 삭제 처리됨)인 경우 차단] */
        if (!empty($room['deleted_user_ids'])) {
            $del_list = explode(',', (string)$room['deleted_user_ids']);
            if (in_array((string)$user_id, $del_list, true)) {
                echo json_encode(['status' => 'error', 'message' => '삭제되거나 나간 대화방입니다.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // 방 제목
        $room_display_title = !empty($room['title']) ? $room['title'] : '';
        if (empty($room_display_title)) {
            $opp_id = ($room['user_one_id'] == $user_id) ? $room['user_two_id'] : $room['user_one_id'];
            $opp_stmt = $pdo->prepare("
                SELECT u.username, u.role, p.nickname 
                FROM users u 
                LEFT JOIN chat_user_profiles p ON u.id = p.user_id 
                WHERE u.id = ? LIMIT 1
            ");
            $opp_stmt->execute([$opp_id]);
            $opp_user = $opp_stmt->fetch(PDO::FETCH_ASSOC);

            $opp_name = !empty($opp_user['nickname']) ? $opp_user['nickname'] : ($opp_user['username'] ?? '대화 상대');
            $room_display_title = "{$opp_name} (" . ($opp_user['role'] ?? 'USER') . ")";
        }

        // 대화 메시지 조회 (자신에게 숨김 처리된 메시지 제외)
        $sql = "
            SELECT m.id, m.sender_id, m.message, m.is_edited, m.is_deleted,
                   m.attachment_type, m.attachment_path, m.attachment_orig_name, m.attachment_size,
                   m.created_at, m.hidden_user_ids,
                   COALESCE(p.nickname, u.username) AS sender_name,
                   p.avatar_path AS sender_avatar
            FROM chat_messages m
            LEFT JOIN users u ON m.sender_id = u.id
            LEFT JOIN chat_user_profiles p ON m.sender_id = p.user_id
            WHERE m.room_id = ? AND m.id > ?
            ORDER BY m.id ASC
        ";
        $m_stmt = $pdo->prepare($sql);
        $m_stmt->execute([$room_id, $last_id]);
        $raw_messages = $m_stmt->fetchAll(PDO::FETCH_ASSOC);

        // 현재 사용자에게 숨겨진 메시지 필터링
        $messages = [];
        foreach ($raw_messages as $m) {
            if (!empty($m['hidden_user_ids'])) {
                $hiddenList = explode(',', (string)$m['hidden_user_ids']);
                if (in_array((string)$user_id, $hiddenList, true)) {
                    continue; // 본인에게 숨김 처리된 메시지는 스킵
                }
            }
            $messages[] = $m;
        }

        // 최근 수정/삭제 동기화
        $updated_messages = [];
        try {
            $u_stmt = $pdo->prepare("
                SELECT id, message, is_edited, is_deleted, hidden_user_ids 
                FROM chat_messages 
                WHERE room_id = ? AND (is_edited = 1 OR is_deleted = 1 OR hidden_user_ids IS NOT NULL)
                ORDER BY id DESC LIMIT 30
            ");
            $u_stmt->execute([$room_id]);
            $raw_updated = $u_stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($raw_updated as $um) {
                $is_hidden_for_me = false;
                if (!empty($um['hidden_user_ids'])) {
                    $hiddenList = explode(',', (string)$um['hidden_user_ids']);
                    if (in_array((string)$user_id, $hiddenList, true)) {
                        $is_hidden_for_me = true;
                    }
                }
                $um['hidden_for_me'] = $is_hidden_for_me;
                $updated_messages[] = $um;
            }
        } catch (\Throwable $uEx) {}

        // 읽음 업데이트
        try {
            $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE room_id = ? AND sender_id != ? AND is_read = 0")
                ->execute([$room_id, $user_id]);
        } catch (\Throwable $rEx) {}

        echo json_encode([
            'status' => 'success',
            'room_info' => [
                'title' => $room_display_title,
                'is_host' => ($user_role === 'ADMIN')
            ],
            'messages' => $messages ?: [],
            'updated_messages' => $updated_messages ?: []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* [메시지 전송 (전송 시 상대방이나 본인의 삭제 플래그 자동 복원)] */
    if ($action === 'send_message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $room_id = (int)($_POST['room_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $has_file = isset($_FILES['chat_file']) && $_FILES['chat_file']['error'] === UPLOAD_ERR_OK;

        if ($room_id <= 0 || (empty($message) && !$has_file)) {
            echo json_encode(['status' => 'error', 'message' => '메시지 또는 파일을 입력하세요.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $att_type = 'NONE';
        $att_path = null;
        $att_orig = null;
        $att_size = 0;

        if ($has_file) {
            $orig_name = $_FILES['chat_file']['name'];
            $file_size = (int)$_FILES['chat_file']['size'];
            $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

            $disallowed = ['php', 'php3', 'phtml', 'html', 'htm', 'js', 'exe', 'sh', 'bat'];
            if (!in_array($ext, $disallowed) && $file_size <= 25 * 1024 * 1024) {
                $img_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $att_type = in_array($ext, $img_exts) ? 'IMAGE' : 'FILE';

                $rand_name = 'chat_' . date('Ymd_His') . '_' . substr(md5(uniqid(mt_rand(), true)), 0, 8) . '.' . $ext;
                $save_path = $upload_chat_dir . $rand_name;

                if (@move_uploaded_file($_FILES['chat_file']['tmp_name'], $save_path)) {
                    $att_path = 'uploads/chat/' . $rand_name;
                    $att_orig = $orig_name;
                    $att_size = $file_size;
                }
            }
        }

        $ins = $pdo->prepare("
            INSERT INTO chat_messages 
            (room_id, sender_id, message, attachment_type, attachment_path, attachment_orig_name, attachment_size, is_deleted) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 0)
        ");
        $ins->execute([
            $room_id, $user_id, $message,
            $att_type, $att_path, $att_orig, $att_size
        ]);

        /* [방에 새 메시지가 발송되면 삭제 플래그(deleted_user_ids)에서 발송자 제외] */
        $r_chk = $pdo->prepare("SELECT deleted_user_ids FROM chat_rooms WHERE id = ? LIMIT 1");
        $r_chk->execute([$room_id]);
        $cur_del = $r_chk->fetchColumn();
        if (!empty($cur_del)) {
            $del_arr = explode(',', (string)$cur_del);
            $del_arr = array_diff($del_arr, [(string)$user_id]);
            $new_del = implode(',', array_filter($del_arr));
            $pdo->prepare("UPDATE chat_rooms SET deleted_user_ids = ? WHERE id = ?")->execute([$new_del, $room_id]);
        }

        @$pdo->prepare("UPDATE chat_rooms SET updated_at = NOW() WHERE id = ?")->execute([$room_id]);
        @$pdo->prepare("UPDATE chat_room_members SET has_left = 0 WHERE room_id = ? AND user_id = ?")->execute([$room_id, $user_id]);

        echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* [메시지 삭제 (24시간 이내 / 이후 정책)] */
    if ($action === 'delete_message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $message_id = (int)($_POST['message_id'] ?? 0);

        $chk = $pdo->prepare("SELECT sender_id, attachment_path, created_at, hidden_user_ids FROM chat_messages WHERE id = ? LIMIT 1");
        $chk->execute([$message_id]);
        $msg = $chk->fetch(PDO::FETCH_ASSOC);

        if (!$msg) {
            echo json_encode(['status' => 'error', 'message' => '메시지를 찾을 수 없습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($user_role !== 'ADMIN' && (int)$msg['sender_id'] !== $user_id) {
            echo json_encode(['status' => 'error', 'message' => '자신의 메시지만 삭제할 수 있습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $created_time = strtotime($msg['created_at']);
        $diff_seconds = time() - $created_time;
        $is_within_24h = ($diff_seconds <= 86400);

        if ($is_within_24h) {
            if (!empty($msg['attachment_path'])) {
                @unlink(__DIR__ . '/' . $msg['attachment_path']);
            }
            $pdo->prepare("UPDATE chat_messages SET is_deleted = 1, message = '삭제된 메시지입니다.', attachment_type = 'NONE', attachment_path = NULL WHERE id = ?")
                ->execute([$message_id]);

            echo json_encode(['status' => 'success', 'mode' => 'global', 'message' => '메시지가 삭제되었습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            $existing_hidden = !empty($msg['hidden_user_ids']) ? explode(',', (string)$msg['hidden_user_ids']) : [];
            if (!in_array((string)$user_id, $existing_hidden, true)) {
                $existing_hidden[] = (string)$user_id;
            }
            $new_hidden_str = implode(',', array_filter($existing_hidden));

            $pdo->prepare("UPDATE chat_messages SET hidden_user_ids = ? WHERE id = ?")
                ->execute([$new_hidden_str, $message_id]);

            echo json_encode(['status' => 'success', 'mode' => 'me_only', 'message' => '본인의 대화방에서만 삭제되었습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /* [메시지 수정] */
    if ($action === 'edit_message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $message_id = (int)($_POST['message_id'] ?? 0);
        $new_content = trim($_POST['new_content'] ?? '');

        if ($message_id <= 0 || empty($new_content)) {
            echo json_encode(['status' => 'error', 'message' => '수정할 내용을 입력하세요.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $chk = $pdo->prepare("SELECT sender_id, is_deleted, created_at FROM chat_messages WHERE id = ? LIMIT 1");
        $chk->execute([$message_id]);
        $msg = $chk->fetch(PDO::FETCH_ASSOC);

        if (!$msg) {
            echo json_encode(['status' => 'error', 'message' => '메시지를 찾을 수 없습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($msg['is_deleted'] == 1) {
            echo json_encode(['status' => 'error', 'message' => '삭제된 메시지는 수정할 수 없습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ((int)$msg['sender_id'] !== $user_id) {
            echo json_encode(['status' => 'error', 'message' => '자신의 메시지만 수정할 수 있습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $created_time = strtotime($msg['created_at']);
        if ((time() - $created_time) > 86400) {
            echo json_encode(['status' => 'error', 'message' => '전송 후 24시간이 지난 메시지는 수정할 수 없습니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $pdo->prepare("UPDATE chat_messages SET message = ?, is_edited = 1 WHERE id = ?")
            ->execute([$new_content, $message_id]);

        echo json_encode(['status' => 'success', 'message' => '메시지가 수정되었습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /* ========================================================================== */
    /* [대화방 나가기 (영구 삭제가 아닌 Flag를 통한 삭제/숨김 처리)] */
    /* ========================================================================== */
    if ($action === 'leave_room') {
        $room_id = (int)($_POST['room_id'] ?? ($_GET['room_id'] ?? 0));

        if ($room_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => '유효하지 않은 대화방입니다.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 1. 멤버 상태 'has_left = 1' 플래그 설정
        $pdo->prepare("UPDATE chat_room_members SET has_left = 1 WHERE room_id = ? AND user_id = ?")
            ->execute([$room_id, $user_id]);

        // 2. 대화방 테이블(chat_rooms)의 deleted_user_ids 플래그에 본인 ID 추가
        $r_chk = $pdo->prepare("SELECT deleted_user_ids FROM chat_rooms WHERE id = ? LIMIT 1");
        $r_chk->execute([$room_id]);
        $existing_del = $r_chk->fetchColumn();

        $del_users = !empty($existing_del) ? explode(',', (string)$existing_del) : [];
        if (!in_array((string)$user_id, $del_users, true)) {
            $del_users[] = (string)$user_id;
        }
        $new_del_str = implode(',', array_filter($del_users));

        $pdo->prepare("UPDATE chat_rooms SET deleted_user_ids = ? WHERE id = ?")
            ->execute([$new_del_str, $room_id]);

        // 3. 안내 시스템 메시지 전송 (기존 대화 이력 보존)
        $pdo->prepare("INSERT INTO chat_messages (room_id, sender_id, message) VALUES (?, ?, ?)")
            ->execute([$room_id, $user_id, "{$username}님이 대화방을 나갔습니다."]);

        echo json_encode(['status' => 'success', 'message' => '대화방에서 나갔습니다.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => '지원하지 않는 액션입니다.'], JSON_UNESCAPED_UNICODE);
    exit;

} catch (\Throwable $fatalEx) {
    echo json_encode([
        'status' => 'error',
        'message' => '시스템 처리 오류: ' . $fatalEx->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}