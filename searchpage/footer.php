<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">


<style>
    .footer-new {
        background-color: #B73C3C;
        color: white;
        font-family: Arial, sans-serif;

    }

    .hotline-section {
        display: flex;
        justify-content: space-around;
        background-color: #fff;
        padding: 30px 10px;
        border: 1px solid #ccc;
        border-bottom: none;
    }

    .hotline-box {
        flex: 1;
        padding: 0 20px;
        text-align: center;
        border-right: 1px solid #ddd;
    }

    .hotline-box:last-child {
        border-right: none;
    }

    .hotline-box h3 {
        color: #D91616;
        font-weight: bold;
        margin-bottom: 20px;
    }

    .hotline-item {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
    }

    .hotline-item i {
        font-size: 24px;
        color: #333;
        margin-right: 10px;
    }

    .hotline-item div {
        text-align: left;
        font-size: 14px;
        color: #D91616;
    }

    .hotline-btn {
        background-color: #D91616;
        color: white;
        border: none;
        padding: 8px 14px;
        border-radius: 4px;
        margin-top: 10px;
        cursor: pointer;
    }

    .hotline-btn:hover {
        background-color: #880000;
    }



    .footer-container {
        display: flex;
        justify-content: space-between;
        max-width: 100%;
        flex-wrap: wrap;
        margin: auto;
    }

    .footer-column {
        flex: 1 1 30%;
        min-width: 400px;
        margin: 10px 0px 50px 40px;

    }

    .footer-column h4 {
        margin-bottom: 30px;
        font-weight: bold;
    }

    .footer-column p,
    .footer-column ul {
        font-size: 14px;
        line-height: 1.6;
    }

    .footer-column ul {
        list-style: none;
        padding: 0;
    }

    .footer-column ul li {
        margin-bottom: 8px;
    }

    .social-icons {
        margin-bottom: 12px;
    }

    .social-icons i {
        font-size: 20px;
        margin-right: 10px;
        border-radius: 10px;
        color: white;
    }


    .social-icons .facebook {
        background-color: #1877f2;
        padding: 10px;
    }

    .social-icons .tiktok {
        background-color: #000;
        padding: 10px;
    }

    .social-icons .telegram {
        background-color: #24A1DE;
        padding: 10px;
    }

    .social-icons .instagram {
        background: radial-gradient(circle at 30% 107%,
                #fdf497 0%,
                #fdf497 5%,
                #fd5949 45%,
                #d6249f 60%,
                #285AEB 90%);
        display: inline-block;
        padding: 10px;
    }

    .qr-and-apps {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .qr-and-apps img {
        height: 100px;
    }

    .app-links img {
        width: 130px;
        display: block;
        height: 60px;
    }

    @media (max-width: 768px) {
        .hotline-section {
            flex-direction: column;
        }

        .hotline-box {
            border-right: none;
            border-bottom: 1px solid #ddd;
            padding: 20px 0;
        }

        .hotline-box:last-child {
            border-bottom: none;
        }

        .footer-container {
            flex-direction: column;
            padding: 20px;
        }

        .footer-column {
            min-width: 100%;
            margin: 10px 0 20px 0;
        }

        .qr-and-apps {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<footer class="footer-new">

    <!-- Hotline -->

    <!-- Thông tin & liên kết -->
    <div class="footer-container">

        <!-- Cột 1: About -->
        <div class="footer-column">
            <h4><?= $lang['footer_about_us']; ?></h4>
            <p>
                <strong><?= $lang['footer_company_full']; ?></strong><br>
                <?= $lang['footer_phone']; ?><br>
                <?= $lang['footer_email_job_seeker']; ?><br>
                <?= $lang['footer_email_employer']; ?>
            </p>
        </div>

        <!-- Cột 2: Information -->

        <!-- Cột 3: Kết nối & App -->

    </div>
</footer>
</html>