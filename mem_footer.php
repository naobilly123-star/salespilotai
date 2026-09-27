<!-- [REFACTORED - 로그인/회원가입 다크 테마 전용 푸터] -->
<style>
    .login-footer {
        width: 100%;
        max-width: 860px;
        text-align: center;
        color: #94a3b8 !important;
        font-size: 0.82rem;
        line-height: 1.65;
        margin-top: 20px;
        letter-spacing: -0.015em;
    }

    .login-footer .policy-links {
        display: flex;
        justify-content: center;
        align-items: center;
        flex-wrap: nowrap;
        margin-bottom: 12px;
    }
    .login-footer .policy-links a {
        color: #cbd5e1 !important;
        text-decoration: none !important;
        font-weight: 500;
        transition: color 0.15s ease;
    }
    .login-footer .policy-links a:hover {
        color: #ffffff !important;
        text-decoration: underline !important;
    }
    .login-footer .policy-links .policy-highlight {
        color: #ffffff !important;
        font-weight: 700 !important;
    }
    .login-footer .policy-divider {
        display: inline-block !important;
        margin: 0 8px !important;
        color: #64748b !important;
        font-weight: 300;
    }

    .login-footer .footer-item {
        display: inline;
        color: #94a3b8 !important;
    }
    .login-footer .footer-label {
        color: #e2e8f0 !important;
        font-weight: 600;
    }
    .login-footer .footer-val {
        color: #94a3b8 !important;
    }
    .login-footer .footer-val a {
        color: #60a5fa !important;
        text-decoration: none !important;
        transition: color 0.15s ease;
    }
    .login-footer .footer-val a:hover {
        color: #93c5fd !important;
        text-decoration: underline !important;
    }
    .login-footer .footer-divider {
        display: inline;
        margin: 0 6px;
        color: #475569 !important;
    }
    .login-footer .copyright-text {
        color: #64748b !important;
        font-size: 0.76rem;
    }

    .login-footer .txt-lbs-full { display: inline; }
    .login-footer .txt-lbs-short { display: none; }

    @media (max-width: 767.98px) {
        .login-footer {
            /* 모바일 뷰포트에서 좌우 중앙 정렬 유지 및 안전 패딩 부여 */
            text-align: left;
            padding: 0 20px !important;
            box-sizing: border-box !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }
        .login-footer .txt-lbs-full { display: none !important; }
        .login-footer .txt-lbs-short { display: inline !important; }

        /* 이용약관-고객센터 양 끝 달라붙음 및 글자 잘림 완전 해소 */
        .login-footer .policy-links {
            display: flex !important;
            flex-wrap: wrap !important; /* 좁은 기기에서 화면 밖으로 넘치지 않도록 wrap 허용 */
            row-gap: 6px !important;   /* 줄바꿈 시 상하 여백 확보 */
            justify-content: center !important;
            align-items: center !important;
            white-space: normal !important; /* nowrap 해제 */
            margin: 0 auto 16px auto !important;
            padding: 0 10px !important; /* 끝단 여백 확보 */
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
        }
        .login-footer .policy-links a {
            font-size: 0.75rem !important; /* [보완] 스마트폰 뷰포트 맞춤 폰트 크기 최적화 */
            letter-spacing: -0.025em !important;
            padding: 2px 4px !important;
            white-space: nowrap !important; /* 개별 메뉴명 자체는 쪼개지지 않도록 방지 */
            display: inline-block;
            flex-shrink: 0;
        }
        .login-footer .policy-divider {
            margin: 0 5px !important; /* [보완] 구분선 간격 조절로 균형 유지 */
            font-size: 0.68rem !important;
            opacity: 0.6;
            flex-shrink: 0;
        }

        .login-footer .footer-item {
            display: flex !important;
            align-items: flex-start !important;
            margin-bottom: 3px;
        }
        .login-footer .footer-label {
            flex-shrink: 0;
            margin-right: 6px;
            white-space: nowrap;
        }
        .login-footer .footer-val {
            flex: 1;
            word-break: keep-all;
        }
        .login-footer .footer-divider {
            display: none !important;
        }
    }
</style>

<footer class="login-footer">
    <div class="policy-links">
        <!-- [무한 로딩 방지 패치: no-loader 클래스 추가로 전역 AppLoader 이벤트 실행 차단] -->
        <a href="terms.htm" class="no-loader" onclick="openPolicyModal(event, 'terms.htm', '서비스 이용약관');">이용약관</a>
        <span class="policy-divider">|</span>
        <a href="privacy.htm" class="policy-highlight no-loader" onclick="openPolicyModal(event, 'privacy.htm', '개인정보처리방침');">개인정보처리방침</a>
        <span class="policy-divider">|</span>
        <a href="location_terms.htm" class="no-loader" onclick="openPolicyModal(event, 'location_terms.htm', '위치기반서비스 이용약관');"><span class="txt-lbs-full">위치기반서비스이용약관</span><span class="txt-lbs-short">위치기반서비스약관</span></a>
        <span class="policy-divider">|</span>
        <a href="service_intro.htm" class="no-loader" onclick="openPolicyModal(event, 'service_intro.htm', '서비스 소개');">서비스 소개</a>
        <span class="policy-divider">|</span>
        <a href="customer_center.htm" class="no-loader" onclick="openPolicyModal(event, 'customer_center.htm', '고객센터');">고객센터</a>
    </div>

    <div>
        <p class="mb-1">
            <span class="footer-item">
                <strong class="footer-label">상호명 :</strong>
                <span class="footer-val">나오빌리</span>
            </span>
            <span class="footer-divider">|</span>
            <span class="footer-item">
                <strong class="footer-label">대표자 :</strong>
                <span class="footer-val">함민석</span>
            </span>
            <span class="footer-divider">|</span>
            <span class="footer-item">
                <strong class="footer-label">개인정보보호책임자 :</strong>
                <span class="footer-val">함민석</span>
            </span>
        </p>

        <p class="mb-1">
            <span class="footer-item">
                <strong class="footer-label">사업자등록번호 :</strong>
                <span class="footer-val">
                    601-16-09294 
                    <a href="https://www.ftc.go.kr/bizCommPop.do?wrkr_no=6011609294" target="_blank" style="font-weight: 600; margin-left: 2px;">[사업자정보확인]</a>
                </span>
            </span>
            <span class="footer-divider">|</span>
            <span class="footer-item">
                <strong class="footer-label">통신판매업신고 :</strong>
                <span class="footer-val">제2023-서울송파-1582호</span>
            </span>
        </p>

        <p class="mb-1">
            <span class="footer-item">
                <strong class="footer-label">사업장 소재지 :</strong>
                <span class="footer-val">서울특별시 송파구 백제고분로46길 22, 2층 602호(송파동, 송파(家)하우스)</span>
            </span>
        </p>

        <p class="mb-1">
            <span class="footer-item">
                <strong class="footer-label">고객센터 :</strong>
                <span class="footer-val"><a href="tel:0507-1319-3897">0507-1319-3897</a></span>
            </span>
            <span class="footer-divider">|</span>
            <span class="footer-item">
                <strong class="footer-label">이메일 :</strong>
                <span class="footer-val"><a href="mailto:naobilly123@naver.com">naobilly123@naver.com</a></span>
            </span>
            <span class="footer-divider">|</span>
            <span class="footer-item">
                <strong class="footer-label">호스팅제공자 :</strong>
                <span class="footer-val">네이버클라우드(주)</span>
            </span>
        </p>

        <p class="mt-2 mb-0 copyright-text">
            Copyright © 2026 나오빌리. All rights reserved.
        </p>
    </div>
</footer>

<div class="modal fade text-start" id="policyLayerModal" tabindex="-1" aria-labelledby="policyLayerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 960px; margin: 1.75rem auto;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden; background: #ffffff;">
            <div class="modal-header bg-dark text-white py-3 px-4 border-0">
                <h5 class="modal-title fw-bold fs-6 text-white" id="policyLayerModalLabel">
                    <i class="fa-solid fa-file-lines me-2 text-primary"></i>안내
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="policyModalFrame" src="about:blank" style="width: 100%; height: 75vh; border: none; display: block; background: #f8fafc;" title="정책 및 약관 안내"></iframe>
            </div>
            <div class="modal-footer bg-light py-2 px-3 border-top d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="openPolicyNewTab()">
                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>새 창으로 열기
                </button>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">
                    닫기
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    let currentModalTargetUrl = '';

    function openPolicyModal(e, url, title) {
        /* [무한 로딩 방지 패치 1: 이벤트 기본동작 및 상위 클릭 전파 즉각 중단] */
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        /* [무한 로딩 방지 패치 2: 현재 창 또는 부모 창에 대기 중인 AppLoader 강제 해제] */
        if (window.AppLoader && typeof window.AppLoader.hide === 'function') {
            window.AppLoader.hide();
        }
        if (window.parent && window.parent.AppLoader && typeof window.parent.AppLoader.hide === 'function') {
            window.parent.AppLoader.hide();
        }

        currentModalTargetUrl = url;
        
        const titleElem = document.getElementById('policyLayerModalLabel');
        const frameElem = document.getElementById('policyModalFrame');
        
        if (titleElem) {
            titleElem.innerHTML = '<i class="fa-solid fa-circle-info me-2 text-primary"></i>' + title;
        }
        if (frameElem) {
            /* [무한 로딩 방지 패치 3: iframe 콘텐츠 로딩 완료 시점에 로더 2차 강제 종료 보장] */
            frameElem.onload = function() {
                if (window.AppLoader && typeof window.AppLoader.hide === 'function') {
                    window.AppLoader.hide();
                }
            };
            frameElem.src = url;
        }

        const modalTarget = document.getElementById('policyLayerModal');
        if (modalTarget && typeof bootstrap !== 'undefined') {
            const bsModal = bootstrap.Modal.getOrCreateInstance(modalTarget);
            bsModal.show();
        } else {
            window.open(url, '_blank');
        }
    }

    function openPolicyNewTab() {
        if (currentModalTargetUrl) {
            window.open(currentModalTargetUrl, '_blank');
        }
    }
</script>