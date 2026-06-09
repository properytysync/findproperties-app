<?php
session_start();
require("config.php");
require_once __DIR__ . "/_auth.php";

// ✅ Dashboard should be accessible to Admin + Agent
require_login();

// ✅ FIX: compact money formatter for dashboard cards (prevents overflow)
function money_compact(float $amount, string $currency = '₦'): string {
    $abs = abs($amount);

    if ($abs >= 1000000000000) return $currency . number_format($amount / 1000000000000, 2) . 'T';
    if ($abs >= 1000000000)    return $currency . number_format($amount / 1000000000, 2) . 'B';
    if ($abs >= 1000000)       return $currency . number_format($amount / 1000000, 2) . 'M';
    if ($abs >= 1000)          return $currency . number_format($amount / 1000, 2) . 'K';

    return $currency . number_format($amount, 0);
}

// Fetch currency from database
$currencyQuery = "SELECT currency FROM site_info WHERE id = 1";
$currencyResult = mysqli_query($con, $currencyQuery);
$currencyRow = mysqli_fetch_assoc($currencyResult);
$currency = $currencyRow['currency'] ?? '₦';

// Fetch the number of properties for sale that are active
$saleQuery = mysqli_query($con, "SELECT COUNT(*) FROM property WHERE stype='sale' AND status='available'");
$saleRow = mysqli_fetch_array($saleQuery);
$numberOfPropertiesForSale = (int)$saleRow[0];

// Fetch the number of properties for rent that are active
$rentQuery = mysqli_query($con, "SELECT COUNT(*) FROM property WHERE stype='rent' AND status='available'");
$rentRow = mysqli_fetch_array($rentQuery);
$numberOfPropertiesForRent = (int)$rentRow[0];

// Get sold properties data
$query1 = "SELECT COUNT(*) as total_sales, SUM(price) as total_amount 
           FROM property 
           WHERE status = 'sold out' AND price IS NOT NULL";
$result1 = mysqli_query($con, $query1);

if ($result1 && $row = mysqli_fetch_assoc($result1)) {
    $total_sales = (int)$row['total_sales'];
    $total_amount = (float)$row['total_amount'];
} else {
    $total_sales = 0;
    $total_amount = 0;
}

// Get sold properties details for display
$soldPropertiesQuery = "SELECT pid, title, price FROM property WHERE status = 'sold out' ORDER BY price DESC";
$soldPropertiesResult = mysqli_query($con, $soldPropertiesQuery);
$soldProperties = [];
if ($soldPropertiesResult) {
    while ($row = mysqli_fetch_assoc($soldPropertiesResult)) {
        $soldProperties[] = $row;
    }
}

// ✅ FIX: prevent division by zero in progress bars
$totalAll = $numberOfPropertiesForSale + $numberOfPropertiesForRent + $total_sales;
$totalAllSafe = ($totalAll > 0) ? $totalAll : 1;
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Real Estate Admin Dashboard">
    <meta name="keywords" content="real estate, property, admin dashboard">
    <meta name="author" content="Real Estate Admin">
    <link rel="icon" href="assets/images/favicon.png" type="image/x-icon">
    <link rel="shortcut icon" href="assets/images/favicon.png" type="image/x-icon">
    <title>Admin Dashboard - Real Estate</title>
    <!-- Google font-->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
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
    <link rel="stylesheet" type="text/css" href="assets/css/vendors/scrollbar.css">
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
        }

        .dashboard-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden; /* ✅ FIX: keep content inside card */
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .sales-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
            overflow: hidden; /* ✅ FIX */
        }

        /* ✅ FIX: prevent big numbers from overflowing */
        .stat-number {
            font-size: clamp(1.4rem, 2.2vw, 2.5rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-variant-numeric: tabular-nums;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 500;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 15px;
        }

        .icon-sale { background: rgba(16, 185, 129, 0.1); color: var(--success-color); }
        .icon-rent { background: rgba(59, 130, 246, 0.1); color: var(--info-color); }
        .icon-sold { background: rgba(245, 158, 11, 0.1); color: var(--warning-color); }
        .icon-revenue { background: rgba(102, 126, 234, 0.1); color: var(--primary-color); }

        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 30px;
        }

        .property-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .property-item {
            background: var(--light-color);
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            transition: all 0.2s ease;
        }

        .property-item:hover {
            background: white;
            border-color: var(--primary-color);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .property-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--success-color);
        }

        /* ✅ FIX: clamp big revenue text too */
        .revenue-amount {
            font-size: clamp(1.8rem, 3vw, 2.8rem);
            font-weight: 900;
            line-height: 1.1;
            margin: 15px 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 768px) {
            .dashboard-card { padding: 20px; margin-bottom: 20px; }
            .sales-card { padding: 25px; }
        }

        @media (max-width: 576px) {
            .stat-icon { width: 50px; height: 50px; font-size: 1.5rem; }
        }

        /* Custom scrollbar */
        .property-list::-webkit-scrollbar { width: 6px; }
        .property-list::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .property-list::-webkit-scrollbar-thumb { background: var(--primary-color); border-radius: 10px; }
        .property-list::-webkit-scrollbar-thumb:hover { background: var(--secondary-color); }

        .badge-sold {
            background: var(--success-color);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
  </head>

  <body>
   

    <div class="tap-top"><i data-feather="chevrons-up"></i></div>

    <div class="page-wrapper compact-wrapper" id="pageWrapper">
      <div class="page-header">
        <div class="header-wrapper row m-0">
          <div class="header-logo-wrapper col-auto p-0">
            <div class="logo-wrapper">
              <a href="dashboard.php">
                <img class="img-fluid for-light" src="../../assets/img/logo.png" alt="">
                <img class="img-fluid for-dark" src="../../assets/img/logo.png" alt="">
              </a>
            </div>
            <div class="toggle-sidebar">
              <svg class="sidebar-toggle">
                <use href="assets/svg/icon-sprite.svg#stroke-animation"></use>
              </svg>
            </div>
          </div>

          <div class="nav-right col-xxl-7 col-xl-6 col-auto box-col-6 pull-right right-header p-0 ms-auto">
            <ul class="nav-menus">
              <li>
                <div class="mode">
                  <svg>
                    <use href="assets/svg/icon-sprite.svg#fill-dark"></use>
                  </svg>
                </div>
              </li>
              <li class="profile-nav onhover-dropdown p-0">
                <div class="d-flex align-items-center profile-media">
                  <div class="flex-grow-1">
                    <span>Admin Portal</span>
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
          <div>
            <div class="logo-wrapper">
              <a href="dashboard.php"><img class="img-fluid" src="../../assets/img/logo.png" alt=""></a>
              <div class="toggle-sidebar">
                <svg class="sidebar-toggle">
                  <use href="../assets/svg/icon-sprite.svg#toggle-icon"></use>
                </svg>
              </div>
            </div>
            <div class="logo-icon-wrapper"><a href="dashboard.php"><img class="img-fluid" src="../assets/img/logo.png" alt=""></a></div>
            <?php include('menu.php'); ?>
          </div>
        </div>

        <div class="page-body">
          <div class="container-fluid">
            <div class="page-title">
              <div class="row">
                <div class="col-sm-6 p-0">
                  <h3>Dashboard Overview</h3>
                </div>
                <div class="col-sm-6 p-0">
                  <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                      <a href="dashboard.php">
                        <svg class="stroke-icon">
                          <use href="assets/svg/icon-sprite.svg#stroke-home"></use>
                        </svg>
                      </a>
                    </li>
                    <li class="breadcrumb-item">Dashboard</li>
                    <li class="breadcrumb-item active">Overview</li>
                  </ol>
                </div>
              </div>
            </div>
          </div>

          <div class="container-fluid default-dashboard">
            <div class="row mb-4">
              <div class="col-12">
                <div class="welcome-card">
                  <div class="row align-items-center">
                    <div class="col-md-8">
                      <h1 class="text-white mb-2">Welcome back, Admin! 👋</h1>
                      <p class="text-white mb-3 opacity-75">Here's what's happening with your properties today.</p>
                      <a class="btn btn-light btn-lg" href="propertyadd.php">
                        <i class="fas fa-plus me-2"></i>Add New Property
                      </a>
                    </div>
                    <div class="col-md-4 text-center d-none d-md-block">
                      <img src="../assets/images/dashboard/welcome.png" alt="Welcome" class="img-fluid" style="max-height: 150px;">
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="row mb-4">
              <div class="col-xl-3 col-md-6">
                <div class="dashboard-card">
                  <div class="stat-icon icon-sale"><i class="fas fa-home"></i></div>
                  <div class="stat-number text-dark"><?php echo $numberOfPropertiesForSale; ?></div>
                  <div class="stat-label">Properties for Sale</div>
                </div>
              </div>

              <div class="col-xl-3 col-md-6">
                <div class="dashboard-card">
                  <div class="stat-icon icon-rent"><i class="fas fa-building"></i></div>
                  <div class="stat-number text-dark"><?php echo $numberOfPropertiesForRent; ?></div>
                  <div class="stat-label">Properties for Rent</div>
                </div>
              </div>

              <div class="col-xl-3 col-md-6">
                <div class="dashboard-card">
                  <div class="stat-icon icon-sold"><i class="fas fa-check-circle"></i></div>
                  <div class="stat-number text-dark"><?php echo $total_sales; ?></div>
                  <div class="stat-label">Properties Sold</div>
                </div>
              </div>

              <div class="col-xl-3 col-md-6">
                <div class="dashboard-card">
                  <div class="stat-icon icon-revenue"><i class="fas fa-chart-line"></i></div>

                  <!-- ✅ FIX: show compact revenue in small card + keep full in title tooltip -->
                  <div class="stat-number text-dark"
                       title="<?php echo htmlspecialchars($currency . number_format($total_amount, 0)); ?>">
                    <?php echo money_compact($total_amount, $currency); ?>
                  </div>

                  <div class="stat-label">Total Revenue</div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-xl-8 col-lg-7 mb-4">
                <div class="sales-card">
                  <div class="row align-items-center">
                    <div class="col-md-6">
                      <h3 class="text-white mb-3"><i class="fas fa-trophy me-2"></i>Sales Performance</h3>

                      <!-- ✅ keep full value here (big card) -->
                      <div class="revenue-amount text-white"
                           title="<?php echo htmlspecialchars($currency . number_format($total_amount, 2)); ?>">
                        <?php echo $currency . number_format($total_amount, 2); ?>
                      </div>

                      <p class="text-white opacity-75 mb-0">Total Revenue Generated</p>
                    </div>

                    <div class="col-md-6 text-center">
                      <div class="mb-3">
                        <div class="stat-number text-white"><?php echo $total_sales; ?></div>
                        <div class="stat-label">Properties Sold</div>
                      </div>
                      <span class="badge-sold"><i class="fas fa-check me-1"></i>Active</span>
                    </div>
                  </div>

                  <?php if (!empty($soldProperties)): ?>
                  <div class="mt-4">
                    <h5 class="text-white mb-3">Recently Sold Properties</h5>
                    <div class="property-list">
                      <?php foreach ($soldProperties as $property): ?>
                      <div class="property-item bg-white bg-opacity-10 border-white border-opacity-20">
                        <div class="row align-items-center">
                          <div class="col-8">
                            <div class="text-white fw-semibold">Property #<?php echo $property['pid']; ?></div>
                            <div class="text-white text-opacity-75 small">
                              <?php echo htmlspecialchars(substr($property['title'], 0, 40)); ?><?php echo strlen($property['title']) > 40 ? '...' : ''; ?>
                            </div>
                          </div>
                          <div class="col-4 text-end">
                            <div class="property-price text-white"><?php echo $currency . number_format((float)$property['price'], 0); ?></div>
                          </div>
                        </div>
                      </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                  <?php else: ?>
                  <div class="mt-4 text-center py-4">
                    <i class="fas fa-home fa-3x text-white opacity-50 mb-3"></i>
                    <p class="text-white opacity-75 mb-0">No properties sold yet</p>
                  </div>
                  <?php endif; ?>
                </div>
              </div>

              <div class="col-xl-4 col-lg-5">
                <div class="dashboard-card mb-4">
                  <h5 class="mb-4"><i class="fas fa-chart-pie me-2 text-primary"></i>Property Overview</h5>

                  <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="text-muted">For Sale</span>
                      <span class="fw-semibold"><?php echo $numberOfPropertiesForSale; ?></span>
                    </div>
                    <div class="progress" style="height: 8px;">
                      <div class="progress-bar bg-success" style="width: <?php echo ($numberOfPropertiesForSale / $totalAllSafe) * 100; ?>%"></div>
                    </div>
                  </div>

                  <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="text-muted">For Rent</span>
                      <span class="fw-semibold"><?php echo $numberOfPropertiesForRent; ?></span>
                    </div>
                    <div class="progress" style="height: 8px;">
                      <div class="progress-bar bg-info" style="width: <?php echo ($numberOfPropertiesForRent / $totalAllSafe) * 100; ?>%"></div>
                    </div>
                  </div>

                  <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span class="text-muted">Sold</span>
                      <span class="fw-semibold"><?php echo $total_sales; ?></span>
                    </div>
                    <div class="progress" style="height: 8px;">
                      <div class="progress-bar bg-warning" style="width: <?php echo ($total_sales / $totalAllSafe) * 100; ?>%"></div>
                    </div>
                  </div>
                </div>

                <div class="dashboard-card">
                  <h5 class="mb-4"><i class="fas fa-rocket me-2 text-primary"></i>Quick Actions</h5>

                  <div class="row g-3">
                    <div class="col-6">
                      <a href="propertyadd.php" class="btn btn-primary w-100 d-flex flex-column align-items-center py-3">
                        <i class="fas fa-plus fa-2x mb-2"></i><span>Add Property</span>
                      </a>
                    </div>
                    <div class="col-6">
                      <a href="propertyview.php" class="btn btn-outline-primary w-100 d-flex flex-column align-items-center py-3">
                        <i class="fas fa-eye fa-2x mb-2"></i><span>View All</span>
                      </a>
                    </div>
                    <div class="col-6">
                      <a href="users.php" class="btn btn-outline-success w-100 d-flex flex-column align-items-center py-3">
                        <i class="fas fa-users fa-2x mb-2"></i><span>Users</span>
                      </a>
                    </div>
                    <div class="col-6">
                      <a href="settings.php" class="btn btn-outline-info w-100 d-flex flex-column align-items-center py-3">
                        <i class="fas fa-cog fa-2x mb-2"></i><span>Settings</span>
                      </a>
                    </div>
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
              <div class="col-md-6 p-0 text-end">
                <p class="mb-0">Dashboard v2.1</p>
              </div>
            </div>
          </div>
        </footer>
      </div>
    </div>

    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/icons/feather-icon/feather.min.js"></script>
    <script src="../assets/js/icons/feather-icon/feather-icon.js"></script>
    <script src="../assets/js/scrollbar/simplebar.js"></script>
    <script src="../assets/js/scrollbar/custom.js"></script>
    <script src="../assets/js/config.js"></script>
    <script src="../assets/js/sidebar-menu.js"></script>
    <script src="../assets/js/sidebar-pin.js"></script>
    <script src="../assets/js/slick/slick.min.js"></script>
    <script src="../assets/js/slick/slick.js"></script>
    <script src="../assets/js/header-slick.js"></script>
    <script src="../assets/js/chart/morris-chart/raphael.js"></script>
    <script src="../assets/js/chart/morris-chart/morris.js"></script>
    <script src="../assets/js/chart/apex-chart/apex-chart.js"></script>
    <script src="../assets/js/chart/apex-chart/stock-prices.js"></script>
    <script src="../assets/js/chart/apex-chart/moment.min.js"></script>
    <script src="../assets/js/notify/bootstrap-notify.min.js"></script>
    <script src="../assets/js/dashboard/default.js"></script>
    <script src="../assets/js/notify/index.js"></script>
    <script src="../assets/js/datatable/datatables/jquery.dataTables.min.js"></script>
    <script src="../assets/js/datatable/datatables/datatable.custom.js"></script>
    <script src="../assets/js/datatable/datatables/datatable.custom1.js"></script>
    <script src="../assets/js/owlcarousel/owl.carousel.js"></script>
    <script src="../assets/js/owlcarousel/owl-custom.js"></script>
    <script src="../assets/js/typeahead/handlebars.js"></script>
    <script src="../assets/js/typeahead/typeahead.bundle.js"></script>
    <script src="../assets/js/typeahead/typeahead.custom.js"></script>
    <script src="../assets/js/typeahead-search/handlebars.js"></script>
    <script src="../assets/js/typeahead-search/typeahead-custom.js"></script>
    <script src="../assets/js/height-equal.js"></script>
    <script src="../assets/js/script.js"></script>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.dashboard-card');
        cards.forEach((card, index) => {
          card.style.opacity = '0';
          card.style.transform = 'translateY(20px)';
          setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
          }, index * 100);
        });
      });
    </script>
  </body>
</html>
