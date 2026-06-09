<?php
session_start();
require("config.php");

if (!isset($_SESSION['auser'])) {
    header("location:index.php");
    exit;
}

// Fetch current settings
$site_info_query = mysqli_query($con, "SELECT * FROM site_info WHERE id=1");
$site_info = mysqli_fetch_array($site_info_query);

// Handle form submission
if (isset($_POST['update_settings'])) {
    $paystack_public_key = mysqli_real_escape_string($con, $_POST['paystack_public_key']);
    $paystack_secret_key = mysqli_real_escape_string($con, $_POST['paystack_secret_key']);
    $viewing_fee = mysqli_real_escape_string($con, $_POST['viewing_fee']);
    $enable_viewing_payment = isset($_POST['enable_viewing_payment']) ? 1 : 0;

    $update_sql = "UPDATE site_info SET 
        paystack_public_key = '$paystack_public_key',
        paystack_secret_key = '$paystack_secret_key',
        viewing_fee = '$viewing_fee',
        enable_viewing_payment = $enable_viewing_payment
        WHERE id=1";

    if (mysqli_query($con, $update_sql)) {
        $msg = "<div class='alert alert-success'>Settings Updated Successfully</div>";
    } else {
        $error = "<div class='alert alert-warning'>Update Failed: " . mysqli_error($con) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Settings</title>
    <!-- Include same CSS/JS as contactview.php -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&display=swap" rel="stylesheet">
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
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>
<body>
    <div class="page-wrapper compact-wrapper" id="pageWrapper">
        <div class="page-header">
            <!-- Same header as contactview.php -->
            <div class="header-wrapper row m-0">
                <div class="header-logo-wrapper col-auto p-0">
                    <div class="logo-wrapper"><a href="index.html"><img class="img-fluid for-light" src="../../assets/img/logo.png" alt=""><img class="img-fluid for-dark" src="../../assets/img/logo.png" alt=""></a></div>
                    <div class="toggle-sidebar">
                        <svg class="sidebar-toggle"> 
                            <use href="assets/svg/icon-sprite.svg#stroke-animation"></use>
                        </svg>
                    </div>
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
                                <li><a href="settings.php"><i data-feather="settings"></i><span>Settings</span></a></li>
                                <li><a href="logout.php"><i data-feather="log-in"> </i><span>Logout</span></a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="page-body-wrapper">
            <div class="sidebar-wrapper" data-layout="fill-svg">
                <?php include('menu.php'); ?>
            </div>
            <div class="page-body">
                <div class="container-fluid">        
                    <div class="page-title">
                        <div class="row">
                            <div class="col-sm-6 p-0"></div>
                            <div class="col-sm-6 p-0">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="dashboard.php"><svg class="stroke-icon"><use href="assets/svg/icon-sprite.svg#stroke-home"></use></svg></a></li>
                                    <li class="breadcrumb-item">Settings</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Payment Settings</h4>
                                    <?php if (isset($msg)) echo $msg; ?>
                                    <?php if (isset($error)) echo $error; ?>
                                </div>
                                <div class="card-body">
                                    <form method="post" class="row g-3">
                                        <div class="col-md-6">
                                            <label for="paystack_public_key" class="form-label">Paystack Public Key</label>
                                            <input type="text" class="form-control" id="paystack_public_key" name="paystack_public_key" value="<?php echo htmlspecialchars($site_info['paystack_public_key'] ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="paystack_secret_key" class="form-label">Paystack Secret Key</label>
                                            <input type="text" class="form-control" id="paystack_secret_key" name="paystack_secret_key" value="<?php echo htmlspecialchars($site_info['paystack_secret_key'] ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="viewing_fee" class="form-label">Viewing Fee (in NGN)</label>
                                            <input type="number" step="0.01" class="form-control" id="viewing_fee" name="viewing_fee" value="<?php echo htmlspecialchars($site_info['viewing_fee'] ?? '1000.00'); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="enable_viewing_payment" name="enable_viewing_payment" <?php echo ($site_info['enable_viewing_payment'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="enable_viewing_payment">Enable Viewing Payment Requirement</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" name="update_settings" class="btn btn-primary">Update Settings</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
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
    <!-- Scripts same as contactview.php -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="assets/js/icons/feather-icon/feather.min.js"></script>
    <script src="assets/js/icons/feather-icon/feather-icon.js"></script>
    <script src="assets/js/scrollbar/simplebar.js"></script>
    <script src="assets/js/scrollbar/custom.js"></script>
    <script src="assets/js/config.js"></script>
    <script src="assets/js/sidebar-menu.js"></script>
    <script src="assets/js/sidebar-pin.js"></script>
    <script src="assets/js/slick/slick.min.js"></script>
    <script src="assets/js/slick/slick.js"></script>
    <script src="assets/js/header-slick.js"></script>
    <script src="assets/js/chart/apex-chart/apex-chart.js"></script>
    <script src="assets/js/notify/bootstrap-notify.min.js"></script>
    <script src="assets/js/dashboard/default.js"></script>
    <script src="assets/js/notify/index.js"></script>
    <script src="assets/js/datatable/datatables/jquery.dataTables.min.js"></script>
    <script src="assets/js/datatable/datatables/datatable.custom.js"></script>
    <script src="assets/js/owlcarousel/owl.carousel.js"></script>
    <script src="assets/js/owlcarousel/owl-custom.js"></script>
    <script src="assets/js/typeahead/handlebars.js"></script>
    <script src="assets/js/typeahead/typeahead.bundle.js"></script>
    <script src="assets/js/typeahead/typeahead.custom.js"></script>
    <script src="assets/js/typeahead-search/handlebars.js"></script>
    <script src="assets/js/typeahead-search/typeahead-custom.js"></script>
    <script src="assets/js/height-equal.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>