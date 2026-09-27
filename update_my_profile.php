<?php
/* 회원 본인 정보 수정 독립 처리 엔드포인트 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => '로그인이 필요합니다.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => '잘못된 접근 방식입니다.']);
    exit;
}

$my_store_name   = trim($_POST['my_store_name'] ?? '');
$my_owner_name   = trim($_POST['my_owner_name'] ?? '');
$my_phone        = trim($_POST['my_phone'] ?? '');
$my_email        = trim($_POST['my_email'] ?? '');
$my_biz_no       = trim($_POST['my_biz_no'] ?? '');
$my_address      = trim($_POST['my_address'] ?? '');
$my_new_password = $_POST['my_new_password'] ?? '';

$my_biz_category = trim($_POST['my_biz_category'] ?? '');
$my_biz_sub      = trim($_POST['my_biz_sub'] ?? '');
$my_biz_custom   = trim($_POST['my_biz_custom'] ?? '');

if ($my_biz_sub === '직접입력' || $my_biz_category === '기타 업종') {
    $final_sub = !empty($my_biz_custom) ? $biz_custom : '기타상세';
    $my_biz_type = $my_biz_category . ' > ' . $final_sub;
} elseif (!empty($my_biz_category) && !empty($my_biz_sub)) {
    $my_biz_type = $my_biz_category . ' > ' . $my_biz_sub;
} else {
    $my_biz_type = !empty($my_biz_category) ? $my_biz_category : '기타/미지정';
}

if (empty($my_store_name)) {
    echo json_encode(['status' => 'error', 'message' => '상호명(점포명)은 필수 입력 항목입니다.']);
    exit;
}

try {
    // 1. users 테이블 갱신
    if (!empty($my_new_password)) {
        $hashed_pwd = password_hash($my_new_password, PASSWORD_DEFAULT);
        $upd_stmt = $pdo->prepare("
            UPDATE users 
            SET store_name = :store_name, owner_name = :owner_name, phone = :phone, 
                email = :email, biz_no = :biz_no, biz_type = :biz_type, 
                address = :address, password = :password 
            WHERE id = :id
        ");
        $upd_stmt->execute([
            'store_name' => $my_store_name,
            'owner_name' => $my_owner_name,
            'phone'      => $my_phone,
            'email'      => $my_email,
            'biz_no'     => $my_biz_no,
            'biz_type'   => $my_biz_type,
            'address'    => $my_address,
            'password'   => $hashed_pwd,
            'id'         => $user_id
        ]);
    } else {
        $upd_stmt = $pdo->prepare("
            UPDATE users 
            SET store_name = :store_name, owner_name = :owner_name, phone = :phone, 
                email = :email, biz_no = :biz_no, biz_type = :biz_type, 
                address = :address 
            WHERE id = :id
        ");
        $upd_stmt->execute([
            'store_name' => $my_store_name,
            'owner_name' => $my_owner_name,
            'phone'      => $my_phone,
            'email'      => $my_email,
            'biz_no'     => $my_biz_no,
            'biz_type'   => $my_biz_type,
            'address'    => $my_address,
            'id'         => $user_id
        ]);
    }

    // 2. stores 테이블 동기화
    try {
        $chk_store = $pdo->prepare("SELECT id FROM stores WHERE user_id = :uid LIMIT 1");
        $chk_store->execute(['uid' => $user_id]);
        if ($chk_store->fetch()) {
            $upd_s = $pdo->prepare("UPDATE stores SET store_name = :sname, biz_no = :bno, address = :addr WHERE user_id = :uid");
            $upd_s->execute([
                'sname' => $my_store_name,
                'bno'   => $my_biz_no,
                'addr'  => $my_address,
                'uid'   => $user_id
            ]);
        }
    } catch (\Throwable $exStore) {}

    // 세션 상호명 동기화
    $_SESSION['store_name'] = $my_store_name;

    echo json_encode(['status' => 'success', 'message' => '회원 정보가 성공적으로 수정되었습니다.']);
    exit;

} catch (\PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => '데이터베이스 처리 오류: ' . $e->getMessage()]);
    exit;
}