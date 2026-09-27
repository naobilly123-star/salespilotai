<?php
/* 외부 라이브러리 의존 없는 순수 소켓 기반 보안 SMTP 발송 엔진 (Naver/Gmail 완벽 호환) */

class SimpleSMTP {
    private $host;
    private $port;
    private $username;
    private $password;
    private $timeout = 15;
    private $socket;
    public $error = '';

    public function __construct($host, $port, $username, $password) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    private function getResponse() {
        $data = '';
        while ($str = fgets($this->socket, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) === ' ') { break; }
        }
        return $data;
    }

    private function sendCommand($cmd, $expectedCode) {
        fputs($this->socket, $cmd . "\r\n");
        $res = $this->getResponse();
        $code = substr($res, 0, 3);
        if ($code != $expectedCode) {
            $this->error = "SMTP Error [{$cmd}]: Expected {$expectedCode}, received: {$res}";
            return false;
        }
        return true;
    }

    public function send($to, $subject, $htmlBody, $replyToEmail = '', $replyToName = '') {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $transport = ($this->port == 465) ? 'ssl://' : 'tcp://';
        $this->socket = @stream_socket_client(
            $transport . $this->host . ':' . $this->port,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$this->socket) {
            $this->error = "Connection failed: {$errstr} ({$errno})";
            return false;
        }

        $this->getResponse();

        // 1. EHLO 핸드셰이크
        if (!$this->sendCommand("EHLO " . gethostname(), 250)) {
            fclose($this->socket);
            return false;
        }

        // 포트가 587(STARTTLS)인 경우 TLS 핸드셰이크
        if ($this->port == 587) {
            if (!$this->sendCommand("STARTTLS", 220)) {
                fclose($this->socket);
                return false;
            }
            stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $this->sendCommand("EHLO " . gethostname(), 250);
        }

        // 2. AUTH LOGIN 인증
        if (!$this->sendCommand("AUTH LOGIN", 334)) {
            fclose($this->socket);
            return false;
        }
        if (!$this->sendCommand(base64_encode($this->username), 334)) {
            fclose($this->socket);
            return false;
        }
        if (!$this->sendCommand(base64_encode($this->password), 235)) {
            $this->error = "Authentication failed: 아이디 또는 애플리케이션 비밀번호를 확인해주세요.";
            fclose($this->socket);
            return false;
        }

        // 3. 발신자 / 수신자 설정 (네이버 정책: 발신자는 로그인한 계정과 일치해야 함)
        $fromEmail = $this->username;
        if (strpos($fromEmail, '@') === false) {
            $fromEmail .= '@naver.com';
        }

        if (!$this->sendCommand("MAIL FROM:<{$fromEmail}>", 250)) {
            fclose($this->socket);
            return false;
        }
        if (!$this->sendCommand("RCPT TO:<{$to}>", 250)) {
            fclose($this->socket);
            return false;
        }

        // 4. DATA 명령 및 본문 전송
        if (!$this->sendCommand("DATA", 354)) {
            fclose($this->socket);
            return false;
        }

        $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
        $encodedFromName = "=?UTF-8?B?" . base64_encode("나오빌리 고객센터") . "?=";

        $headers = [];
        $headers[] = "From: {$encodedFromName} <{$fromEmail}>";
        $headers[] = "To: <{$to}>";
        if (!empty($replyToEmail)) {
            $encodedReplyName = !empty($replyToName) ? "=?UTF-8?B?" . base64_encode($replyToName) . "?=" : "";
            $headers[] = "Reply-To: {$encodedReplyName} <{$replyToEmail}>";
        }
        $headers[] = "Subject: {$encodedSubject}";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: base64";
        $headers[] = "Date: " . date("r");
        $headers[] = "X-Mailer: BizProfit-SimpleSMTP";

        $fullData = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($htmlBody)) . "\r\n.";

        fputs($this->socket, $fullData . "\r\n");
        $res = $this->getResponse();
        
        $this->sendCommand("QUIT", 221);
        fclose($this->socket);

        return (substr($res, 0, 3) == 250);
    }
}