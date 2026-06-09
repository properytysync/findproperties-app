<?php
session_start();
require("config.php");

// Redirect to login if the admin is not logged in
if (!isset($_SESSION['auser'])) {
    header("location:index.php");
    exit();
}

// ✅ Prevent undefined variable warnings
$faviconPath = "";
$currentFaviconPath = "";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get form data for header
    $phoneNumber = $_POST['phone_number'] ?? '';
    $email = $_POST['email'] ?? '';

    // Get form data for footer
    $companyName = $_POST['company_name'] ?? '';
    $welcomeMessage = $_POST['welcome_message'] ?? '';
    $address = $_POST['address'] ?? '';
    $footerPhoneNumber = $_POST['footer_phone_number'] ?? '';
    $footerEmail = $_POST['footer_email'] ?? '';
    $facebookUrl = $_POST['facebook_url'] ?? '';
    $instagramUrl = $_POST['instagram_url'] ?? '';
    $linkedinUrl = $_POST['linkedin_url'] ?? '';
    $twitterUrl = $_POST['twitter_url'] ?? '';
    $currency = $_POST['currency'] ?? '';

    // Upload header logo image
    $headerLogoPath = "";
    if (isset($_FILES['header_logo']) && $_FILES['header_logo']['size'] > 0) {
        $targetDirectory = "../images/";
        $targetFile = $targetDirectory . basename($_FILES["header_logo"]["name"]);
        if (move_uploaded_file($_FILES["header_logo"]["tmp_name"], $targetFile)) {
            $headerLogoPath = "images/" . basename($_FILES["header_logo"]["name"]);
        } else {
            echo "Error uploading header logo.";
            exit();
        }
    }

    // Upload footer logo image
    $footerLogoPath = "";
    if (isset($_FILES['footer_logo']) && $_FILES['footer_logo']['size'] > 0) {
        $targetDirectory = "../images/";
        $targetFile = $targetDirectory . basename($_FILES["footer_logo"]["name"]);
        if (move_uploaded_file($_FILES["footer_logo"]["tmp_name"], $targetFile)) {
            $footerLogoPath = "images/" . basename($_FILES["footer_logo"]["name"]);
        } else {
            echo "Error uploading footer logo.";
            exit();
        }
    }

    // Upload favicon
    if (isset($_FILES['favicon']) && $_FILES['favicon']['size'] > 0) {
        $targetDirectory = "../images/";
        $targetFile = $targetDirectory . basename($_FILES["favicon"]["name"]);
        if (move_uploaded_file($_FILES["favicon"]["tmp_name"], $targetFile)) {
            $faviconPath = "images/" . basename($_FILES["favicon"]["name"]);

            $faviconPathEsc = mysqli_real_escape_string($con, $faviconPath);
            $updateFaviconQuery = "UPDATE site_info SET favicon_path = '$faviconPathEsc' WHERE id = 1";
            mysqli_query($con, $updateFaviconQuery);
        } else {
            echo "Error uploading favicon.";
            exit();
        }
    }

    // Update header info
    $phoneNumberEsc = mysqli_real_escape_string($con, $phoneNumber);
    $emailEsc = mysqli_real_escape_string($con, $email);

    $updateHeaderQuery = "
        UPDATE contact_info 
        SET 
            phone_number = '$phoneNumberEsc', 
            email = '$emailEsc'
        WHERE id = 1";
    mysqli_query($con, $updateHeaderQuery);

    // Update header logo path if a new logo is uploaded
    if (!empty($headerLogoPath)) {
        $headerLogoEsc = mysqli_real_escape_string($con, $headerLogoPath);
        $updateHeaderLogoQuery = "UPDATE site_info SET logo_path = '$headerLogoEsc' WHERE id = 1";
        mysqli_query($con, $updateHeaderLogoQuery);
    }

    // Update footer content
    $companyNameEsc = mysqli_real_escape_string($con, $companyName);
    $welcomeMessageEsc = mysqli_real_escape_string($con, $welcomeMessage);
    $addressEsc = mysqli_real_escape_string($con, $address);
    $footerPhoneEsc = mysqli_real_escape_string($con, $footerPhoneNumber);
    $footerEmailEsc = mysqli_real_escape_string($con, $footerEmail);
    $facebookEsc = mysqli_real_escape_string($con, $facebookUrl);
    $instagramEsc = mysqli_real_escape_string($con, $instagramUrl);
    $linkedinEsc = mysqli_real_escape_string($con, $linkedinUrl);
    $twitterEsc = mysqli_real_escape_string($con, $twitterUrl);

    $updateFooterQuery = "
        UPDATE footer_content 
        SET 
            company_name = '$companyNameEsc', 
            welcome_message = '$welcomeMessageEsc', 
            address = '$addressEsc', 
            phone_number = '$footerPhoneEsc', 
            email = '$footerEmailEsc', 
            facebook_url = '$facebookEsc', 
            instagram_url = '$instagramEsc', 
            linkedin_url = '$linkedinEsc', 
            twitter_url = '$twitterEsc'
        WHERE id = 1";
    mysqli_query($con, $updateFooterQuery);

    // Update footer logo path if a new logo is uploaded
    if (!empty($footerLogoPath)) {
        $footerLogoEsc = mysqli_real_escape_string($con, $footerLogoPath);
        $updateFooterLogoQuery = "UPDATE footer_content SET logo_path = '$footerLogoEsc' WHERE id = 1";
        mysqli_query($con, $updateFooterLogoQuery);
    }

    // Update currency
    $currencyEsc = mysqli_real_escape_string($con, $currency);
    $updateCurrencyQuery = "UPDATE site_info SET currency = '$currencyEsc' WHERE id = 1";
    mysqli_query($con, $updateCurrencyQuery);

    header("Location: success.php");
    exit();
}

// Fetch current header information
$query = "SELECT phone_number, email FROM contact_info WHERE id = 1";
$result = mysqli_query($con, $query);
$row = mysqli_fetch_assoc($result);
$phoneNumber = $row['phone_number'] ?? '';
$email = $row['email'] ?? '';

// Fetch current site info (✅ includes favicon_path)
$query = "SELECT logo_path, currency, favicon_path FROM site_info WHERE id = 1";
$result = mysqli_query($con, $query);
$row = mysqli_fetch_assoc($result);

$currentHeaderLogoPath = $row['logo_path'] ?? '';
$currentCurrency = $row['currency'] ?? '₦';
$currentFaviconPath = $row['favicon_path'] ?? '';

// Fetch current footer content
$query = "SELECT * FROM footer_content WHERE id = 1";
$result = mysqli_query($con, $query);
$row = mysqli_fetch_assoc($result);

$companyName = $row['company_name'] ?? '';
$welcomeMessage = $row['welcome_message'] ?? '';
$address = $row['address'] ?? '';
$footerPhoneNumber = $row['phone_number'] ?? '';
$footerEmail = $row['email'] ?? '';
$facebookUrl = $row['facebook_url'] ?? '';
$instagramUrl = $row['instagram_url'] ?? '';
$linkedinUrl = $row['linkedin_url'] ?? '';
$twitterUrl = $row['twitter_url'] ?? '';
$currentFooterLogoPath = $row['logo_path'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- ✅ Dynamic favicon for admin -->
    <?php if (!empty($currentFaviconPath)) : ?>
      <link rel="icon" href="../<?php echo $currentFaviconPath; ?>" type="image/x-icon">
      <link rel="shortcut icon" href="../<?php echo $currentFaviconPath; ?>" type="image/x-icon">
    <?php else: ?>
      <link rel="icon" href="assets/images/favicon.png" type="image/x-icon">
      <link rel="shortcut icon" href="assets/images/favicon.png" type="image/x-icon">
    <?php endif; ?>

    <title>Admin - Real Estate Web App</title>

    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/icofont.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/themify.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/flag-icon.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/feather-icon.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/slick.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/slick-theme.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/scrollbar.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/animate.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/datatables.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/owlcarousel.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
    <link id="color" rel="stylesheet" href="assets/css/color-1.css" media="screen">
    <link rel="stylesheet" type="text/css" href="assets/css/responsive.css">
	
	    <link rel="stylesheet" type="text/css" href="assets/css/responsive.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

   <style>
        .div-settings {
            width: 60%;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .div-settings h2 {
            color: #007BFF;
            margin-bottom: 20px;
            border-bottom: 2px solid #007BFF;
            padding-bottom: 10px;
        }
        .div-settings label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: bold;
        }
        .div-settings input[type="text"],
        .div-settings input[type="email"],
        .div-settings input[type="url"],
        .div-settings input[type="file"],
        .div-settings textarea {
            width: calc(100% - 20px);
            margin: 0 10px 20px 0;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            display: block;
        }
        .div-settings textarea { height: 100px; }
        .div-settings img { max-width: 100px; margin-bottom: 20px; display: block; }
        .div-settings input[type="submit"] {
            background-color: #007BFF; color: #fff; border: none;
            padding: 15px 30px; border-radius: 5px; cursor: pointer;
        }
        .div-settings input[type="submit"]:hover { background-color: #0056b3; }

        .banner-link-card{
            border:1px solid #e6e6e6;
            padding:16px;
            border-radius:10px;
            background:#f8fbff;
            margin: 8px 0 20px 0;
        }
        .banner-link-card a{
            display:inline-block;
            padding:10px 14px;
            border-radius:8px;
            background:#0d6efd;
            color:#fff;
            text-decoration:none;
            font-weight:600;
        }
        .banner-link-card a:hover{ background:#0b5ed7; }
    </style>
</head>
<body>
    <div class="page-wrapper compact-wrapper" id="pageWrapper">
      <div class="page-header">
        <div class="header-wrapper row m-0">
          <div class="header-logo-wrapper col-auto p-0">
            <div class="logo-wrapper"><a href="index.html"><img class="img-fluid for-light" src="../../assets/img/logo.png" alt=""><img class="img-fluid for-dark" src="../../assets/img/logo.png" alt=""></a></div>
          </div>
          <div class="nav-right col-xxl-7 col-xl-6 col-auto box-col-6 pull-right right-header p-0 ms-auto">
            <ul class="nav-menus">
              <li class="profile-nav onhover-dropdown p-0">
                <div class="d-flex align-items-center profile-media">
                  <div class="flex-grow-1"><span>Admin Portal</span>
                    <p class="mb-0">Admin <i class="middle fa fa-angle-down"></i></p>
                  </div>
                </div>
                <ul class="profile-dropdown onhover-show-div">
                  <li><a href="profile.php"><i data-feather="user"></i><span>Account </span></a></li>
                  <li><a href="logout.php"><i data-feather="log-in"> </i><span>Logout</span></a></li>
                </ul>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <div class="page-body-wrapper">
        <div class="sidebar-wrapper" data-layout="fill-svg">
          <div>
            <div class="logo-icon-wrapper"><a href="dasboard.php"><img class="img-fluid" src="assets/img/logo.png" alt=""></a></div>
            <?php include('menu.php'); ?>
          </div>
        </div>

        <div class="page-body">
          <div class="container-fluid">
            <div class="page-title">
              <div class="row">
                <div class="col-sm-6 p-0"></div>
                <div class="col-sm-6 p-0">
                  <ol class="breadcrumb">
                    <li class="breadcrumb-item">Dashboard</li>
                    <li class="breadcrumb-item active">Site Settings</li>
                  </ol>
                </div>
              </div>
            </div>
          </div>

          <div class="div-settings">
            <form method="post" action="" enctype="multipart/form-data">

                <h2>Header Settings</h2>
                <label for="phone_number">Phone Number:</label>
                <input type="text" id="phone_number" name="phone_number" value="<?php echo $phoneNumber; ?>">

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo $email; ?>">

                <label for="header_logo">Header Logo:</label>
                <input type="file" id="header_logo" name="header_logo">
                <?php if (!empty($currentHeaderLogoPath)) : ?>
                    <img src="../<?php echo $currentHeaderLogoPath; ?>" alt="Current Header Logo">
                <?php endif; ?>

                <h2>Footer Settings</h2>
                <label for="company_name">Company Name:</label>
                <input type="text" id="company_name" name="company_name" value="<?php echo $companyName; ?>">

                <label for="welcome_message">Welcome Message:</label>
                <textarea id="welcome_message" name="welcome_message"><?php echo $welcomeMessage; ?></textarea>

                <label for="address">Address:</label>
                <input type="text" id="address" name="address" value="<?php echo $address; ?>">

                <label for="footer_phone_number">Footer Phone Number:</label>
                <input type="text" id="footer_phone_number" name="footer_phone_number" value="<?php echo $footerPhoneNumber; ?>">

                <label for="footer_email">Footer Email:</label>
                <input type="email" id="footer_email" name="footer_email" value="<?php echo $footerEmail; ?>">

                <label for="facebook_url">Facebook URL:</label>
                <input type="url" id="facebook_url" name="facebook_url" value="<?php echo $facebookUrl; ?>">

                <label for="instagram_url">Instagram URL:</label>
                <input type="url" id="instagram_url" name="instagram_url" value="<?php echo $instagramUrl; ?>">

                <label for="linkedin_url">LinkedIn URL:</label>
                <input type="url" id="linkedin_url" name="linkedin_url" value="<?php echo $linkedinUrl; ?>">

                <label for="twitter_url">Twitter URL:</label>
                <input type="url" id="twitter_url" name="twitter_url" value="<?php echo $twitterUrl; ?>">

                <label for="footer_logo">Footer Logo:</label>
                <input type="file" id="footer_logo" name="footer_logo">
                <?php if (!empty($currentFooterLogoPath)) : ?>
                    <img src="../<?php echo $currentFooterLogoPath; ?>" alt="Current Footer Logo">
                <?php endif; ?>

                <!-- ✅ REPLACED Banner Area -->
                <h2>Homepage Banner / Slider</h2>
                <div class="banner-link-card">
                    <p style="margin:0 0 10px 0;">
                        Manage homepage banner display mode (Hero or Slider) and slides here:
                    </p>
                    <a href="banner_slider.php">Open Banner/Slider Settings</a>
                </div>

                <h2>Favicon</h2>
                <label for="favicon">Upload Favicon:</label>
                <input type="file" class="form-control-file" id="favicon" name="favicon">
                <?php if (!empty($currentFaviconPath)) : ?>
                    <img src="../<?php echo $currentFaviconPath; ?>" alt="Current Favicon" style="max-width:40px;margin-top:8px;">
                <?php endif; ?>
                <br>

                <h2>Currency Settings</h2>
                <label for="currency">Select Currency:</label>
                <select name="currency" id="currency">
                    <option value="₦" <?php echo $currentCurrency == '₦' ? 'selected' : ''; ?>>Naira (₦)</option>
                    <option value="$" <?php echo $currentCurrency == '$' ? 'selected' : ''; ?>>Dollar ($)</option>
                    <option value="€" <?php echo $currentCurrency == '€' ? 'selected' : ''; ?>>Euro (€)</option>
                    <option value="R" <?php echo $currentCurrency == 'R' ? 'selected' : ''; ?>>Rand (R)</option>
                    <option value="GH₵" <?php echo $currentCurrency == 'GH₵' ? 'selected' : ''; ?>>Ghana Cedi (GH₵)</option>
                    <option value="A$" <?php echo $currentCurrency == 'A$' ? 'selected' : ''; ?>>Australian Dollar (A$)</option>
                    <option value="C$" <?php echo $currentCurrency == 'C$' ? 'selected' : ''; ?>>Canadian Dollar (C$)</option>
                </select>
                <br><br>

                <input type="submit" value="Save Settings">
            </form>
          </div>

        </div>

        <footer class="footer">
          <div class="container-fluid">
            <div class="row">
             <div class="col-md-6 p-0 footer-copyright">
                <p class="mb-0">Copyright &copy; <?php echo date("Y"); ?> Real Estate Admin. All rights reserved.</p>
             </div>
            </div>
          </div>
        </footer>

      </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
  </body>
</html>
