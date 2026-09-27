<?php
/* ==============================================================================
 * 1:1 온라인 문의 첨부파일 즉시 다운로드 스트리밍 엔진
 * - 파일 저장 경로 직접 노출 방지 및 안전한 다운로드 헤더 전송
 * - 디렉터리 순회(../) 공격 차단 및 확장자/존재 여부 검증
 * ============================================================================== */

// 한국 표준시 설정
date_default_timezone_set('Asia/Seoul');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. 요청 파라미터 수신 및 정제
$file = trim($_GET['file'] ?? '');
$orig_name = trim($_GET['name'] ?? '');

if (empty($file)) {
    http_response_code(400);
    exit('잘못된 요청입니다. (파일 파라미터 누락)');
}

// 2. 보안 검증: 디렉터리 순회(../ 및 경로 문자) 제거
$safe_file = basename($file);
$safe_orig_name = !empty($orig_name) ? basename($orig_name) : $safe_file;

// 업로드 디렉터리 절대 경로 지정
$upload_dir = __DIR__ . '/uploads/inquiries/';
$filepath = $upload_dir . $safe_file;

// 3. 실제 파일 존재 여부 및 유효성 확인
if (!file_exists($filepath) || !is_file($filepath)) {
    http_response_code(404);
    exit('요청하신 첨부파일을 서버에서 찾을 수 없습니다. 이미 삭제되었거나 경로가 올바르지 않습니다.');
}

// 4. 실행 파일 등 비정상 다운로드 차단
$ext = strtolower(pathinfo($safe_file, PATHINFO_EXTENSION));
$disallowed_ext = ['php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'cgi', 'pl', 'sh', 'bat', 'cmd', 'exe', 'js', 'jsp', 'asp', 'aspx'];
if (in_array($ext, $disallowed_ext, true)) {
    http_response_code(403);
    exit('보안 정책상 다운로드할 수 없는 파일 형식입니다.');
}

// 5. 브라우저 및 OS별 파일명 인코딩 호환 처리 (RFC 5987 호환 UTF-8 인코딩)
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$encoded_filename = rawurlencode($safe_orig_name);

// 6. 다운로드 강제 전송 헤더 출력 (브라우저 열람 대신 즉시 다운로드 창 호출)
if (ob_get_level()) {
    ob_end_clean();
}

$filesize = filesize($filepath);
$mime_type = 'application/octet-stream';

if (function_exists('mime_content_type')) {
    $detected_mime = @mime_content_type($filepath);
    if ($detected_mime) {
        $mime_type = $detected_mime;
    }
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $safe_orig_name) . '"; filename*=UTF-8\'\'' . $encoded_filename);
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . $filesize);

// 7. 대용량 파일 버퍼링 메모리 초과 방지 스트리밍 출력
$handle = fopen($filepath, 'rb');
if ($handle !== false) {
    while (!feof($handle)) {
        echo fread($handle, 8192);
        flush();
    }
    fclose($handle);
} else {
    readfile($filepath);
}
exit;