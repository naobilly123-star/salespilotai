<?php
/* ========================================================================== */
/* ⚡ 고속 파일 캐싱 & 세션 락 해제 헬퍼 (api_cache_helper.php)                 */
/* - Ubuntu 24.04 최적화, 외부 API 지연 시 워커 고사 원천 방지                  */
/* ========================================================================== */

if (!function_exists('getSafeCachedApiData')) {
    /**
     * 외부 API 호출 결과를 캐싱하여 반환 (TTL 초 단위)
     */
    function getSafeCachedApiData($cacheKey, $ttlSeconds, $fetchCallback) {
        $cacheDir = sys_get_temp_dir() . '/salespilot_cache';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }
        $cacheFile = $cacheDir . '/' . md5($cacheKey) . '.json';

        // 유효한 캐시가 있으면 즉시 반환 (외부 API 통신 생략)
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $ttlSeconds)) {
            $cached = @file_get_contents($cacheFile);
            if ($cached !== false) {
                $decoded = json_decode($cached, true);
                if ($decoded !== null) {
                    return $decoded;
                }
            }
        }

        // 캐시 만료 시에만 콜백 실행
        $freshData = $fetchCallback();
        if ($freshData !== null && $freshData !== false) {
            @file_put_contents($cacheFile, json_encode($freshData, JSON_UNESCAPED_UNICODE), LOCK_EX);
        } elseif (file_exists($cacheFile)) {
            // 외부 API 장애 시 이전 캐시 데이터로 자동 Fallback
            $stale = @file_get_contents($cacheFile);
            if ($stale !== false) {
                return json_decode($stale, true);
            }
        }

        return $freshData;
    }
}

if (!function_exists('releaseSessionLock')) {
    /**
     * 데이터 변경이 없는 단순 조회 요청 시 세션 락을 즉시 해제하여 병목 제거
     */
    function releaseSessionLock() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['action'])) {
                session_write_close();
            }
        }
    }
}