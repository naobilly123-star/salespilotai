<!-- [REFACTORED - 가로 스크롤 100% 제거 및 Full-Width 완벽 조치본] -->
<style>
    /* 1. 가로 스크롤 유발 요소를 방지하기 위한 html, body 안전 규격 */
    html, body {
        max-width: 100% !important;
        overflow-x: hidden !important;
    }

    /* 2. footer-section: 100vw 대신 100% 사용으로 스크롤바 두께 오차 완벽 해결 */
    .footer-section {
        background-color: #ffffff !important;
        border-top: 1px solid #e2e8f0 !important;
        color: #64748b;
        position: relative;
        z-index: 10;
        text-align: center;
        letter-spacing: -0.015em;
        width: 100% !important;
        box-sizing: border-box !important;
    }
    .footer-section a {
        text-decoration: none !important;
        color: #64748b !important;
        transition: color 0.15s ease;
    }
    .footer-section a:hover {
        color: #2563eb !important;
        text-decoration: underline !important;
    }

    .footer-inner-wrapper {
        width: 100%;
        max-width: 900px;
        margin: 0 auto;
        text-align: center;
    }
    .footer-section .policy-links {
        margin-bottom: 14px;
        white-space: nowrap;
    }

    .policy-divider {
        display: inline-block !important;
        margin: 0 8px !important;
        color: #cbd5e1 !important;
        font-weight: 300;
    }

    .footer-item {
        display: inline;
    }
    .footer-divider {
        display: inline;
        margin: 0 6px;
        color: #e2e8f0;
    }

    .txt-lbs-full { display: inline; }
    .txt-lbs-short { display: none; }

    @media (max-width: 767.98px) {
        .footer-inner-wrapper {
            text-align: left;
            padding: 0 20px !important;
            box-sizing: border-box !important;
        }

        .txt-lbs-full { display: none !important; }
        .txt-lbs-short { display: inline !important; }

        .footer-section .policy-links {
            display: flex !important;
            flex-wrap: wrap !important;
            row-gap: 6px !important;
            justify-content: center !important;
            align-items: center !important;
            white-space: normal !important;
            margin: 0 auto 16px auto !important;
            padding: 0 10px !important;
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box !important;
        }
        .footer-section .policy-links a {
            font-size: 0.75rem !important;
            letter-spacing: -0.025em !important;
            padding: 2px 4px !important;
            display: inline-block;
            flex-shrink: 0;
        }
        .policy-divider {
            margin: 0 5px !important;
            font-size: 0.68rem !important;
            color: #cbd5e1 !important;
            flex-shrink: 0;
        }

        .footer-item {
            display: flex !important;
            align-items: flex-start !important;
            margin-bottom: 4px;
            font-size: 0.8rem;
        }
        .footer-label {
            flex-shrink: 0;
            margin-right: 6px;
            white-space: nowrap;
            color: #475569;
            font-weight: 600;
        }
        .footer-val {
            flex: 1;
            word-break: keep-all;
        }
        .footer-divider {
            display: none !important;
        }
    }

    .policy-modal-dialog {
        max-width: 960px;
        margin: 1.75rem auto;
    }
    .policy-modal-iframe {
        width: 100%;
        height: 75vh;
        border: none;
        display: block;
        background-color: #f8fafc;
    }
    @media (max-width: 767.98px) {
        .policy-modal-dialog {
            max-width: 95%;
            margin: 0.75rem auto;
        }
        .policy-modal-iframe {
            height: 78vh;
        }
    }
</style>

<!-- Footer 시작 -->
<footer class="footer-section mt-5 py-4 text-secondary" id="globalPageFooter">
    <div class="container">
        <div class="footer-inner-wrapper">
            <div class="policy-links mb-3">
                <a href="/terms.htm" class="fw-semibold no-loader" onclick="openPolicyModal(event, '/terms.htm', '서비스 이용약관');">이용약관</a>
                <span class="policy-divider">|</span>
                <a href="/privacy.htm" class="fw-bold text-dark no-loader" onclick="openPolicyModal(event, '/privacy.htm', '개인정보처리방침');">개인정보처리방침</a>
                <span class="policy-divider">|</span>
                <a href="/location_terms.htm" class="fw-semibold no-loader" onclick="openPolicyModal(event, '/location_terms.htm', '위치기반서비스 이용약관');"><span class="txt-lbs-full">위치기반서비스이용약관</span><span class="txt-lbs-short">위치기반서비스약관</span></a>
                <span class="policy-divider">|</span>
                <a href="/service_intro.htm" class="fw-semibold no-loader" onclick="openPolicyModal(event, '/service_intro.htm', '서비스 소개');">서비스 소개</a>
                <span class="policy-divider">|</span>
                <a href="/customer_center.htm" class="fw-semibold no-loader" onclick="openPolicyModal(event, '/customer_center.htm', '고객센터');">고객센터</a>
            </div>

            <div class="small text-muted" style="line-height: 1.7;">
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
                            <a href="https://www.ftc.go.kr/bizCommPop.do?wrkr_no=6011609294" target="_blank" class="fw-semibold text-primary ms-1">[사업자정보확인]</a>
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

                <p class="mt-2 mb-0 text-muted" style="font-size: 0.78rem;">
                    Copyright © 2026 나오빌리. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- 상위 컨테이너에 갇혀 있을 경우 자동으로 body 직속 자식으로 이동시켜 100% 꽉 채우고 가로 스크롤을 방지하는 스크립트 -->
<script>
    (function() {
        const footer = document.getElementById('globalPageFooter');
        if (footer && footer.parentElement && footer.parentElement.tagName.toLowerCase() !== 'body') {
            document.body.appendChild(footer);
        }
    })();
</script>

<!-- 통합 레이어 팝업 모달 -->
<div class="modal fade text-start" id="policyLayerModal" tabindex="-1" aria-labelledby="policyLayerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered policy-modal-dialog">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden; background: #ffffff;">
            <div class="modal-header bg-dark text-white py-3 px-4 border-0" style="background: linear-gradient(135deg, #0f172a, #1e293b) !important;">
                <h5 class="modal-title fw-bold fs-6 text-white" id="policyLayerModalLabel">
                    <i class="fa-solid fa-file-lines me-2 text-primary"></i>안내
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="policyModalFrame" src="about:blank" class="policy-modal-iframe" title="정책 및 약관 상세 내용"></iframe>
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
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

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