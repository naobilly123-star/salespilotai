<?php
/* ========================================================================== */
/* 👤 본인 정보 실시간 수정 비동기(AJAX) 처리 엔진 (update_my_profile.php) */
/* - 개인(예비창업자) 및 사업자 회원 투트랙 완벽 지원 */
/* ========================================================================== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();

header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => '로그인이 필요합니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $store_name = trim($_POST['my_store_name'] ?? '');
    $owner_name = trim($_POST['my_owner_name'] ?? '');
    $phone      = trim($_POST['my_phone'] ?? '');
    $email      = trim($_POST['my_email'] ?? '');
    $biz_no     = trim($_POST['my_biz_no'] ?? '');
    $address    = trim($_POST['my_address'] ?? '');
    $new_pwd    = $_POST['my_new_password'] ?? '';

    $biz_category = trim($_POST['my_biz_category'] ?? '');
    $biz_sub      = trim($_POST['my_biz_sub'] ?? '');
    $biz_custom   = trim($_POST['my_biz_custom'] ?? '');

    // 현재 사용자 기존 정보 조회
    $u_chk = $pdo->prepare("SELECT role, biz_type, store_name FROM users WHERE id = :id LIMIT 1");
    $u_chk->execute(['id' => $user_id]);
    $curr_u = $u_chk->fetch(PDO::FETCH_ASSOC);

    $is_personal = ($curr_u && (strpos($curr_u['biz_type'], '예비창업') !== false || $curr_u['role'] === 'USER' && empty($biz_no)));

    if ($is_personal && empty($biz_category)) {
        $biz_type = !empty($curr_u['biz_type']) ? $curr_u['biz_type'] : '예비창업/개인 시뮬레이션';
    } else {
        if ($biz_sub === '직접입력' || $biz_category === '기타 업종') {
            $final_sub = !empty($biz_custom) ? $biz_custom : '기타상세';
            $biz_type  = $biz_category . ' > ' . $final_sub;
        } elseif (!empty($biz_category) && !empty($biz_sub)) {
            $biz_type  = $biz_category . ' > ' . $biz_sub;
        } else {
            $biz_type  = !empty($biz_category) ? $biz_category : ($curr_u['biz_type'] ?? '기타/미지정');
        }
    }

    if (empty($store_name)) {
        echo json_encode(['status' => 'error', 'message' => ($is_personal ? '성명(이름)을 입력해주세요.' : '점포명(상호명)을 입력해주세요.')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        if (!empty($new_pwd)) {
            $hashed = password_hash($new_pwd, PASSWORD_DEFAULT);
            $sql = "UPDATE users 
                    SET store_name = :store_name, owner_name = :owner_name, phone = :phone, email = :email, 
                        biz_no = :biz_no, biz_type = :biz_type, address = :address, password = :password 
                    WHERE id = :id";
            $params = [
                'store_name' => $store_name,
                'owner_name' => $owner_name,
                'phone'      => $phone,
                'email'      => $email,
                'biz_no'     => $biz_no,
                'biz_type'   => $biz_type,
                'address'    => $address,
                'password'   => $hashed,
                'id'         => $user_id
            ];
        } else {
            $sql = "UPDATE users 
                    SET store_name = :store_name, owner_name = :owner_name, phone = :phone, email = :email, 
                        biz_no = :biz_no, biz_type = :biz_type, address = :address 
                    WHERE id = :id";
            $params = [
                'store_name' => $store_name,
                'owner_name' => $owner_name,
                'phone'      => $phone,
                'email'      => $email,
                'biz_no'     => $biz_no,
                'biz_type'   => $biz_type,
                'address'    => $address,
                'id'         => $user_id
            ];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $_SESSION['store_name'] = $store_name;

        echo json_encode([
            'status' => 'success',
            'message' => '회원 정보가 성공적으로 수정되었습니다.'
        ], JSON_UNESCAPED_UNICODE);
    } catch (PDOException $e) {
        error_log("Update Profile Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => '데이터베이스 처리 중 오류가 발생했습니다.'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}