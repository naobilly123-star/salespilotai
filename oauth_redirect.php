<?php
/* ========================================================================== */
/* BizProfit AI 소상공인 매출분석 플랫폼 - 소셜 OAuth 리다이렉트 처리기 */
/* - 카카오(Kakao), 네이버(Naver), 구글(Google) 3대 소셜 로그인 공식 규격 대응 */
/* - admin_site_settings.htm에서 저장한 DB(site_settings) 키 값 100% 우선 자동 세팅 */
/* - CSRF 방어를 위한 cryptographically secure state 토큰 자동 생성 및 세션 바인딩 */
/* - 기존 시스템 및 타 기능 무영향 보장 */
/* ========================================================================== */

// 세션 활성화
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DB 연결 로드
require_once 'db.php';

// 공급자(Provider) 식별 및 유효성 검증
$provider = trim($_GET['provider'] ?? '');
$allowed_providers = ['kakao', 'naver', 'google'];

if (!in_array($provider, $allowed_providers, true)) {
    header("Location: login.htm");
    exit;
}

// --------------------------------------------------------------------------
// 기본 설정값 및 콜백 기본 주소 산출
// --------------------------------------------------------------------------
$current_domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$redirect_base_uri = $current_domain . '/oauth_callback.php';

// 기본 클라이언트 자격증명 (DB 설정이 없을 때의 안전 Fallback)
$oauth_config = [
    'kakao' => [
        'client_id'    => '7f48076315416399c3e6746aea3b73c6',
        'redirect_uri' => 'https://salespilotai.kr/oauth_callback.php?provider=kakao',
        'auth_url'     => 'https://kauth.kakao.com/oauth/authorize'
    ],
    'naver' => [
        'client_id'    => 'ape4W6b4gYDWFTBErKDR',
        'redirect_uri' => $redirect_base_uri . '?provider=naver',
        'auth_url'     => 'https://nid.naver.com/oauth2.0/authorize'
    ],
    'google' => [
        'client_id'    => '157590027313-n7l8b5ifnjc7jutm662akfiuqa9cd8cv.apps.googleusercontent.com',
        'redirect_uri' => $redirect_base_uri . '?provider=google',
        'auth_url'     => 'https://accounts.google.com/o/oauth2/v2/auth',
        'scope'        => 'openid email profile'
    ]
];

// --------------------------------------------------------------------------
// DB(site_settings) 테이블에서 실시간 설정값 우선 자동 세팅
// - admin_site_settings.htm에서 저장한 키 값이 존재할 경우 최우선 덮어쓰기 적용
// --------------------------------------------------------------------------
try {
    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE :prefix");
        $stmt->execute(['prefix' => 'oauth_' . $provider . '%']);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = $row['setting_key'];
            $val = trim($row['setting_value'] ?? '');
            
            // 유효한 설정값이 존재하는 경우 자동 동기화
            if (!empty($val) && strpos($val, 'YOUR_') === false) {
                if ($key === "oauth_{$provider}_client_id") {
                    $oauth_config[$provider]['client_id'] = $val; // Client ID 자동 주입
                }
                if ($key === "oauth_{$provider}_redirect_uri") {
                    $oauth_config[$provider]['redirect_uri'] = $val; // Redirect URI 자동 주입
                }
            }
        }
    }
} catch (Exception $e) {
    // DB 읽기 예외 발생 시에도 기본값으로 무중단 구동 유지
}

// --------------------------------------------------------------------------
// CSRF 방어를 위한 난수 State 토큰 생성 및 세션 저장
// --------------------------------------------------------------------------
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state']    = $state;
$_SESSION['oauth_provider'] = $provider;

// --------------------------------------------------------------------------
// DB에서 주입된 최종 자격증명으로 인가 규격 파라미터 조합
// --------------------------------------------------------------------------
$cfg = $oauth_config[$provider];

$params = [
    'response_type' => 'code',
    'client_id'     => $cfg['client_id'],
    'redirect_uri'  => $cfg['redirect_uri']
];

// 제공자별 전용 파라미터 분기
if ($provider === 'naver') {
    $params['state'] = $state;
} elseif ($provider === 'google') {
    $params['state'] = $state;
    $params['scope'] = $cfg['scope'] ?? 'openid email profile';
    $params['access_type'] = 'offline';
    $params['prompt'] = 'select_account';
} elseif ($provider === 'kakao') {
    $params['state'] = $state;
}

// 최종 인가 요청 URL 조립
$auth_redirect_url = $cfg['auth_url'] . '?' . http_build_query($params);

// --------------------------------------------------------------------------
// 소셜 인가 서버로 안전한 즉시 리다이렉트 실행
// --------------------------------------------------------------------------
header("Location: " . $auth_redirect_url);
exit;