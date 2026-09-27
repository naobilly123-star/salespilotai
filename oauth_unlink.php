<?php
/* ========================================================================== */
/* BizProfit AI 소상공인 매출분석 플랫폼 - 소셜 연결 끊기(Unlink) 콜백 수신 */
/* - 네이버/카카오/구글 사용자가 외부 포털에서 서비스 연동 해제 시 자동 처리 */
/* - DB users 테이블의 oauth_id 및 oauth_provider 초기화(안전한 탈퇴/연동 해제) */
/* - 네이버 공식 웹훅 응답 규격 {"result":"success"} JSON 출력 */
/* ========================================================================== */

// 세션 시작 (필요 시 세션 무효화 대응)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DB 연결
require_once 'db.php';

// 응답 헤더 설정 (JSON)
header('Content-Type: application/json; charset=UTF-8');

// 공급자(provider) 식별
$provider = trim($_GET['provider'] ?? 'naver');

// 1. 요청 파라미터 또는 POST 페이로드 파싱
// 네이버는 연결 끊기 콜백 시 사용자 식별키를 파라미터(unique_id 또는 user_id)로 전달합니다.
$oauth_id = trim($_REQUEST['unique_id'] ?? ($_REQUEST['user_id'] ?? ($_REQUEST['id'] ?? '')));

// 만약 JSON Body 형태로 들어오는 경우 처리
if (empty($oauth_id)) {
    $raw_input = file_get_contents('php://input');
    if (!empty($raw_input)) {
        $json_data = json_decode($raw_input, true);
        if (is_array($json_data)) {
            $oauth_id = trim($json_data['unique_id'] ?? ($json_data['user_id'] ?? ($json_data['id'] ?? '')));
        }
    }
}

// 2. 고유 식별값(ID) 누락 검증
if (empty($oauth_id)) {
    http_response_code(400);
    echo json_encode([
        'result'  => 'fail',
        'message' => '사용자 식별자(unique_id)가 제공되지 않았습니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. DB 소셜 연동 해제 처리
try {
    // 3-1. 해당 소셜 계정으로 연동된 회원 조회
    $stmt = $pdo->prepare("SELECT id, username, store_name FROM users WHERE oauth_provider = :provider AND oauth_id = :oauth_id LIMIT 1");
    $stmt->execute([
        'provider' => $provider,
        'oauth_id' => $oauth_id
    ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // 3-2. 안전한 연결 해제 (소셜 연동 정보만 NULL로 초기화하여 기존 데이터 보존)
        // 만약 완전 탈퇴 처리를 원하시면 DELETE 쿼리로 전환할 수 있습니다.
        $unlink_stmt = $pdo->prepare("
            UPDATE users 
            SET oauth_provider = NULL, 
                oauth_id = NULL 
            WHERE id = :id
        ");
        $unlink_stmt->execute(['id' => $user['id']]);

        // 만약 현재 연동 해제된 회원이 현재 브라우저에 로그인 중인 경우 세션 정리
        if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$user['id']) {
            session_unset();
            session_destroy();
        }
    }

    // 4. 네이버/소셜 서버에 성공 규격 응답 반환
    http_response_code(200);
    echo json_encode([
        'result'  => 'success',
        'message' => '성공적으로 연결 해제 처리되었습니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    // DB 예외 발생 시 에러 로깅 및 실패 응답
    http_response_code(500);
    echo json_encode([
        'result'  => 'fail',
        'message' => '서버 내부 오류로 연동 해제 처리에 실패했습니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}