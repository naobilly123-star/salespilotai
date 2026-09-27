<?php
/* ========================================================================== */
/* 💳 토스페이먼츠 결제 승인 처리 엔진 (payment_success.php)                    */
/* ========================================================================== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();

require_once 'db.php';

$paymentKey = $_GET['paymentKey'] ?? '';
$orderId    = $_GET['orderId'] ?? '';
$amount     = (int)($_GET['amount'] ?? 0);
$user_id    = $_SESSION['user_id'] ?? null;

if (!$paymentKey || !$orderId || !$amount || !$user_id) {
    header("Location: dashboard.htm?error=invalid_payment_request");
    exit;
}

// 토스페이먼츠 시크릿 키 (테스트 또는 라이브 시크릿 키)
$secretKey = "test_gsk_docs_OaPz8L5KdmQXkzRz3y47BMw6"; 
$credential = base64_encode($secretKey . ":");

// 서버 대 서버 최종 결제 승인 API 호출
$ch = curl_init("https://api.tosspayments.com/v1/payments/confirm");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'paymentKey' => $paymentKey,
        'orderId'    => $orderId,
        'amount'     => $amount
    ]),
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . $credential,
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$resData = json_decode($response, true);

if ($httpCode === 200 && isset($resData['status']) && $resData['status'] === 'DONE') {
    try {
        $pdo->beginTransaction();

        // 1. 결제 내역 저장
        $ins = $pdo->prepare("
            INSERT INTO payment_logs 
            (user_id, order_id, payment_key, amount, method, status, approved_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([
            $user_id,
            $orderId,
            $paymentKey,
            $amount,
            $resData['method'] ?? '간편결제',
            'SUCCESS',
            $resData['approvedAt'] ?? date('Y-m-d H:i:s')
        ]);

        // 2. 유료 회원 권한(STORE/사업자회원) 자동 승격
        $upd = $pdo->prepare("UPDATE users SET role = 'STORE', status = 'APPROVED' WHERE id = ?");
        $upd->execute([$user_id]);
        $_SESSION['role'] = 'STORE';

        $pdo->commit();

        $_SESSION['flash_msg'] = "성공적으로 결제되었습니다. 유료 정회원 혜택이 즉시 활성화되었습니다!";
        header("Location: dashboard.htm?payment=success");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Payment DB Error: " . $e->getMessage());
        header("Location: dashboard.htm?error=db_error");
        exit;
    }
} else {
    // 결제 승인 실패 처리
    $errMsg = $resData['message'] ?? '결제 승인 처리 중 오류가 발생했습니다.';
    error_log("Toss Payment Failed: " . $errMsg);
    header("Location: dashboard.htm?error=" . urlencode($errMsg));
    exit;
}