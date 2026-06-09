<?php
ini_set('session.cache_limiter', 'public');
session_cache_limiter(false);
session_start();
include("config.php");

// Check database connection
if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch SEO settings for the homepage
$seoQuery = $con->prepare("SELECT seo_title, seo_description, seo_keywords, seo_author FROM seo_settings WHERE page = 'homepage'");
if ($seoQuery) {
    $seoQuery->execute();
    $seoResult = $seoQuery->get_result()->fetch_assoc();
}

$seoTitle = $seoResult['seo_title'] ?? 'Default Homepage Title';
$seoDescription = $seoResult['seo_description'] ?? 'Default Homepage Description';
$seoKeywords = $seoResult['seo_keywords'] ?? 'property, real estate, homes';
$seoAuthor = $seoResult['seo_author'] ?? 'Your Company Name';

// Fetch the banner image path, currency symbol, and other site info
$query = "SELECT banner_image_path, currency, banner_writeup, favicon_path, logo_path FROM site_info WHERE id = 1";
$result = mysqli_query($con, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $bannerImagePath = $row['banner_image_path'];
    $currency = $row['currency'];
    $bannerWriteup = $row['banner_writeup'];
    $faviconPath = $row['favicon_path'];
    $logoPath = $row['logo_path'];
} else {
    echo "Error fetching site info: " . mysqli_error($con);
    exit;
}

// Fetch footer content from the database
$query = "SELECT * FROM footer_content WHERE id = 1";
$result = mysqli_query($con, $query);
$footerContent = mysqli_fetch_assoc($result);

$companyName = $footerContent['company_name'];
$welcomeMessage = $footerContent['welcome_message'];
$address = $footerContent['address'];
$phoneNumber = $footerContent['phone_number'];
$email = $footerContent['email'];
$facebookUrl = $footerContent['facebook_url'];
$instagramUrl = $footerContent['instagram_url'];
$linkedinUrl = $footerContent['linkedin_url'];
$twitterUrl = $footerContent['twitter_url'];
$logoPath = $footerContent['logo_path'];

// Fetch contact information
$contactQuery = "SELECT phone_number, email FROM contact_info WHERE id = 1";
$contactResult = mysqli_query($con, $contactQuery);
if ($contactResult) {
    $contactRow = mysqli_fetch_assoc($contactResult);
    $phoneNumber = $contactRow['phone_number'];
    $email = $contactRow['email'];
}

// Fetch menu items
$menuItems = [];
$menuQuery = "SELECT name, url FROM menu_items WHERE visible = 1 ORDER BY id";
$menuResult = mysqli_query($con, $menuQuery);
if ($menuResult) {
    while ($menuRow = mysqli_fetch_assoc($menuResult)) {
        $menuItems[] = $menuRow;
    }
}

// Fetch states and their property counts
$stateQuery = "
    SELECT s.sname AS state, s.image_path, COUNT(p.pid) AS total_properties
    FROM state s
    LEFT JOIN property p ON s.sname = p.state
    GROUP BY s.sname, s.image_path";
$stateResult = mysqli_query($con, $stateQuery);
if (!$stateResult) {
    echo "Error fetching states: " . mysqli_error($con);
    exit;
}

$propertyQuery = mysqli_query($con, "
    SELECT * FROM property 
    WHERE is_featured = 1 
    ORDER BY date DESC 
    LIMIT 6
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-TVTHQKC4LH"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-TVTHQKC4LH');
</script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="<?php echo htmlspecialchars($seoDescription); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($seoKeywords); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($seoAuthor); ?>">
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo $faviconPath; ?>">
    <link rel="apple-touch-icon-precomposed" href="<?php echo $faviconPath; ?>">
    
    <!-- Title -->
    <title><?php echo htmlspecialchars($seoTitle); ?></title>
    
    <!-- CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/owl.carousel@2.3.4/dist/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/magnific-popup@1.1.0/dist/magnific-popup.css">
    <!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --primary: #2A5EE8;
            --primary-dark: #1A4BCF;
            --secondary: #0000FF;
            --dark: #1A1D26;
            --light: #F8F9FA;
            --gray: #6C757D;
            --light-gray: #E9ECEF;
            --transition: all 0.3s ease;
            --white: #FFFFFF;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            color: var(--dark);
            line-height: 1.7;
            overflow-x: hidden;
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
        }
        
        .btn {
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(42, 94, 232, 0.2);
        }
        
        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
            color: var(--white);
        }
        
        .section-title {
            margin-bottom: 80px;
            text-align: center;
        }
        
        .section-title h2 {
            font-size: 36px;
            position: relative;
            display: inline-block;
            padding-bottom: 15px;
        }
        
        .section-title h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--primary);
        }
        
        /* Header */
        .navbar {
            padding: 30px 0;
            transition: var(--transition);
            background: transparent !important;
        }
        
        .navbar.scrolled {
            padding: 10px 0;
            background: var(--white) !important;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand img {
            height: 60px;
        }
        
        .navbar-nav {
            flex-grow: 1;
            justify-content: center;
        }
        
        .nav-link {
            font-weight: 500;
            padding: 8px 15px !important;
            color: var(--white) !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        }
        
        .navbar.scrolled .nav-link {
            color: var(--dark) !important;
            text-shadow: none;
        }
        
        .nav-link:hover {
            color: var(--secondary) !important;
        }
        
        .navbar-toggler {
            border: none;
            padding: 10px;
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 0.9)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        .navbar.scrolled .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(0, 0, 0, 0.9)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('<?php echo $bannerImagePath; ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 90vh;
            display: flex;
            align-items: center;
            color: var(--white);
            position: relative;
        }
        
        .hero-content h1 {
            font-size: 56px;
            line-height: 1.2;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .hero-content p {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .search-box {
            background: var(--white);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .search-box .form-control {
            height: 55px;
            border-radius: 8px;
            border: 1px solid var(--light-gray);
            padding-left: 20px;
        }
        
        .search-box .form-control:focus {
            box-shadow: none;
            border-color: var(--primary);
        }
        
        .search-box select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px 12px;
        }
        
        /* Property Card */
        .property-card {
            border-radius: 12px;
            overflow: hidden;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
            margin-bottom: 30px;
        }
        
        .property-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .property-img {
            height: 220px;
            overflow: hidden;
            position: relative;
        }
        
        .property-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }
        
        .property-card:hover .property-img img {
            transform: scale(1.05);
        }
        
        .property-status {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--primary);
            color: var(--white);
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .property-content {
            padding: 20px;
        }
        
        .property-price {
            color: var(--primary);
            font-weight: 700;
            font-size: 20px;
        }
        
        .property-title {
            font-size: 18px;
            margin: 10px 0;
        }
        
        .property-title a {
            color: var(--dark);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .property-title a:hover {
            color: var(--primary);
        }
        
        .property-location {
            color: var(--gray);
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .property-features {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid var(--light-gray);
            padding-top: 15px;
            margin-top: 15px;
        }
        
        .property-features span {
            font-size: 14px;
        }
        
        .property-features i {
            color: var(--primary);
            margin-right: 5px;
        }
        
        /* About Section */
        .about-section {
            padding: 120px 0;
            background: var(--light);
        }
        
        .about-img {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .about-img img {
            width: 100%;
            height: auto;
        }
        
        .about-content h2 {
            margin-bottom: 20px;
        }
        
        .about-content .btn {
            margin-top: 20px;
        }
        
        .read-more-btn {
            background-color: var(--secondary);
            color: var(--white);
            padding: 14px 32px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            display: inline-block;
            text-decoration: none;
            transition: var(--transition);
        }
        
        .read-more-btn:hover {
            background-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(42, 94, 232, 0.2);
        }
        
        /* Agents */
        .agent-card {
            text-align: center;
            margin-bottom: 30px;
            transition: var(--transition);
        }
        
        .agent-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 20px;
            border: 5px solid var(--white);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .agent-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .agent-info h4 {
            margin-bottom: 5px;
        }
        
        .agent-info p {
            color: var(--gray);
            font-size: 14px;
        }
        
        .agent-social {
            margin-top: 15px;
        }
        
        .agent-social a {
            display: inline-block;
            width: 35px;
            height: 35px;
            line-height: 35px;
            text-align: center;
            background: var(--light-gray);
            color: var(--dark);
            border-radius: 50%;
            margin: 0 3px;
            transition: var(--transition);
        }
        
        .agent-social a:hover {
            background: var(--primary);
            color: var(--white);
        }
        
        /* Neighborhoods */
        .neighborhood-card {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .neighborhood-img {
            height: 250px;
            overflow: hidden;
        }
        
        .neighborhood-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }
        
        .neighborhood-card:hover .neighborhood-img img {
            transform: scale(1.05);
        }
        
        .neighborhood-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            color: var(--white);
            padding: 20px;
        }
        
        .neighborhood-info h4 {
            margin-bottom: 5px;
        }
        
        .neighborhood-info p {
            margin: 0;
            opacity: 0.9;
        }
        
        /* Testimonials */
        .testimonial-section {
            padding: 120px 0;
            background: var(--light);
        }
        
        .testimonial-card {
            background: var(--white);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin: 15px;
        }
        
        .testimonial-text {
            font-style: italic;
            margin-bottom: 20px;
            position: relative;
        }
        
        .testimonial-text:before {
            content: '"';
            font-size: 60px;
            color: var(--primary);
            opacity: 0.2;
            position: absolute;
            top: -20px;
            left: -10px;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
        }
        
        .testimonial-author img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
        }
        
        .author-info h5 {
            margin-bottom: 0;
        }
        
        .author-info p {
            font-size: 14px;
            color: var(--gray);
            margin: 0;
        }
        
        /* Newsletter */
        .newsletter-section {
            padding: 120px 0;
            background: var(--primary);
            color: var(--white);
        }
        
        .newsletter-form {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .newsletter-form .form-control {
            height: 55px;
            border-radius: 8px;
            border: none;
            padding-left: 20px;
        }
        
        .newsletter-form .btn {
            background: var(--white);
            color: var(--primary);
            font-weight: 600;
        }
        
        .newsletter-form .btn:hover {
            background: var(--light-gray);
        }
        
        /* Footer */
        .footer {
            background: var(--dark);
            color: var(--white);
            padding: 120px 0 30px;
        }
        
        .footer-logo img {
            height: 60px;
            margin-bottom: 20px;
        }
        
        .footer-about p {
            opacity: 0.8;
            margin-bottom: 20px;
        }
        
        .footer-social a {
            display: inline-block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            text-align: center;
            background: rgba(255,255,255,0.1);
            color: var(--white);
            border-radius: 50%;
            margin-right: 10px;
            transition: var(--transition);
        }
        
        .footer-social a:hover {
            background: var(--primary);
            transform: translateY(-5px);
        }
        
        .footer-title {
            font-size: 20px;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 2px;
            background: var(--primary);
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: rgba(255,255,255,0.7);
            transition: var(--transition);
            text-decoration: none;
        }
        
        .footer-links a:hover {
            color: var(--white);
            padding-left: 5px;
        }
        
        .footer-contact li {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }
        
        .footer-contact i {
            margin-right: 10px;
            color: var(--primary);
            margin-top: 5px;
        }
        
        .copyright {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
            margin-top: 40px;
            text-align: center;
            opacity: 0.7;
            font-size: 14px;
        }
        
        /* Back to top */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            line-height: 50px;
            text-align: center;
            background: var(--primary);
            color: var(--white);
            border-radius: 50%;
            font-size: 20px;
            z-index: 99;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .back-to-top.active {
            opacity: 1;
            visibility: visible;
        }
        
        .back-to-top:hover {
            background: var(--primary-dark);
            color: var(--white);
            transform: translateY(-5px);
        }
        
        /* Responsive */
        @media (max-width: 991px) {
            .hero-content {
                padding-top: 80px;
            }
            
            .hero-content h1 {
                font-size: 42px;
            }
            
            .section-title h2 {
                font-size: 30px;
            }
            
            .navbar-collapse {
                background: var(--white);
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
                margin-top: 10px;
            }
            
            .navbar-nav {
                justify-content: flex-start;
            }
            
            .nav-link {
                color: var(--dark) !important;
                text-shadow: none;
            }
        }
        
        @media (max-width: 767px) {
            .hero-content {
                padding-top: 100px;
            }
            
            .hero-content h1 {
                font-size: 36px;
            }
            
            .search-box {
                margin-top: 30px;
            }
            
            .section-title h2 {
                font-size: 28px;
            }
        }
        
        /* Fixed navbar toggler styles */
        .navbar-toggler {
            border-color: white !important;
        }

        .navbar.scrolled .navbar-toggler {
            border-color: var(--dark) !important;
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='white' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
        }
        
        .navbar.scrolled .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(0, 0, 0, 0.9)' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
        }
        
        
         .chat-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            height: 700px;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .chat-header h2 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .chat-header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .message {
            display: flex;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .bot-message {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 12px;
            max-width: 80%;
            line-height: 1.6;
            color: #333;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .user-message {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 18px;
            border-radius: 12px;
            max-width: 70%;
            margin-left: auto;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .message-time {
            font-size: 11px;
            color: #999;
            margin-top: 5px;
            display: block;
        }

        .user-message .message-time {
            color: rgba(255, 255, 255, 0.7);
        }

        .chat-link {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            text-decoration: none !important;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            margin-top: 12px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .chat-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .typing-indicator {
            display: none;
            padding: 10px 15px;
            background: #f0f0f0;
            border-radius: 20px;
            width: fit-content;
        }

        .typing-indicator.active {
            display: block;
        }

        .typing-indicator span {
            height: 8px;
            width: 8px;
            background: #999;
            border-radius: 50%;
            display: inline-block;
            margin: 0 2px;
            animation: typing 1.4s infinite;
        }

        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.7;
            }
            30% {
                transform: translateY(-10px);
                opacity: 1;
            }
        }

        .quick-suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 15px;
            border-top: 1px solid #e0e0e0;
            background: #fafbfc;
        }

        .quick-suggestion {
            padding: 8px 16px;
            background: white;
            border: 1px solid #667eea;
            color: #667eea;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .quick-suggestion:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .chat-input-container {
            display: flex;
            padding: 15px;
            background: white;
            border-top: 1px solid #e0e0e0;
            gap: 10px;
        }

        .chat-input {
            flex: 1;
            padding: 12px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .chat-input:focus {
            border-color: #667eea;
        }

        .chat-send-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .chat-send-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            .chat-container {
                margin: 20px;
                height: calc(100vh - 40px);
                border-radius: 15px;
            }

            .bot-message, .user-message {
                max-width: 90%;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <nav class="navbar navbar-expand-lg navbar-light fixed-top">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <img src="<?php echo $logoPath; ?>" alt="<?php echo $companyName; ?>">
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <?php foreach ($menuItems as $item): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo htmlspecialchars($item['url']); ?>"><?php echo htmlspecialchars($item['name']); ?></a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero-section mb-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                   <div class="hero-content">
    <h1><?php echo htmlspecialchars($bannerWriteup); ?></h1>
    <a href="property.php" class="btn btn-primary mr-2 mb-2">Browse Properties</a>
    <a href="contact.php" class="btn btn-outline-primary mb-2">Contact Us</a>
</div>

                </div>
                <div class="col-lg-6">
                    <div class="search-box">
                        <h4 class="mb-4">Find Your Dream Home</h4>
                        <form action="property.php" method="get">
                            <div class="form-group">
                                <input type="text" class="form-control" name="search" placeholder="State">
                            </div>
                            <div class="form-group">
                                <select class="form-control" name="type">
                                    <option value="">Property Type</option>
                                    <option value="apartment">Apartment</option>
                                    <option value="flat">Flat</option>
                                    <option value="bunglow">Bungalow</option>
                                    <option value="duplex">Duplex</option>
                                    <option value="villa">Villa</option>
                                    <option value="office">Office</option>
                                    <option value="land">Land</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <select class="form-control" name="stype">
                                    <option value="">Property Status</option>
                                    <option value="rent">For Rent</option>
                                    <option value="sale">For Sale</option>
                                    <option value="shortlet">Shortlet</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Search Properties</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Properties -->
    <section class="py-120 mb-5">
        <div class="container">
            <div class="section-title">
                <h2>Featured Properties</h2>
                <p>Explore our handpicked selection of premium properties</p>
            </div>
            
            <div class="row">
                <?php
                if (!$con) {
                    echo '<div class="col-12 text-center py-5"><p>Database connection failed: ' . htmlspecialchars(mysqli_connect_error()) . '</p></div>';
                } else {
                    $sql = "SELECT property.*, user.uname, user.utype, user.uimage 
                            FROM property 
                            JOIN user ON property.uid = user.uid 
                            WHERE property.status <> 'sold out' AND property.is_featured = 1 
                            ORDER BY property.date DESC 
                            LIMIT 6";
                    $query = mysqli_query($con, $sql);

                    if (!$query || mysqli_num_rows($query) == 0) {
                        echo '<div class="col-12 text-center py-5"><p>No featured properties available</p></div>';
                    } else {
                        while ($row = mysqli_fetch_assoc($query)) {
                            $priceFormatted = $currency . number_format($row['price']);
                            $propertyLink = 'propertydetail.php?pid=' . urlencode($row['pid']);
                ?>
                <div class="col-lg-4 col-md-6">
                    <div class="property-card">
                        <div class="property-img">
                            <img src="admin/property/<?= htmlspecialchars($row['pimage']) ?>" alt="<?= htmlspecialchars($row['title']) ?>">
                            <span class="property-status"><?= ucfirst(htmlspecialchars($row['stype'])) ?></span>
                        </div>
                        <div class="property-content">
                            <div class="property-price"><?= $priceFormatted ?></div>
                            <h3 class="property-title"><a href="<?= $propertyLink ?>"><?= htmlspecialchars($row['title']) ?></a></h3>
                            <div class="property-location">
                                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($row['location']) ?>
                            </div>
                            <div class="property-features">
                                <span><i class="fas fa-bed"></i> <?= htmlspecialchars($row['bedroom']) ?> Beds</span>
                                <span><i class="fas fa-bath"></i> <?= htmlspecialchars($row['bathroom']) ?> Baths</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                        }
                    }
                }
                ?>
            </div>
            
            <div class="text-center mt-5">
                <a href="property.php" class="btn btn-outline-primary">View All Properties</a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section mb-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="about-img animate__animated animate__fadeInLeft">
                        <?php 
                        $query = mysqli_query($con, "SELECT * FROM about");
                        while ($row = mysqli_fetch_array($query)) {
                            echo '<img src="admin/upload/' . $row['3'] . '" alt="About ' . $companyName . '">';
                        }
                        ?>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about-content animate__animated animate__fadeInRight">
                        <?php 
                        $query = mysqli_query($con, "SELECT * FROM about");
                        while ($row = mysqli_fetch_array($query)) {
                            echo '<h2>' . $row['1'] . '</h2>';
                            $aboutText = strip_tags($row['2']);
                            $words = explode(' ', $aboutText);
                            
                            if (count($words) > 50) {
                                $shortText = implode(' ', array_slice($words, 0, 50)) . '...';
                                echo '<p>' . nl2br(htmlspecialchars($shortText)) . '</p>';
                                echo '<a href="about.php" class="read-more-btn">Read More</a>';
                            } else {
                                echo '<p>' . nl2br(htmlspecialchars($aboutText)) . '</p>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Properties Section -->
    <section class="py-120 mb-5">
        <div class="container">
            <div class="section-title">
                <h2>Latest Properties</h2>
                <p>Browse our recently added properties</p>
            </div>
            
            <?php
            $valid_filters = ['all', 'for-sale', 'for-rent', 'for-shortlet'];
            $filter = isset($_GET['filter']) && in_array($_GET['filter'], $valid_filters) ? $_GET['filter'] : 'all';
            $where_clause = "WHERE property.status <> 'sold out'";
            
            if ($filter == 'for-sale') {
                $where_clause .= " AND property.stype = 'sale'";
            } elseif ($filter == 'for-rent') {
                $where_clause .= " AND property.stype = 'rent'";
            } elseif ($filter == 'for-shortlet') {
                $where_clause .= " AND property.stype = 'shortlet'";
            }

            $results_per_page = 6;
            $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
            if ($current_page < 1) $current_page = 1;
            
            $offset = ($current_page - 1) * $results_per_page;
            
            $total_query = mysqli_query($con, "SELECT COUNT(*) as total FROM property $where_clause");
            if ($total_query) {
                $total_row = mysqli_fetch_assoc($total_query);
                $total_properties = $total_row['total'];
                $total_pages = ceil($total_properties / $results_per_page);
                if ($current_page > $total_pages && $total_pages > 0) {
                    $current_page = $total_pages;
                    $offset = ($current_page - 1) * $results_per_page;
                }
            } else {
                $total_properties = 0;
                $total_pages = 1;
            }
            
            $query = mysqli_query($con, "SELECT property.*, user.uname, user.utype, user.uimage 
                                       FROM property 
                                       JOIN user ON property.uid = user.uid 
                                       $where_clause
                                       ORDER BY property.date DESC 
                                       LIMIT $offset, $results_per_page");
            ?>
            
            <div class="properties-filter mb-5">
                <div class="filter-buttons">
                    <button class="filter-btn <?= $filter == 'all' ? 'active' : '' ?>" data-filter="all">All Properties</button>
                    <button class="filter-btn <?= $filter == 'for-sale' ? 'active' : '' ?>" data-filter="for-sale">For Sale</button>
                    <button class="filter-btn <?= $filter == 'for-rent' ? 'active' : '' ?>" data-filter="for-rent">For Rent</button>
                    <button class="filter-btn <?= $filter == 'for-shortlet' ? 'active' : '' ?>" data-filter="for-shortlet">Shortlets</button>
                </div>
            </div>
            
            <div class="row">
                <?php 
                if (!$query) {
                    echo '<div class="col-12 text-center py-5"><p>Error retrieving properties: ' . htmlspecialchars(mysqli_error($con)) . '</p></div>';
                } elseif (mysqli_num_rows($query) == 0) {
                    echo '<div class="col-12 text-center py-5"><p>No properties found for this ' . htmlspecialchars($filter == 'all' ? 'category' : $filter) . '.</p></div>';
                } else {
                    while ($row = mysqli_fetch_assoc($query)) {
                        $priceFormatted = $currency . number_format($row['price']);
                        $propertyLink = 'propertydetail.php?pid=' . urlencode($row['pid']);
                ?>
                <div class="col-lg-4 col-md-6">
                    <div class="property-card">
                        <div class="property-img">
                            <img src="admin/property/<?= htmlspecialchars($row['pimage']) ?>" alt="<?= htmlspecialchars($row['title']) ?>">
                            <span class="property-status"><?= ucfirst(htmlspecialchars($row['stype'])) ?></span>
                        </div>
                        <div class="property-content">
                            <div class="property-price"><?= $priceFormatted ?></div>
                            <h3 class="property-title"><a href="<?= $propertyLink ?>"><?= htmlspecialchars($row['title']) ?></a></h3>
                            <div class="property-location">
                                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($row['location']) ?>
                            </div>
                            <div class="property-features">
                                <span><i class="fas fa-bed"></i> <?= htmlspecialchars($row['bedroom']) ?> Beds</span>
                                <span><i class="fas fa-bath"></i> <?= htmlspecialchars($row['bathroom']) ?> Baths</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php 
                    }
                }
                ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <div class="pagination-wrapper mt-5">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($current_page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= ($current_page - 1) ?>&filter=<?= htmlspecialchars($filter) ?>" aria-label="Previous">
                                <span aria-hidden="true">«</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php 
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        if ($start_page > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1&filter='.htmlspecialchars($filter).'">1</a></li>';
                            if ($start_page > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $active = ($i == $current_page) ? 'active' : '';
                            echo '<li class="page-item ' . $active . '"><a class="page-link" href="?page=' . $i . '&filter='.htmlspecialchars($filter).'">' . $i . '</a></li>';
                        }
                        
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&filter='.htmlspecialchars($filter).'">' . $total_pages . '</a></li>';
                        }
                        ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= ($current_page + 1) ?>&filter=<?= htmlspecialchars($filter) ?>" aria-label="Next">
                                <span aria-hidden="true">»</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Agents Section -->
    <section class="py-120 bg-light mb-5">
        <div class="container">
            <div class="section-title">
                <h2>Meet Our Agents</h2>
                <p>Our professional agents are ready to help you find your dream home</p>
            </div>
            
            <div class="row">
                <?php
                $query = mysqli_query($con, "SELECT * FROM board_members ORDER BY created_at DESC");
                if(mysqli_num_rows($query) > 0) {
                    while ($row = mysqli_fetch_assoc($query)) {
                ?>
                <div class="col-lg-3 col-md-6">
                    <div class="agent-card">
                        <div class="agent-img">
                            <img src="admin/<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                        </div>
                        <div class="agent-info">
                            <h4><?= htmlspecialchars($row['name']) ?></h4>
                            <p><?= htmlspecialchars($row['position']) ?></p>
                        </div>
                      
                    </div>
                </div>
                <?php
                    }
                } else {
                    echo '<div class="col-12 text-center py-5"><p>No agents found</p></div>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Neighborhoods Section -->
    <section class="py-120 mb-5">
        <div class="container">
            <div class="section-title">
                <h2>Explore Neighborhoods</h2>
                <p>Discover properties in these popular locations</p>
            </div>
            
            <div class="row">
                <?php
                while ($stateRow = mysqli_fetch_assoc($stateResult)) {
                    $state = $stateRow['state'];
                    $total_properties = $stateRow['total_properties'];
                    $imagePath = $stateRow['image_path'];
                ?>
                <div class="col-lg-4 col-md-6">
                    <div class="neighborhood-card">
                        <div class="neighborhood-img">
                            <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($state) ?>">
                        </div>
                        <div class="neighborhood-info">
                            <h4><?= htmlspecialchars($state) ?></h4>
                            <p><?= htmlspecialchars($total_properties) ?> Properties</p>
                            <a href="stateproperty.php?state=<?= urlencode($state) ?>" class="btn btn-sm btn-primary mt-2">View Properties</a>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="newsletter-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="text-white mb-4">Stay Updated With Our Newsletter</h2>
                    <p class="text-white mb-5">Subscribe to get the latest property listings and real estate news delivered to your inbox.</p>
                    
                    <form class="newsletter-form" id="subscribe-form" action="subscribe.php" method="post">
                        <div class="input-group">
                            <input type="email" name="email" class="form-control" placeholder="Your email address" required>
                            <div class="input-group-append">
                                <button class="btn" type="submit">Subscribe</button>
                            </div>
                        </div>
                        <div id="subscribe-msg" class="mt-3 text-white"></div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="footer-about">
                        <div class="footer-logo">
                            <img src="<?php echo $logoPath; ?>" alt="<?php echo $companyName; ?>">
                        </div>
                        <p><?php echo $welcomeMessage; ?></p>
                        <div class="footer-social">
                            <a href="<?php echo $facebookUrl; ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
                            <a href="<?php echo $twitterUrl; ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                            <a href="<?php echo $linkedinUrl; ?>" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                            <a href="<?php echo $instagramUrl; ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <div class="footer-links">
                        <h3 class="footer-title">Quick Links</h3>
                        <ul>
                            <li><a href="index.php">Home</a></li>
                            <li><a href="about.php">About Us</a></li>
                            <li><a href="property.php">Properties</a></li>
                            <li><a href="about.php">Agents</a></li>
                            <li><a href="contact.php">Contact</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="footer-links">
                        <h3 class="footer-title">Property Types</h3>
                        <ul>
                            <li><a href="property.php?type=apartment">Apartments</a></li>
                            <li><a href="property.php?type=villa">Villas</a></li>
                            <li><a href="property.php?type=office">Office Spaces</a></li>
                            <li><a href="property.php?type=land">Land</a></li>
                            <li><a href="property.php?type=commercial">Commercial</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="footer-contact">
                        <h3 class="footer-title">Contact Us</h3>
                        <ul>
                            <li>
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo $address; ?></span>
                            </li>
                            <li>
                                <i class="fas fa-phone-alt"></i>
                                <a href="tel:<?php echo str_replace(' ', '', $phoneNumber); ?>"><?php echo $phoneNumber; ?></a>
                            </li>
                            <li>
                                <i class="fas fa-envelope"></i>
                                <a href="mailto:<?php echo $email; ?>"><?php echo $email; ?></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="copyright">
                © <?php echo date("Y"); ?> <?php echo $companyName; ?>. All Rights Reserved.
            </div>
        </div>
    </footer>

    <!-- Back to Top -->
    <a href="#" class="back-to-top"><i class="fas fa-arrow-up"></i></a>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/owl.carousel@2.3.4/dist/owl.carousel.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/wowjs@1.1.3/dist/wow.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/magnific-popup@1.1.0/dist/jquery.magnific-popup.min.js"></script>
    
    <script>
        // Navbar scroll effect
        $(window).scroll(function() {
            if ($(this).scrollTop() > 100) {
                $('.navbar').addClass('scrolled');
            } else {
                $('.navbar').removeClass('scrolled');
            }
        });
        
        // Back to top button
        $(window).scroll(function() {
            if ($(this).scrollTop() > 300) {
                $('.back-to-top').addClass('active');
            } else {
                $('.back-to-top').removeClass('active');
            }
        });
        
        $('.back-to-top').click(function(e) {
            e.preventDefault();
            $('html, body').animate({scrollTop: 0}, '300');
        });
        
        // Testimonial slider
        $('.testimonial-slider').owlCarousel({
            loop: true,
            margin: 30,
            nav: true,
            dots: false,
            autoplay: true,
            responsive: {
                0: {
                    items: 1
                },
                768: {
                    items: 2
                },
                1200: {
                    items: 3
                }
            }
        });
        
        // Filter buttons
        $('.filter-btn').click(function() {
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');
            const filter = $(this).data('filter');
            window.location.href = `?filter=${filter}&page=1`;
        });
        
        // Initialize WOW.js for animations
        new WOW().init();
        
        // Ensure mobile menu toggle works
        $('.navbar-toggler').click(function() {
            $('#navbarNav').collapse('toggle');
        });
    </script>
</body>
</html>