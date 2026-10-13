<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #e74c3c;
            color: white;
            text-align: center;
        }
        h1 {
            margin: 0 20px;
            padding: 20px 0;
        }

        h2 {
            color: #e74c3c;
        }

        .contact-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-top: 20px;
        }

        .contact-info {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .contact-info div {
            flex: 1;
            margin: 10px;
        }

        .contact-info h3 {
            color: #e74c3c;
        }

        .contact-info p {
            font-size: 16px;
        }

        .map-container {
            width: 100%;
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 20px;
        }

        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

    </style>
    <link rel="stylesheet" href="mediato-theme.css?v=6">
</head>
<body>
<?php
    include 'homepage/header.php';
?>
<header>
    <h1>Contact Us</h1>
</header>

<div class="contact-container">
    <h2>Get in Touch</h2>
    
    <div class="contact-info">
        <div>
            <h3>Our Office</h3>
            <p>Ho Chi Minh City Branch: Room 102, Building 20–208 Tran Cao Van, Da Kao Ward, District 1, Ho Chi Minh City</p>
            <p>Hanoi Branch: 4th Floor, Times Tower, 35 Le Van Luong, Thanh Xuan, Hanoi.</p>
        </div>
        <div>
            <h3>Email Us</h3>
            <p>Job Seeker Support Email: ntv@jobhive.vn</p>
            <p>Employer Support Email: ntd@jobhive.vn</p>
        </div>
        <div>
            <h3>Phone</h3>
            <p>(028) 7109 424</p>
            <p>(024) 7109 440</p>
        </div>
    </div>
    
    <div class="map-container">
        <h3>Find Us on Google Maps</h3>
        <!-- Google Maps embed -->
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.230535335151!2d106.69840871485061!3d10.76315796223071!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752c2a7b44b28b%3A0xc0c0c0c0c0c0c0c0!2zU2hvcCBNYXJrZXQgS3V5QW5oYQ!5e0!3m2!1sen!2s!4v1612506615721!5m2!1sen!2s" 
        allowfullscreen="" loading="lazy"></iframe>
    </div>
</div>
<?php
    include 'homepage/footer.php';
?>
</body>
</html>
