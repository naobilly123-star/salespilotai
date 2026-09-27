<?php
/* ========================================================================== */
/* [업종 자동화 추가] 공공데이터포털 소상공인시장진흥공단 상권정보 업종코드 동기화 모듈 */
/* ========================================================================== */
require_once 'db.php';

// CLI 크론잡 실행이 아니고 웹 접근인 경우 최고 관리자 권한 체크
if (php_sapi_name() !== 'cli') {
    if (!isset($_SESSION['user_id'])) {
        die(json_encode(['status' => 'error', 'message' => '로그인이 필요합니다.']));
    }
    $chk = $pdo->prepare("SELECT role FROM users WHERE id = :id");
    $chk->execute(['id' => $_SESSION['user_id']]);
    $u = $chk->fetch();
    if (!$u || $u['role'] !== 'ADMIN') {
        die(json_encode(['status' => 'error', 'message' => '관리자 권한이 필요합니다.']));
    }
}

// 공공데이터포털(data.go.kr) 발급 서비스 일반 인증키
$serviceKey = 'YOUR_DATA_GO_KR_API_KEY'; 

// 소상공인진흥공단 상권정보 업종 소분류 코드 API 엔드포인트
$endpoint = "http://apis.data.go.kr/B553077/api/open/sdsc2/baroApi?resId=upjong&type=json&ServiceKey=" . urlencode($serviceKey);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$synced_count = 0;

if ($http_code === 200 && $response) {
    $result = json_decode($response, true);
    $items = $result['body']['items'] ?? [];

    if (!empty($items)) {
        $stmt = $pdo->prepare("
            INSERT INTO business_categories (main_category, sub_category, industry_code, is_active, updated_at)
            VALUES (:main, :sub, :code, 1, NOW())
            ON DUPLICATE KEY UPDATE 
                main_category = VALUES(main_category),
                sub_category = VALUES(sub_category),
                is_active = 1,
                updated_at = NOW()
        ");

        $pdo->beginTransaction();
        try {
            foreach ($items as $item) {
                // 공공데이터 표준 명칭 매핑 (대분류, 중/소분류, 업종코드)
                $main = trim($item['indsLclsNm'] ?? '');
                $sub  = trim($item['indsSclsNm'] ?? ($item['indsMclsNm'] ?? ''));
                $code = trim($item['indsSclsCd'] ?? ($item['indsMclsCd'] ?? ''));

                if (!empty($main) && !empty($sub) && !empty($code)) {
                    $stmt->execute([
                        'main' => $main,
                        'sub'  => $sub,
                        'code' => $code
                    ]);
                    $synced_count++;
                }
            }
            $pdo->commit();
            $msg = "공공데이터 업종코드 동기화 완료: 총 {$synced_count}건 반영됨";
            $status = 'success';
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "DB 저장 실패: " . $e->getMessage();
            $status = 'error';
        }
    } else {
        $msg = "공공데이터 API 응답 데이터가 비어있거나 키 인증에 실패했습니다.";
        $status = 'error';
    }
} else {
    $msg = "공공데이터 서버 통신 실패 (HTTP 상태코드: {$http_code})";
    $status = 'error';
}

if (php_sapi_name() === 'cli') {
    echo "[{$status}] {$msg}\n";
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => $status, 'message' => $msg, 'count' => $synced_count]);
}
?>