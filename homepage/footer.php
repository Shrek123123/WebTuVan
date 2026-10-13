<style>
    .mediator-site-footer {
        border-top: 1px solid #dce1da;
        background: #eaece5;
        color: #1d302f;
        font-family: Arial, Helvetica, sans-serif;
    }
    .mediator-footer-inner {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(180px, .65fr);
        gap: 42px;
        width: min(1160px, calc(100% - 44px));
        margin: 0 auto;
        padding: 38px 0 26px;
    }
    .mediator-footer-brand {
        margin: 0 0 9px;
        font: 500 1.35rem Georgia, 'Times New Roman', serif;
    }
    .mediator-footer-copy {
        max-width: 530px;
        margin: 0;
        color: #5e6d69;
        font-size: .91rem;
        line-height: 1.7;
    }
    .mediator-footer-heading {
        margin: 2px 0 12px;
        color: #5e6d69;
        font-size: .7rem;
        font-weight: 800;
        letter-spacing: .13em;
        text-transform: uppercase;
    }
    .mediator-footer-links {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 9px;
    }
    .mediator-footer-links a {
        color: #1d302f;
        font-size: .9rem;
        text-decoration-color: transparent;
        text-underline-offset: 4px;
    }
    .mediator-footer-links a:hover, .mediator-footer-links a:focus-visible {
        color: #2f7068;
        text-decoration-color: currentColor;
    }
    .mediator-footer-bottom {
        width: min(1160px, calc(100% - 44px));
        margin: 0 auto;
        padding: 14px 0 18px;
        border-top: 1px solid #d2d9d1;
        color: #68746f;
        font-size: .78rem;
    }
    @media (max-width: 560px) {
        .mediator-footer-inner { grid-template-columns: 1fr; gap: 24px; width: calc(100% - 32px); padding-top: 28px; }
        .mediator-footer-bottom { width: calc(100% - 32px); }
    }
</style>
<footer class="mediator-site-footer">
    <div class="mediator-footer-inner">
        <section>
            <h2 class="mediator-footer-brand">Mediato</h2>
            <p class="mediator-footer-copy">Mediato giúp bạn tham khảo hồ sơ hòa giải viên và gửi yêu cầu đặt lịch. Hình thức và địa điểm tư vấn cần được xác nhận với hòa giải viên trước buổi hẹn.</p>
        </section>
        <nav aria-label="Liên kết cuối trang">
            <h2 class="mediator-footer-heading">Khám phá</h2>
            <div class="mediator-footer-links">
                <a href="index.php">Trang chủ</a>
                <a href="searchpage.php">Tìm hòa giải viên</a>
                <a href="feedback_jobseeker.php">Gửi góp ý</a>
            </div>
        </nav>
    </div>
    <div class="mediator-footer-bottom">Thông tin hồ sơ chỉ nhằm mục đích tham khảo, không thay thế tư vấn pháp lý.</div>
</footer>
