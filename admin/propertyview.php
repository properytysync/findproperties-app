<?php
session_start();
require("config.php");
require_once __DIR__ . "/_auth.php";

require_login();

// Number of properties per page
$limit = 9;

// Get the current page or set default to 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch the property type based on the filter
$stype = isset($_GET['stype']) ? $_GET['stype'] : 'All';
$filter_condition = '';

// Determine filter condition based on selected type
if ($stype === 'sale') {
    $filter_condition = " WHERE stype = 'sale' AND status != 'sold out'";
} elseif ($stype === 'rent') {
    $filter_condition = " WHERE stype = 'rent' AND status != 'sold out'";
} elseif ($stype === 'sold out') {
    $filter_condition = " WHERE status = 'sold out'";
}

// Count total number of properties for pagination
$total_query = mysqli_query($con, "SELECT COUNT(*) as total FROM property" . $filter_condition);
$total_row = mysqli_fetch_assoc($total_query);
$total_properties = $total_row['total'];

// Calculate total pages
$total_pages = ceil($total_properties / $limit);

// Fetch the properties for the current page, ordered by pid DESC
$query = mysqli_query($con, "SELECT * FROM property" . $filter_condition . " ORDER BY pid DESC LIMIT $limit OFFSET $offset");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dunzo admin is super flexible, powerful, clean & modern responsive bootstrap 5 admin template with unlimited possibilities.">
    <meta name="keywords" content="admin template, Dunzo admin template, dashboard template, flat admin template, responsive admin template, web app">
    <meta name="author" content="pixelstrap">
    <link rel="icon" href="assets/images/favicon.png" type="image/x-icon">
    <link rel="shortcut icon" href="assets/images/favicon.png" type="image/x-icon">
    <title>Admin</title>
    <!-- Google font-->
    <link href="https://fonts.googleapis.com/css?family=Outfit:400,400i,500,500i,700,700i&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,300i,400,400i,500,500i,700,700i,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="assets/css/font-awesome.css">
    <!-- ico-font-->
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/icofont.css">
    <!-- Themify icon-->
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/themify.css">
    <!-- Flag icon-->
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/flag-icon.css">
    <!-- Feather icon-->
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/feather-icon.css">
    <!-- Plugins css start-->
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/slick.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/slick-theme.css">
    <link rel="stylesheet" type="text/csstype="text/css" href="assets/css/vendors/scrollbar.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/animate.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/datatables.css">
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/owlcarousel.css">
    <!-- Plugins css Ends-->
    <!-- Bootstrap css-->
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/bootstrap.css">
    <!-- App css-->
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
    <link id="color" rel="stylesheet" href="assets/css/color-1.css" media="screen">
    <!-- Responsive css-->
    <link rel="stylesheet" type="text/css" href="assets/css/responsive.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .table-responsive {
            width: 100%;
            margin-bottom: 15px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            width: 100%;
            max-width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .table th, .table td {
            padding: 8px;
            text-align: left;
            border-top: 1px solid #dee2e6;
        }

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
        }

        .table tbody + tbody {
            border-top: 2px solid #dee2e6;
        }

        .table .table {
            background-color: #fff;
        }

        @media screen and (max-width: 600px) {
            .table-responsive {
                border: 0;
            }

            .table th, .table td {
                padding: 8px;
                display: block;
                text-align: right;
            }

            .table th {
                text-align: right;
            }

            .table td::before {
                content: attr(data-label);
                float: left;
                font-weight: bold;
            }
        }
    </style>
</head>
<body>
    <!-- loader starts-->
    <div class="loader-wrapper">
        <div class="theme-loader">
            <div class="loader-p"></div>
        </div>
    </div>
    <!-- loader ends-->
    <!-- tap on top starts-->
    <div class="tap-top"><i data-feather="chevrons-up"></i></div>
    <!-- tap on tap ends-->
    <!-- page-wrapper Start-->
    <div class="page-wrapper compact-wrapper" id="pageWrapper">
        <!-- Page Header Start-->
        <div class="page-header">
            <div class="header-wrapper row m-0">
                <div class="header-logo-wrapper col-auto p-0">
                    <div class="logo-wrapper"><a href="index.html"><img class="img-fluid for-light" src="assets/img/logo.png" alt=""><img class="img-fluid for-dark" src="assets/img/logo.png" alt=""></a></div>
                    <div class="toggle-sidebar">
                        <svg class="sidebar-toggle">
                            <use href="assets/svg/icon-sprite.svg#stroke-animation"></use>
                        </svg>
                    </div>
                </div>
                <div class="nav-right col-xxl-7 col-xl-6 col-auto box-col-6 pull-right right-header p-0 ms-auto">
                    <ul class="nav-menus">
                        <li class="onhover-dropdown"></li>
                        <li>
                            <div class="mode">
                                <svg>
                                    <use href="assets/svg/icon-sprite.svg#fill-dark"></use>
                                </svg>
                            </div>
                        </li>
                        <li class="profile-nav onhover-dropdown p-0">
                            <div class="d-flex align-items-center profile-media">
                                <div class="flex-grow-1"><span>Admin Portal</span>
                                    <p class="mb-0">Admin <i class="middle fa fa-angle-down"></i></p>
                                </div>
                            </div>
                            <ul class="profile-dropdown onhover-show-div">
                                <li><a href="profile.php"><i data-feather="user"></i><span>Account </span></a></li>
                                <li><a href="settings.php"><i data-feather="settings"></i><span>Settings</span></a></li>
                                <li><a href="logout.php"><i data-feather="log-in"></i><span>Logout</span></a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <script class="result-template" type="text/x-handlebars-template">
                    <div class="ProfileCard u-cf">
                        <div class="ProfileCard-avatar"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-airplay m-0"><path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1"></path><polygon points="12 15 17 21 7 21 12 15"></polygon></svg></div>
                        <div class="ProfileCard-details">
                            <div class="ProfileCard-realName">{{name}}</div>
                        </div>
                    </div>
                </script>
                <script class="empty-template" type="text/x-handlebars-template"><div class="EmptyMessage">Your search turned up 0 results. This most likely means the backend is down, yikes!</div></script>
            </div>
        </div>
        <!-- Page Header Ends-->
        <!-- Page Body Start-->
        <div class="page-body-wrapper">
            <!-- Page Sidebar Start-->
            <div class="sidebar-wrapper" data-layout="fill-svg">
                <div>
                    <div class="logo-wrapper">
                        <div class="toggle-sidebar"></div>
                    </div>
                    <div class="logo-icon-wrapper"><a href="dasboard.php"><img class="img-fluid" src="assets/img/logo.png" alt=""></a></div>
                    <?php include('menu.php'); ?>
                </div>
            </div>
            <!-- Page Sidebar Ends-->
            <div class="page-body">
                <div class="container-fluid">
                    <div class="page-title">
                        <div class="row">
                            <div class="col-sm-6 p-0"></div>
                            <div class="col-sm-6 p-0">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.html">
                                        <svg class="stroke-icon">
                                            <use href="assets/svg/icon-sprite.svg#stroke-home"></use>
                                        </svg></a></li>
                                    <li class="breadcrumb-item">Dashboard</li>
                                    <li class="breadcrumb-item active">View Property</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Container-fluid starts-->
                <div class="container">
                    <h2>View Properties</h2>
                    <?php
                    if (isset($_GET['msg'])) {
                        echo '<div class="alert alert-success">' . htmlspecialchars($_GET['msg']) . '</div>';
                    }
                    ?>
                    <div class="mb-4">
                        <a href="propertyview.php?stype=All" class="btn btn-primary">All</a>
                        <a href="propertyview.php?stype=sale" class="btn btn-warning">For Sale</a>
                        <a href="propertyview.php?stype=rent" class="btn btn-info">For Rent</a>
                        <a href="propertyview.php?stype=sold out" class="btn btn-danger">Sold Out</a>
                    </div>
                    <div class="row">
                        <?php
                        while ($row = mysqli_fetch_assoc($query)) {
                        ?>
                            <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h5>
                                        <p class="card-text">
                                            <strong>Property ID:</strong> <?php echo htmlspecialchars($row['pid']); ?><br>
                                            <strong>Type:</strong> <?php echo htmlspecialchars($row['type']); ?><br>
                                            <strong>Selling Type:</strong> <?php echo htmlspecialchars($row['stype']); ?><br>
                                            <strong>Status:</strong> <?php echo htmlspecialchars($row['status']); ?><br>
                                            <strong>View Count:</strong> <?php echo htmlspecialchars($row['views']); ?><br>
                                            <strong>Price:</strong> <?php echo htmlspecialchars(number_format($row['price'])); ?><br>
                                            <strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?><br>
                                            <strong>City:</strong> <?php echo htmlspecialchars($row['city']); ?><br>
                                        </p>
                                        <div class="property-images">
                                            <?php
                                            $image_columns = ['pimage', 'pimage1', 'pimage2', 'pimage3', 'pimage4'];
                                            foreach ($image_columns as $image_column) {
                                                if (!empty($row[$image_column])) {
                                                    echo '<img src="property/' . htmlspecialchars($row[$image_column]) . '" alt="Property Image" style="height: 50px; width: 50px; margin: 2px;" />';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="card-footer text-center">
									<a href="property_flyer.php?pid=<?php echo (int)$row['pid']; ?>" class="btn btn-success btn-sm" target="_blank">
  Download Flyer (PDF)
</a>
                                        <a href="edit_property.php?pid=<?php echo htmlspecialchars($row['pid']); ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="delete_property.php?pid=<?php echo htmlspecialchars($row['pid']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this property?');">Delete</a>
                                    </div>
                                </div>
                            </div>
                        <?php
                        }
                        ?>
                    </div>
                    <!-- Pagination -->
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="propertyview.php?stype=<?php echo $stype; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
                <!-- Container-fluid Ends-->
            </div>
            <!-- footer start-->
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-6 p-0 footer-copyright">
                            <p class="mb-0">Copyright © <?php echo date("Y"); ?> Real Estate Admin. All rights reserved.</p>
                        </div>
                        <div class="col-md-6 p-0"></div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <!-- latest jquery-->
    <script src="assets/js/jquery.min.js"></script>
    <!-- Bootstrap js-->
    <script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>
    <!-- feather icon js-->
    <script src="assets/js/icons/feather-icon/feather.min.js"></script>
    <script src="assets/js/icons/feather-icon/feather-icon.js"></script>
    <!-- scrollbar js-->
    <script src="assets/js/scrollbar/simplebar.js"></script>
    <script src="assets/js/scrollbar/custom.js"></script>
    <!-- Sidebar jquery-->
    <script src="assets/js/config.js"></script>
    <!-- Plugins JS start-->
    <script src="assets/js/sidebar-menu.js"></script>
    <script src="assets/js/sidebar-pin.js"></script>
    <script src="assets/js/slick/slick.min.js"></script>
    <script src="assets/js/slick/slick.js"></script>
    <script src="assets/js/header-slick.js"></script>
    <script src="assets/js/chart/morris-chart/raphael.js"></script>
    <script src="assets/js/chart/morris-chart/morris.js"></script>
    <script src="assets/js/chart/morris-chart/prettify.min.js"></script>
    <script src="assets/js/chart/apex-chart/apex-chart.js"></script>
    <script src="assets/js/chart/apex-chart/stock-prices.js"></script>
    <script src="assets/js/chart/apex-chart/moment.min.js"></script>
    <script src="assets/js/notify/bootstrap-notify.min.js"></script>
    <script src="assets/js/dashboard/default.js"></script>
    <script src="assets/js/notify/index.js"></script>
    <script src="assets/js/datatable/datatables/jquery.dataTables.min.js"></script>
    <script src="assets/js/datatable/datatables/datatable.custom.js"></script>
    <script src="assets/js/datatable/datatables/datatable.custom1.js"></script>
    <script src="assets/js/owlcarousel/owl.carousel.js"></script>
    <script src="assets/js/owlcarousel/owl-custom.js"></script>
    <script src="assets/js/typeahead/handlebars.js"></script>
    <script src="assets/js/typeahead/typeahead.bundle.js"></script>
    <script src="assets/js/typeahead/typeahead.custom.js"></script>
    <script src="assets/js/typeahead-search/handlebars.js"></script>
    <script src="assets/js/typeahead-search/typeahead-custom.js"></script>
    <script src="assets/js/height-equal.js"></script>
    <!-- Plugins JS Ends-->
    <!-- Theme js-->
    <script src="assets/js/script.js"></script>
    <!-- Plugin used-->
</body>
</html>