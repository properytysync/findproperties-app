<?php
session_start();
require("config.php");

if(!isset($_SESSION['auser'])) {
    header("location:index.php");
}

// Fetch about data
$query = mysqli_query($con, "SELECT * FROM about");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dunzo admin is super flexible, powerful, clean &amp; modern responsive bootstrap 5 admin template with unlimited possibilities.">
    <meta name="keywords" content="admin template, Dunzo admin template, dashboard template, flat admin template, responsive admin template, web app">
    <meta name="author" content="pixelstrap">
    <link rel="icon" href="assets/images/favicon.png" type="image/x-icon">
    <link rel="shortcut icon" href="assets/images/favicon.png" type="image/x-icon">
    <title>About Management - Real Estate Admin</title>
    <!-- Google font-->
    <link href="../../css?family=Outfit:400,400i,500,500i,700,700i&amp;display=swap" rel="stylesheet">
    <link href="../../css-1?family=Roboto:300,300i,400,400i,500,500i,700,700i,900&amp;display=swap" rel="stylesheet">
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
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <style>
        /* Enhanced Content Area Styles */
        .page-body {
            background: #f8fafc;
            min-height: calc(100vh - 130px);
        }
        
        .container-fluid {
            padding: 20px 30px;
        }
        
        .page-title {
            margin-bottom: 30px;
        }
        
        .page-title h3 {
            color: #2c323f;
            font-weight: 600;
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        
        .page-title .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
        }
        
        .page-title .breadcrumb-item a {
            color: #666;
        }
        
        .page-title .breadcrumb-item.active {
            color: #4361ee;
        }
        
        /* Main Content Card */
        .main-content-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .card-header {
            background: white;
            padding: 25px 30px;
            border-bottom: 1px solid #eef2f7;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2c323f;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-title i {
            color: #4361ee;
        }
        
        .card-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .btn-primary {
            background: #4361ee;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary:hover {
            background: #3a56d4;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
            color: white;
            text-decoration: none;
        }
        
        /* Message Alert */
        .alert-message {
            margin: 20px 30px;
            padding: 15px 20px;
            border-radius: 8px;
            background: #e3f2fd;
            color: #1976d2;
            border-left: 4px solid #1976d2;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Enhanced Table Styling */
        .table-container {
            padding: 0 30px 30px;
            overflow-x: auto;
        }
        
        .content-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 0;
        }
        
        .content-table thead th {
            background: #f8fafc;
            color: #666;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 18px 20px;
            border-bottom: 2px solid #eef2f7;
            white-space: nowrap;
        }
        
        .content-table tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid #f1f1f1;
        }
        
        .content-table tbody tr:hover {
            background-color: #f8fafc;
        }
        
        .content-table td {
            padding: 20px;
            color: #555;
            vertical-align: middle;
            border-bottom: 1px solid #f1f1f1;
        }
        
        /* Content Preview Styles */
        .content-preview {
            max-width: 400px;
            max-height: 150px;
            overflow: hidden;
            position: relative;
        }
        
        .content-preview::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            background: linear-gradient(transparent, white);
        }
        
        .full-content {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            padding: 40px;
            overflow-y: auto;
        }
        
        .full-content .content-box {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        
        .close-preview {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #f8f9fa;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .close-preview:hover {
            background: #e9ecef;
            transform: rotate(90deg);
        }
        
        /* Image Styling */
        .content-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 3px solid white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .content-image:hover {
            transform: scale(1.8);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            z-index: 100;
            position: relative;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
            text-decoration: none;
        }
        
        .btn-view {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .btn-edit {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-view:hover {
            background: #1976d2;
            color: white;
        }
        
        .btn-edit:hover {
            background: #f57c00;
            color: white;
        }
        
        /* No Data State */
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-data i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .no-data h4 {
            color: #444;
            margin-bottom: 10px;
        }
        
        /* Quick Actions Bar */
        .quick-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .quick-action-btn {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 500;
            color: #4a5568;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .quick-action-btn:hover {
            border-color: #4361ee;
            color: #4361ee;
            transform: translateY(-2px);
            text-decoration: none;
        }
        
        .quick-action-btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        
        .quick-action-btn.primary:hover {
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        /* Mobile Responsive */
        @media (max-width: 992px) {
            .container-fluid {
                padding: 15px;
            }
            
            .card-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .card-actions {
                width: 100%;
                justify-content: space-between;
            }
        }
        
        @media (max-width: 768px) {
            .table-container {
                margin: 0 -15px;
                padding: 0 15px 20px;
            }
            
            .content-table {
                min-width: 700px;
            }
            
            .action-buttons {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .btn-action {
                width: 40px;
                height: 40px;
            }
            
            .content-image {
                width: 80px;
                height: 80px;
            }
        }
        
        @media (max-width: 576px) {
            .quick-actions {
                flex-direction: column;
            }
            
            .quick-action-btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Table Loading Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .content-table tbody tr {
            animation: fadeIn 0.3s ease-out;
        }
        
        .content-table tbody tr:nth-child(1) { animation-delay: 0.1s; }
        .content-table tbody tr:nth-child(2) { animation-delay: 0.2s; }
        .content-table tbody tr:nth-child(3) { animation-delay: 0.3s; }
        
        /* Content Formatting */
        .title-cell {
            font-weight: 600;
            color: #2c323f;
            max-width: 200px;
        }
        
        .id-cell {
            font-weight: 600;
            color: #4361ee;
            text-align: center;
        }
        
        /* Board Members Link */
        .board-members-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 15px 30px;
            background: #2ecc71;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        
        .board-members-link:hover {
            background: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
            color: white;
            text-decoration: none;
        }
        
        /* Stats Info */
        .stats-info {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .stat-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #4361ee;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            min-width: 150px;
        }
        
        .stat-label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #2c323f;
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
                    <div class="logo-wrapper"><a href="index.html"><img class="img-fluid for-light" src="../../assets/img/logo.png" alt=""><img class="img-fluid for-dark" src="../../assets/img/logo.png" alt=""></a></div>
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
                                <li><a href="profile.php"><i data-feather="user"></i><span>Account</span></a></li>
                                <li><a href="settings.php"><i data-feather="settings"></i><span>Settings</span></a></li>
                                <li><a href="logout.php"><i data-feather="log-in"> </i><span>Logout</span></a></li>
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
                    <div class="logo-icon-wrapper"><a href="dashboard.php"><img class="img-fluid" src="assets/img/logo.png" alt=""></a></div>
                    <?php include('menu.php'); ?>
                </div>
            </div>
            <!-- Page Sidebar Ends-->
            <div class="page-body">
                <div class="container-fluid">        
                    <div class="page-title">
                        <div class="row">
                            <div class="col-sm-6 p-0">
                                <h3>About Us Management</h3>
                                <p class="text-muted mb-0">Manage about page content and images</p>
                            </div>
                            <div class="col-sm-6 p-0">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">
                                        <a href="dashboard.php">
                                            <svg class="stroke-icon">
                                                <use href="assets/svg/icon-sprite.svg#stroke-home"></use>
                                            </svg>
                                            Dashboard
                                        </a>
                                    </li>
                                    <li class="breadcrumb-item">Content</li>
                                    <li class="breadcrumb-item active">About Us</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stats Info -->
                    <?php
                    $totalItems = mysqli_num_rows($query);
                    mysqli_data_seek($query, 0); // Reset pointer
                    ?>
                    <div class="stats-info">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $totalItems; ?></div>
                            <div class="stat-label">Total Sections</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $totalItems; ?></div>
                            <div class="stat-label">Active Items</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">0</div>
                            <div class="stat-label">Draft Items</div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="quick-actions">
                        <a href="board_members.php" class="quick-action-btn primary">
                            <i class="fas fa-users"></i> Manage Board Members
                        </a>
                        <a href="#" class="quick-action-btn" onclick="alert('Export functionality coming soon')">
                            <i class="fas fa-download"></i> Export Data
                        </a>
                        <a href="#" class="quick-action-btn" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </a>
                    </div>
                    
                    <!-- Main Content Card -->
                    <div class="main-content-card">
                        <div class="card-header">
                            <h5 class="card-title"><i class="fas fa-info-circle"></i> About Content Sections</h5>
                            <div class="card-actions">
                                <a href="choose_settings.php" class="btn-primary">
                                    <i class="fas fa-plus"></i> Choose  Us Section
                                </a>
                            </div>
                        </div>
                        
                        <?php if(isset($_GET['msg'])): ?>
                        <div class="alert-message">
                            <i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($_GET['msg']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="table-container">
                            <table class="content-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Content</th>
                                        <th>Image</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($query) > 0): ?>
                                        <?php 
                                        $cnt = 1;
                                        while($row = mysqli_fetch_assoc($query)):
                                            $content = htmlspecialchars($row['content']);
                                            $shortContent = strlen($content) > 150 ? substr($content, 0, 150) . '...' : $content;
                                        ?>
                                        <tr>
                                            <td class="id-cell">#<?php echo $cnt; ?></td>
                                            <td class="title-cell"><?php echo htmlspecialchars($row['title']); ?></td>
                                            <td>
                                                <div class="content-preview" id="preview-<?php echo $cnt; ?>">
                                                    <?php echo $shortContent; ?>
                                                </div>
                                                <div class="full-content" id="full-<?php echo $cnt; ?>">
                                                    <div class="content-box">
                                                        <button class="close-preview" onclick="closeFullContent(<?php echo $cnt; ?>)">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                        <h4><?php echo htmlspecialchars($row['title']); ?></h4>
                                                        <div class="mt-3">
                                                            <?php echo nl2br($content); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if(!empty($row['image'])): ?>
                                                <img src="upload/<?php echo htmlspecialchars($row['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($row['title']); ?>" 
                                                     class="content-image"
                                                     onclick="viewImage(this)">
                                                <?php else: ?>
                                                <span class="text-muted">No Image</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn-action btn-view" 
                                                            onclick="showFullContent(<?php echo $cnt; ?>)"
                                                            title="View Full Content">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <a href="aboutedit.php?id=<?php echo $row['id']; ?>" 
                                                       class="btn-action btn-edit"
                                                       title="Edit Section">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php 
                                        $cnt++;
                                        endwhile; 
                                        ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5">
                                                <div class="no-data">
                                                    <i class="fas fa-info-circle fa-3x"></i>
                                                    <h4>No About Content Found</h4>
                                                    <p>There are no about sections in the system yet.</p>
                                                    <a href="#" class="btn-primary mt-3" onclick="alert('Add new section functionality coming soon')">
                                                        <i class="fas fa-plus"></i> Add Your First Section
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Board Members Link -->
                    <div class="text-center">
                        <a href="board_members.php" class="board-members-link">
                            <i class="fas fa-user-tie"></i> Go to Board Members Management
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- footer start-->
            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-6 p-0 footer-copyright">
                            <p class="mb-0">Copyright &copy; <?php echo date("Y"); ?> Real Estate Admin. All rights reserved.</p>
                        </div>
                        <div class="col-md-6 p-0"></div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap/bootstrap.bundle.min.js"></script>
    <script src="assets/js/icons/feather-icon/feather.min.js"></script>
    <script src="assets/js/icons/feather-icon/feather-icon.js"></script>
    <script src="assets/js/scrollbar/simplebar.js"></script>
    <script src="assets/js/scrollbar/custom.js"></script>
    <script src="assets/js/config.js"></script>
    <script src="assets/js/sidebar-menu.js"></script>
    <script src="assets/js/sidebar-pin.js"></script>
    <script src="assets/js/script.js"></script>
    
    <script>
        // Show full content modal
        function showFullContent(id) {
            const fullContent = document.getElementById('full-' + id);
            if (fullContent) {
                fullContent.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        }
        
        // Close full content modal
        function closeFullContent(id) {
            const fullContent = document.getElementById('full-' + id);
            if (fullContent) {
                fullContent.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }
        
        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('full-content')) {
                e.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
        
        // Escape key to close modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.full-content');
                modals.forEach(modal => {
                    modal.style.display = 'none';
                });
                document.body.style.overflow = 'auto';
            }
        });
        
        // View image in modal
        function viewImage(img) {
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.9);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            `;
            
            const modalImg = document.createElement('img');
            modalImg.src = img.src;
            modalImg.style.cssText = `
                max-width: 90%;
                max-height: 90%;
                object-fit: contain;
                border-radius: 8px;
            `;
            
            const closeBtn = document.createElement('button');
            closeBtn.innerHTML = '<i class="fas fa-times"></i>';
            closeBtn.style.cssText = `
                position: absolute;
                top: 20px;
                right: 20px;
                background: rgba(255,255,255,0.1);
                border: none;
                color: white;
                width: 50px;
                height: 50px;
                border-radius: 50%;
                font-size: 20px;
                cursor: pointer;
                transition: all 0.3s ease;
            `;
            
            closeBtn.onmouseover = function() {
                this.style.background = 'rgba(255,255,255,0.2)';
            };
            
            closeBtn.onmouseout = function() {
                this.style.background = 'rgba(255,255,255,0.1)';
            };
            
            closeBtn.onclick = function() {
                document.body.removeChild(modal);
                document.body.style.overflow = 'auto';
            };
            
            modal.appendChild(modalImg);
            modal.appendChild(closeBtn);
            
            document.body.appendChild(modal);
            document.body.style.overflow = 'hidden';
            
            // Close on background click
            modal.onclick = function(e) {
                if (e.target === modal) {
                    document.body.removeChild(modal);
                    document.body.style.overflow = 'auto';
                }
            };
        }
        
        // Initialize tooltips
        $(function () {
            $('[title]').tooltip();
        });
    </script>
</body>
</html>