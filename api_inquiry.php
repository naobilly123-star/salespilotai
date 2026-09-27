<?php
/* 1:1 온라인 문의 비동기 처리 API - 한국 표준시(KST), 다중 첨부파일 저장 및 메일 내 원클릭 다운로드 100% 완결 엔진 */
// 1. 한국 표준시(Asia/Seoul, UTC+9) 전역 강제 설정
date_default_timezone_set('Asia/Seoul');

@ini_set('memory_limit', '256M');
@ini_set('max_execution_time', '120');

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';
require_once 'smtp_mailer.php';

// MySQL 세션 타임존도 한국 표준시(+09:00)로 동기화 (NOW() 함수 일치)
try {
    $pdo->exec("SET time_zone = '+09:00'");
} catch (\Throwable $tzEx) {
    // 세션 타임존 설정 실패 시 무시하고 진행
}

// ==============================================================================
// 네이버 SMTP 인증 접속 정보 설정
// ==============================================================================
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.naver.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 465);
if (!defined('SMTP_USER')) define('SMTP_USER', 'naobilly123');
if (!defined('SMTP_PASS')) define('SMTP_PASS', 'RZ7KMNYPNPGJ');
if (!defined('ADMIN_RECV_EMAIL')) define('ADMIN_RECV_EMAIL', 'naobilly123@naver.com');
// ==============================================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '잘못된 요청 방식입니다.']);
    exit;
}

// 서버 post_max_size 초과 시 방어
if (empty($_POST) && !empty($_SERVER['CONTENT_LENGTH'])) {
    echo json_encode(['success' => false, 'message' => '첨부파일 용량이 서버 허용량을 초과했습니다. 다시 시도해 주세요.']);
    exit;
}

$author_name  = trim($_POST['author_name'] ?? '');
$email        = trim($_POST['email'] ?? '');
$phone        = trim($_POST['phone'] ?? '');
$inquiry_type = trim($_POST['inquiry_type'] ?? '');
$message      = trim($_POST['message'] ?? '');
$privacy      = isset($_POST['privacy']) ? 1 : 0;
$user_id      = $_SESSION['user_id'] ?? null;

// 현재 한국 표준시 기준 일시 취득
$now_time = date('Y-m-d H:i:s');

// 필수 입력값 검증
if (empty($author_name) || empty($email) || empty($inquiry_type) || empty($message)) {
    echo json_encode(['success' => false, 'message' => '필수 항목(*)을 모두 입력해 주세요.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => '올바른 이메일 주소를 입력해 주세요.']);
    exit;
}

if (!$privacy) {
    echo json_encode(['success' => false, 'message' => '개인정보 수집 및 이용 동의가 필요합니다.']);
    exit;
}

// 다중 첨부파일 안전 저장 처리
$saved_attachments = [];
$disallowed_ext = ['php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'cgi', 'pl', 'sh', 'bat', 'cmd', 'exe', 'js', 'jsp', 'asp', 'aspx'];
$upload_dir = __DIR__ . '/uploads/inquiries/';

if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

$files_to_process = [];
foreach (['attachments', 'attachment'] as $file_field) {
    if (isset($_FILES[$file_field])) {
        if (is_array($_FILES[$file_field]['name'])) {
            $cnt = count($_FILES[$file_field]['name']);
            for ($i = 0; $i < $cnt; $i++) {
                if (!empty($_FILES[$file_field]['name'][$i])) {
                    $files_to_process[] = [
                        'name'     => $_FILES[$file_field]['name'][$i],
                        'tmp_name' => $_FILES[$file_field]['tmp_name'][$i],
                        'error'    => $_FILES[$file_field]['error'][$i],
                        'size'     => $_FILES[$file_field]['size'][$i]
                    ];
                }
            }
        } else {
            if (!empty($_FILES[$file_field]['name'])) {
                $files_to_process[] = $_FILES[$file_field];
            }
        }
    }
}

// 최대 5개 파일 저장 처리
$process_limit = min(5, count($files_to_process));
for ($i = 0; $i < $process_limit; $i++) {
    $f = $files_to_process[$i];
    if ($f['error'] === UPLOAD_ERR_OK && is_uploaded_file($f['tmp_name'])) {
        $orig_name = basename($f['name']);
        $size      = (int)$f['size'];
        $ext       = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

        if (in_array($ext, $disallowed_ext, true)) {
            continue;
        }

        $save_name = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '_' . ($i + 1) . '.' . $ext;
        $dest_path = $upload_dir . $save_name;

        if (move_uploaded_file($f['tmp_name'], $dest_path)) {
            $saved_attachments[] = [
                'orig_name' => $orig_name,
                'save_name' => $save_name,
                'file_size' => $size
            ];
        }
    }
}

// DB 보존용 변수 세팅
$attachments_json = !empty($saved_attachments) ? json_encode($saved_attachments, JSON_UNESCAPED_UNICODE) : null;
$first_orig_name  = !empty($saved_attachments) ? $saved_attachments[0]['orig_name'] : null;
$first_save_name  = !empty($saved_attachments) ? $saved_attachments[0]['save_name'] : null;
$first_file_size  = !empty($saved_attachments) ? $saved_attachments[0]['file_size'] : 0;

$type_labels = [
    'api'     => 'API 연동 및 동기화 오류',
    'calc'    => '정산 및 순이익 산출 문의',
    'account' => '계정 및 사업자 정보 변경',
    'billing' => '유료 결제 및 세금계산서',
    'etc'     => '기타 제휴 및 일반 문의'
];
$inquiry_label = $type_labels[$inquiry_type] ?? $inquiry_type;

try {
    // 테이블 컬럼 존재 여부 확인 후 자동 추가
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM inquiries")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('attachments_json', $cols)) {
            $pdo->exec("ALTER TABLE inquiries ADD COLUMN attachments_json LONGTEXT NULL DEFAULT NULL AFTER message");
        }
        if (!in_array('attachment_orig_name', $cols)) {
            $pdo->exec("ALTER TABLE inquiries ADD COLUMN attachment_orig_name VARCHAR(255) NULL DEFAULT NULL AFTER message");
        }
        if (!in_array('attachment_save_name', $cols)) {
            $pdo->exec("ALTER TABLE inquiries ADD COLUMN attachment_save_name VARCHAR(255) NULL DEFAULT NULL AFTER attachment_orig_name");
        }
        if (!in_array('attachment_file_size', $cols)) {
            $pdo->exec("ALTER TABLE inquiries ADD COLUMN attachment_file_size INT UNSIGNED NULL DEFAULT 0 AFTER attachment_save_name");
        }
        if (!in_array('privacy_agreed_at', $cols)) {
            $pdo->exec("ALTER TABLE inquiries ADD COLUMN privacy_agreed_at DATETIME NULL DEFAULT NULL AFTER privacy_agree");
        }
    } catch (\Throwable $colEx) {
        error_log("Column Alter Warning: " . $colEx->getMessage());
    }

    // 한국 시간 변수($now_time)를 직접 삽입하여 시간 일치 보장
    $stmt = $pdo->prepare("INSERT INTO inquiries 
        (user_id, author_name, email, phone, inquiry_type, message, attachment_orig_name, attachment_save_name, attachment_file_size, attachments_json, privacy_agree, privacy_agreed_at, status, created_at) 
        VALUES (:user_id, :author_name, :email, :phone, :inquiry_type, :message, :orig_name, :save_name, :file_size, :att_json, :privacy, :agreed_at, 'PENDING', :created_at)");
        
    $stmt->execute([
        'user_id'    => $user_id,
        'author_name'=> $author_name,
        'email'      => $email,
        'phone'      => $phone,
        'inquiry_type' => $inquiry_type,
        'message'    => $message,
        'orig_name'  => $first_orig_name,
        'save_name'  => $first_save_name,
        'file_size'  => $first_file_size,
        'att_json'   => $attachments_json,
        'privacy'    => $privacy,
        'agreed_at'  => $now_time,
        'created_at' => $now_time
    ]);
    
    $new_inquiry_id = $pdo->lastInsertId();

    // ==============================================================================
    // 최고관리자 실시간 메일 전송 (이메일 내 첨부파일 즉시 다운로드 완벽 지원)
    // ==============================================================================
    try {
        $user_status_label = !empty($user_id) ? "등록회원 (User ID: {$user_id})" : "비회원 접수";
        $contact_phone     = !empty($phone) ? htmlspecialchars($phone) : '미입력';
        $formatted_msg     = nl2br(htmlspecialchars($message));

        /* ==========================================================================
         * 이메일 발송 시 첨부파일 즉시 다운로드 URL 및 디자인 테이블/버튼 생성
         * - download_inquiry_file.php 와 결합하여 메일에서 원클릭으로 바로 다운로드 실행
         * ========================================================================== */
        $attachment_html = "<span style='color:#94a3b8;'>첨부파일 없음</span>";
        if (!empty($saved_attachments)) {
            $att_rows = [];
            // 기본 서비스 도메인 설정 (환경에 맞게 자동 지정)
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $host = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'salespilotai.kr';
            $base_url = $protocol . $host;

            foreach ($saved_attachments as $idx => $att) {
                $file_kb = number_format($att['file_size'] / 1024, 1);
                $orig_filename = htmlspecialchars($att['orig_name']);
                
                // 첨부파일 바로 다운로드 링크 생성
                $download_url = $base_url . "/download_inquiry_file.php?file=" . rawurlencode($att['save_name']) . "&name=" . rawurlencode($att['orig_name']);

                $att_rows[] = "
                <div style='background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; margin-bottom:8px; display:flex; align-items:center; justify-content:space-between;'>
                    <div style='font-size:12.5px; color:#1e293b; max-width:70%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;'>
                        <strong style='color:#2563eb;'>📎 [" . ($idx + 1) . "]</strong>
                        <span style='font-weight:600;'>{$orig_filename}</span>
                        <span style='color:#64748b; font-size:11px; margin-left:4px;'>({$file_kb} KB)</span>
                    </div>
                    <div>
                        <a href='{$download_url}' target='_blank' style='display:inline-block; padding:5px 12px; background-color:#2563eb; color:#ffffff; font-size:11px; font-weight:700; text-decoration:none; border-radius:50px; box-shadow:0 2px 5px rgba(37,99,235,0.2);'>
                            다운로드
                        </a>
                    </div>
                </div>";
            }
            $attachment_html = implode('', $att_rows);
        }
        /* ========================================================================== */

        $mail_subject = "[나오빌리] 새로운 1:1 온라인 문의가 접수되었습니다 (#{$new_inquiry_id})";

        /* ==========================================================================
         * 세련되고 가독성 높은 반응형 HTML 메일 템플릿
         * ========================================================================== */
        $mail_body = "
        <!DOCTYPE html>
        <html lang='ko'>
        <head><meta charset='UTF-8'></head>
        <body style='margin:0; padding:30px 10px; background-color:#f8fafc; font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif;'>
            <div style='max-width:640px; margin:0 auto; background:#ffffff; border-radius:14px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 4px 15px rgba(0,0,0,0.05);'>
                
                <!-- 상단 헤더 -->
                <div style='background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding:24px 28px; color:#ffffff;'>
                    <h2 style='margin:0; font-size:18px; font-weight:700;'>[나오빌리] 1:1 신규 온라인 문의 접수</h2>
                    <p style='margin:6px 0 0 0; font-size:12px; color:#94a3b8;'>고객지원센터를 통해 신규 문의가 접수되었습니다. 첨부파일 및 내용을 확인해 주세요.</p>
                </div>
                
                <!-- 본문 컨텐츠 -->
                <div style='padding:28px;'>
                    <table style='width:100%; border-collapse:collapse; margin-bottom:24px; font-size:13px; color:#334155;'>
                        <tr style='border-bottom:1px solid #f1f5f9;'>
                            <td style='padding:10px 0; width:130px; font-weight:700; color:#64748b;'>문의 번호</td>
                            <td style='padding:10px 0; font-weight:700; color:#2563eb;'>#{$new_inquiry_id}</td>
                        </tr>
                        <tr style='border-bottom:1px solid #f1f5f9;'>
                            <td style='padding:10px 0; font-weight:700; color:#64748b;'>회원 구분</td>
                            <td style='padding:10px 0;'>{$user_status_label}</td>
                        </tr>
                        <tr style='border-bottom:1px solid #f1f5f9;'>
                            <td style='padding:10px 0; font-weight:700; color:#64748b;'>작성자 / 상호명</td>
                            <td style='padding:10px 0; font-weight:600; color:#0f172a;'>" . htmlspecialchars($author_name) . "</td>
                        </tr>
                        <tr style='border-bottom:1px solid #f1f5f9;'>
                            <td style='padding:10px 0; font-weight:700; color:#64748b;'>회신 이메일</td>
                            <td style='padding:10px 0;'><a href='mailto:{$email}' style='color:#2563eb; text-decoration:none; font-weight:600;'>{$email}</a></td>
                        </tr>
                        <tr style='border-bottom:1px solid #f1f5f9;'>
                            <td style='padding:10px 0; font-weight:700; color:#64748b;'>연락처</td>
                            <td style='padding:10px 0;'>{$contact_phone}</td>
                        </tr>
                        <tr style='border-bottom:1px solid #f1f5f9;'>
                            <td style='padding:10px 0; font-weight:700; color:#64748b;'>문의 유형</td>
                            <td style='padding:10px 0;'><span style='display:inline-block; padding:3px 8px; border-radius:6px; background:#eff6ff; color:#2563eb; font-weight:600;'>{$inquiry_label}</span></td>
                        </tr>
                        <tr style='border-bottom:1px solid #f1f5f9;'>
                            <td style='padding:10px 0; font-weight:700; color:#64748b;'>개인정보 동의</td>
                            <td style='padding:10px 0;'>
								<span style='color:#059669; font-weight:700;'>✓ 수집 및 이용 동의 완료</span><br>
								<span style='color:#64748b; font-size:12px; margin-top:3px; display:inline-block;'>({$now_time})</span>
							</td>
                        </tr>
                        <tr>
                            <td style='padding:10px 0; font-weight:700; color:#64748b;'>접수 일시 (KST)</td>
                            <td style='padding:10px 0;'>{$now_time}</td>
                        </tr>
                    </table>

                    <!-- 첨부파일 다운로드 영역 -->
                    <div style='margin-bottom:24px;'>
                        <div style='font-size:13px; font-weight:700; color:#475569; margin-bottom:8px;'>
                            📎 고객 첨부파일 (" . count($saved_attachments) . "개)
                        </div>
                        <div>
                            {$attachment_html}
                        </div>
                    </div>

                    <!-- 문의 상세 내용 -->
                    <div style='margin-bottom:26px;'>
                        <div style='font-size:13px; font-weight:700; color:#475569; margin-bottom:8px;'>문의 내용</div>
                        <div style='background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:18px; font-size:13px; line-height:1.75; color:#1e293b; white-space:pre-wrap;'>{$formatted_msg}</div>
                    </div>

                    <!-- 관리자 바로가기 버튼 -->
                    <div style='text-align:center; padding-top:10px;'>
                        <a href='https://salespilotai.kr/admin_inquiries.htm' target='_blank' style='display:inline-block; padding:12px 30px; background-color:#2563eb; color:#ffffff; font-size:13px; font-weight:700; text-decoration:none; border-radius:50px; box-shadow:0 3px 10px rgba(37,99,235,0.25);'>
                            관리자 페이지에서 확인 및 답변하기 &rarr;
                        </a>
                    </div>
                </div>

                <!-- 메일 푸터 -->
                <div style='background-color:#f1f5f9; padding:14px 28px; font-size:11px; color:#94a3b8; text-align:center;'>
                    본 메일은 나오빌리 고객지원 시스템에서 SMTP 보안 인증을 통해 자동 발송되었습니다.
                </div>
            </div>
        </body>
        </html>";
        /* ========================================================================== */

        $mailer = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
        $isSent = $mailer->send(ADMIN_RECV_EMAIL, $mail_subject, $mail_body, $email, $author_name);

        if (!$isSent) {
            error_log("SMTP Send Failed: " . $mailer->error);
        }
    } catch (\Throwable $mail_ex) {
        error_log("SMTP Process Exception: " . $mail_ex->getMessage());
    }

    echo json_encode([
        'success' => true, 
        'message' => '문의가 정상적으로 접수되었습니다. 담당자 확인 후 신속하게 회신드리겠습니다.'
    ]);

} catch (\PDOException $e) {
    error_log("Inquiry DB Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => '문의 접수 처리 중 서버 오류가 발생했습니다. 잠시 후 다시 시도해 주세요.'
    ]);
}
exit;