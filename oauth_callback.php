<?php
/* ========================================================================== */
/* BizProfit AI 소상공인 매출분석 플랫폼 - 소셜 OAuth 콜백 통합 처리기 */
/* - 카카오(Kakao), 네이버(Naver), 구글(Google) 3대 소셜 Access Token & 프로필 수신 */
/* - admin_site_settings.htm에서 관리하는 DB(site_settings) 키/시크릿 자동 연동 */
/* - 카카오 닉네임/프로필/이메일/연락처 정밀 파싱 및 국내 전화번호 규격 자동 변환 */
/* - 기존 시스템 세션 규격($_SESSION['user_id'], store_name, role) 100% 준수 */
/* ========================================================================== */

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DB 연결
require_once 'db.php';

// 공급자(provider) 식별
$provider = trim($_GET['provider'] ?? ($_SESSION['oauth_provider'] ?? ''));
$allowed_providers = ['kakao', 'naver', 'google'];

if (!in_array($provider, $allowed_providers, true)) {
    $_SESSION['error_msg'] = '지원하지 않거나 올바르지 않은 소셜 공급자입니다.';
    header("Location: login.htm");
    exit;
}

// 1. 에러 및 취소 여부 확인
if (isset($_GET['error'])) {
    $err_desc = $_GET['error_description'] ?? $_GET['error'];
    $_SESSION['error_msg'] = '소셜 로그인이 취소되었거나 오류가 발생했습니다: ' . htmlspecialchars($err_desc);
    header("Location: login.htm");
    exit;
}

// 2. 인가 코드(code) 및 상태(state) 검증
$code  = trim($_GET['code'] ?? '');
$state = trim($_GET['state'] ?? '');

if (empty($code)) {
    $_SESSION['error_msg'] = '인증 코드가 전달되지 않았습니다.';
    header("Location: login.htm");
    exit;
}

// CSRF 검증
if (!empty($_SESSION['oauth_state']) && $_SESSION['oauth_state'] !== $state) {
    $_SESSION['error_msg'] = '비정상적인 접근이 감지되었습니다. (CSRF 토큰 불일치)';
    header("Location: login.htm");
    exit;
}
unset($_SESSION['oauth_state']);

// 3. 플랫폼별 기본 자격증명 및 엔드포인트 설정
$current_domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$redirect_uri = $current_domain . '/oauth_callback.php?provider=' . $provider;

// 기본 설정 배열 (Fallback용)
$oauth_config = [
    'kakao' => [
        'client_id'     => '7f48076315416399c3e6746aea3b73c6',
        'client_secret' => 'JnQ4B0XIFrJv5VZ0kCYztVSXqDSLU5g7',
        'redirect_uri'  => 'https://salespilotai.kr/oauth_callback.php?provider=kakao',
        'token_url'     => 'https://kauth.kakao.com/oauth/token',
        'userinfo_url'  => 'https://kapi.kakao.com/v2/user/me'
    ],
    'naver' => [
        'client_id'     => 'ape4W6b4gYDWFTBErKDR',
        'client_secret' => '6b6TbGufz9',
        'redirect_uri'  => $redirect_uri,
        'token_url'     => 'https://nid.naver.com/oauth2.0/token',
        'userinfo_url'  => 'https://openapi.naver.com/v1/nid/me'
    ],
    'google' => [
        'client_id'     => '157590027313-n7l8b5ifnjc7jutm662akfiuqa9cd8cv.apps.googleusercontent.com',
        'client_secret' => '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',
        'redirect_uri'  => $redirect_uri,
        'token_url'     => 'https://oauth2.googleapis.com/token',
        'userinfo_url'  => 'https://www.googleapis.com/oauth2/v3/userinfo'
    ]
];

// --------------------------------------------------------------------------
// DB(site_settings) 테이블에서 클라이언트 키, 시크릿, URI 자동 로드
// - 관리자 페이지에서 변경한 Client ID 및 Secret이 실시간 반영됩니다.
// --------------------------------------------------------------------------
try {
    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE :prefix");
        $stmt->execute(['prefix' => 'oauth_' . $provider . '%']);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $k = $row['setting_key'];
            $v = trim($row['setting_value'] ?? '');
            
            if (!empty($v) && strpos($v, 'YOUR_') === false) {
                if ($k === "oauth_{$provider}_client_id") {
                    $oauth_config[$provider]['client_id'] = $v;     // Client ID DB 자동 주입
                }
                if ($k === "oauth_{$provider}_client_secret") {
                    $oauth_config[$provider]['client_secret'] = $v; // Client Secret DB 자동 주입
                }
                if ($k === "oauth_{$provider}_redirect_uri") {
                    $oauth_config[$provider]['redirect_uri'] = $v;  // Callback URL DB 자동 주입
                }
            }
        }
    }
} catch (Exception $e) {
    // DB 조회 실패 시에도 기본 배열로 무중단 처리
}

$cfg = $oauth_config[$provider];

// 4. cURL 통신 함수
function sendHttpRequest($url, $method = 'GET', $data = [], $headers = []) {
    $ch = curl_init();
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? http_build_query($data) : $data);
    } else {
        if (!empty($data)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($data);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'error' => $error, 'http_code' => $httpCode];
    }
    return ['success' => true, 'data' => json_decode($response, true), 'raw' => $response, 'http_code' => $httpCode];
}

// 5. DB에서 로드된 최종 키로 Access Token 요청
$token_params = [
    'grant_type'   => 'authorization_code',
    'client_id'    => $cfg['client_id'],
    'client_secret'=> $cfg['client_secret'],
    'code'         => $code,
    'redirect_uri' => $cfg['redirect_uri'],
];

if ($provider === 'naver') {
    $token_params['state'] = $state;
}

$token_res = sendHttpRequest($cfg['token_url'], 'POST', $token_params, ['Content-Type: application/x-www-form-urlencoded']);

if (!$token_res['success'] || empty($token_res['data']['access_token'])) {
    $_SESSION['error_msg'] = '소셜 인증 토큰 발급에 실패했습니다. DB 환경설정(Client ID/Secret)을 확인해 주세요.';
    header("Location: login.htm");
    exit;
}

$access_token = $token_res['data']['access_token'];

// 6. Access Token으로 사용자 프로필 조회
$profile_res = sendHttpRequest($cfg['userinfo_url'], 'GET', [], [
    'Authorization: Bearer ' . $access_token
]);

if (!$profile_res['success'] || empty($profile_res['data'])) {
    $_SESSION['error_msg'] = '소셜 사용자 프로필 조회에 실패했습니다.';
    header("Location: login.htm");
    exit;
}

$raw_user = $profile_res['data'];
$oauth_id = '';
$email    = '';
$name     = '';
$phone    = '';

// 7. 플랫폼별 응답 데이터 정밀 파싱
if ($provider === 'kakao') {
    $oauth_id = (string)($raw_user['id'] ?? '');
    $kakao_account = $raw_user['kakao_account'] ?? [];
    
    // 이메일 추출
    $email = trim($kakao_account['email'] ?? '');
    
    // 닉네임 우선순위 순차 탐색
    if (!empty($kakao_account['profile']['nickname'])) {
        $name = trim($kakao_account['profile']['nickname']);
    } elseif (!empty($raw_user['properties']['nickname'])) {
        $name = trim($raw_user['properties']['nickname']);
    } else {
        $name = '대표자(' . substr($oauth_id, -4) . ')';
    }
    
    // 카카오 전화번호 수신 및 국내 번호 자동 변환
    $raw_phone = trim($kakao_account['phone_number'] ?? '');
    if (!empty($raw_phone)) {
        $clean_phone = preg_replace('/[^0-9+]/', '', $raw_phone);
        if (strpos($clean_phone, '+82') === 0) {
            $phone = '0' . substr($clean_phone, 3);
        } else {
            $phone = $clean_phone;
        }
        if (strlen($phone) === 11) {
            $phone = substr($phone, 0, 3) . '-' . substr($phone, 3, 4) . '-' . substr($phone, 7);
        }
    }
} elseif ($provider === 'naver') {
    $naver_account = $raw_user['response'] ?? [];
    $oauth_id = (string)($naver_account['id'] ?? '');
    $email    = trim($naver_account['email'] ?? '');
    $name     = trim($naver_account['name'] ?? ($naver_account['nickname'] ?? '네이버회원'));
    $phone    = trim($naver_account['mobile'] ?? '');
} elseif ($provider === 'google') {
    $oauth_id = (string)($raw_user['sub'] ?? '');
    $email    = trim($raw_user['email'] ?? '');
    $name     = trim($raw_user['name'] ?? '구글회원');
}

if (empty($oauth_id)) {
    $_SESSION['error_msg'] = '소셜 고유 식별값(ID)을 확인할 수 없습니다.';
    header("Location: login.htm");
    exit;
}

// 8. DB 회원 매핑 및 실시간 정보 동기화 처리
try {
    // 8-1. 이미 연동된 회원 조회
    $stmt = $pdo->prepare("SELECT * FROM users WHERE (oauth_provider = :provider AND oauth_id = :oauth_id) LIMIT 1");
    $stmt->execute(['provider' => $provider, 'oauth_id' => $oauth_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 8-2. 이메일이 일치하는 기존 일반 회원이 있을 경우 연동
    if (!$user && !empty($email)) {
        $stmt_email = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt_email->execute(['email' => $email]);
        $existing_user = $stmt_email->fetch(PDO::FETCH_ASSOC);

        if ($existing_user) {
            $user = $existing_user;
            $upd = $pdo->prepare("UPDATE users SET oauth_provider = :provider, oauth_id = :oauth_id WHERE id = :id");
            $upd->execute(['provider' => $provider, 'oauth_id' => $oauth_id, 'id' => $user['id']]);
        }
    }

    // 8-3. 신규 회원 자동 가입
    if (!$user) {
        $auto_username = $provider . '_' . substr(md5($oauth_id), 0, 10);
        $random_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $store_name = $name . '의 사업장';

        $insert = $pdo->prepare("
            INSERT INTO users (username, password, store_name, owner_name, phone, email, status, role, oauth_provider, oauth_id) 
            VALUES (:username, :password, :store_name, :owner_name, :phone, :email, 'APPROVED', 'USER', :oauth_provider, :oauth_id)
        ");

        $insert->execute([
            'username'       => $auto_username,
            'password'       => $random_password,
            'store_name'     => $store_name,
            'owner_name'     => $name,
            'phone'          => $phone,
            'email'          => $email,
            'oauth_provider' => $provider,
            'oauth_id'       => $oauth_id
        ]);

        $new_user_id = $pdo->lastInsertId();

        $stmt_new = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt_new->execute(['id' => $new_user_id]);
        $user = $stmt_new->fetch(PDO::FETCH_ASSOC);
    } else {
        // 기존 정보 최신 프로필로 갱신
        $updates = [];
        $params  = ['id' => $user['id']];

        if (($user['owner_name'] === '카카오회원' || empty($user['owner_name'])) && !empty($name)) {
            $updates[] = "owner_name = :name";
            $params['name'] = $name;
            $user['owner_name'] = $name;
        }
        if (($user['store_name'] === '카카오회원의 매장' || empty($user['store_name'])) && !empty($name)) {
            $updates[] = "store_name = :store_name";
            $params['store_name'] = $name . '의 사업장';
            $user['store_name'] = $name . '의 사업장';
        }
        if (empty($user['email']) && !empty($email)) {
            $updates[] = "email = :email";
            $params['email'] = $email;
            $user['email'] = $email;
        }
        if (empty($user['phone']) && !empty($phone)) {
            $updates[] = "phone = :phone";
            $params['phone'] = $phone;
            $user['phone'] = $phone;
        }

        if (!empty($updates)) {
            $update_sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
            $pdo->prepare($update_sql)->execute($params);
        }
    }

    // 8-4. 승인 상태 확인
    $user_status = $user['status'] ?? 'APPROVED';
    if ($user_status === 'PENDING') {
        $_SESSION['error_msg'] = '회원가입 승인 대기 중입니다. 최고 관리자 승인 후 로그인할 수 있습니다.';
        header("Location: login.htm");
        exit;
    } elseif ($user_status === 'REJECTED') {
        $_SESSION['error_msg'] = '회원가입 신청이 거절된 계정입니다. 관리자에게 문의해 주세요.';
        header("Location: login.htm");
        exit;
    }

    // 8-5. 표준 세션 발급
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['username']   = $user['username'];
    $_SESSION['store_name'] = $user['store_name'];
    $_SESSION['role']       = $user['role'] ?? 'USER';

    // 대시보드 이동
    header("Location: dashboard.htm");
    exit;

} catch (Exception $e) {
    $_SESSION['error_msg'] = '로그인 처리 중 데이터베이스 오류가 발생했습니다: ' . $e->getMessage();
    header("Location: login.htm");
    exit;
}