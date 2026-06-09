<?php
session_start();
require("config.php");

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Retrieve the pid to identify the property being edited
    $pid = isset($_POST['pid']) ? $_POST['pid'] : null;

    // Initialize error and message variables
    $error = "";
    $msg = "";

    // Retrieve form inputs, handle undefined keys with a default value (e.g., null)
    $title = isset($_POST['title']) ? mysqli_real_escape_string($con, $_POST['title']) : null;
    $pcontent = isset($_POST['pcontent']) ? mysqli_real_escape_string($con, $_POST['pcontent']) : null;
    $type = isset($_POST['type']) ? mysqli_real_escape_string($con, $_POST['type']) : null;
    $stype = isset($_POST['stype']) ? mysqli_real_escape_string($con, $_POST['stype']) : null;
    $bedroom = isset($_POST['bedroom']) ? mysqli_real_escape_string($con, $_POST['bedroom']) : 0;
    $bathroom = isset($_POST['bathroom']) ? mysqli_real_escape_string($con, $_POST['bathroom']) : 0;
    $balcony = isset($_POST['balcony']) ? mysqli_real_escape_string($con, $_POST['balcony']) : 0;
    $kitchen = isset($_POST['kitchen']) ? mysqli_real_escape_string($con, $_POST['kitchen']) : 0;
    $toilet = isset($_POST['toilet']) ? mysqli_real_escape_string($con, $_POST['toilet']) : 0;
    $size = isset($_POST['size']) ? mysqli_real_escape_string($con, $_POST['size']) : 0;
    $price = isset($_POST['price']) ? mysqli_real_escape_string($con, $_POST['price']) : 0;
    $location = isset($_POST['location']) ? mysqli_real_escape_string($con, $_POST['location']) : null;
    $city = isset($_POST['city']) ? mysqli_real_escape_string($con, $_POST['city']) : null;
    $state = isset($_POST['state']) ? mysqli_real_escape_string($con, $_POST['state']) : null;
    $feature = isset($_POST['feature']) ? mysqli_real_escape_string($con, $_POST['feature']) : null;
    $pimage = isset($_POST['pimage']) ? mysqli_real_escape_string($con, $_POST['pimage']) : null;
    $pimage1 = isset($_POST['pimage1']) ? mysqli_real_escape_string($con, $_POST['pimage1']) : null;
    $pimage2 = isset($_POST['pimage2']) ? mysqli_real_escape_string($con, $_POST['pimage2']) : null;
    $pimage3 = isset($_POST['pimage3']) ? mysqli_real_escape_string($con, $_POST['pimage3']) : null;
    $pimage4 = isset($_POST['pimage4']) ? mysqli_real_escape_string($con, $_POST['pimage4']) : null;
    $status = isset($_POST['status']) ? mysqli_real_escape_string($con, $_POST['status']) : null;
    $mapimage = isset($_POST['mapimage']) ? mysqli_real_escape_string($con, $_POST['mapimage']) : null;
    $topmapimage = isset($_POST['topmapimage']) ? mysqli_real_escape_string($con, $_POST['topmapimage']) : null;
    $groundmapimage = isset($_POST['groundmapimage']) ? mysqli_real_escape_string($con, $_POST['groundmapimage']) : null;
    $totalfloor = isset($_POST['totalfloor']) ? mysqli_real_escape_string($con, $_POST['totalfloor']) : null;

    // Prepare the SQL query to update the property record
    if ($pid) {
        $stmt = $con->prepare("
            UPDATE property 
            SET 
                title = ?, 
                pcontent = ?, 
                type = ?, 
                stype = ?, 
                bedroom = ?, 
                bathroom = ?, 
                balcony = ?, 
                kitchen = ?, 
                toilet = ?, 
                size = ?, 
                price = ?, 
                location = ?, 
                city = ?, 
                state = ?, 
                feature = ?, 
                pimage = ?, 
                pimage1 = ?, 
                pimage2 = ?, 
                pimage3 = ?, 
                pimage4 = ?, 
                status = ?, 
                mapimage = ?, 
                topmapimage = ?, 
                groundmapimage = ?, 
                totalfloor = ? 
            WHERE pid = ?
        ");

        // Bind the parameters
        $stmt->bind_param("ssssiisiisissssisssssisssi", 
            $title, 
            $pcontent, 
            $type, 
            $stype, 
            $bedroom, 
            $bathroom, 
            $balcony, 
            $kitchen, 
            $toilet, 
            $size, 
            $price, 
            $location, 
            $city, 
            $state, 
            $feature, 
            $pimage, 
            $pimage1, 
            $pimage2, 
            $pimage3, 
            $pimage4, 
            $status, 
            $mapimage, 
            $topmapimage, 
            $groundmapimage, 
            $totalfloor, 
            $pid
        );

        // Execute the query and check for errors
        if ($stmt->execute()) {
            $msg = "<p class='alert alert-success'>Property updated successfully.</p>";
        } else {
            $error = "<p class='alert alert-warning'>Error: " . $stmt->error . "</p>";
        }

        // Close the statement
        $stmt->close();
    } else {
        $error = "<p class='alert alert-warning'>Error: Property ID is missing.</p>";
    }
    
    // Output messages
    if (!empty($msg)) {
        echo $msg;
    }
    if (!empty($error)) {
        echo $error;
    }
}

// Close the database connection
if (isset($con) && $con) {
    $con->close();
}
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
    <title>Admin - Real Estate Web App</title>
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

        <script>
        function updateFormVisibility() {
            var tenantType = document.getElementById('tenant_type').value;
            var leaseDates = document.getElementById('lease_dates');
            var purchaseDate = document.getElementById('purchase_date_field');

            if (tenantType == 'renter') {
                leaseDates.style.display = 'block';
                document.getElementById('lease_start').required = true;
                document.getElementById('lease_end').required = true;
                purchaseDate.style.display = 'none';
                document.getElementById('purchase_date').required = false;
            } else {
                leaseDates.style.display = 'none';
                document.getElementById('lease_start').required = false;
                document.getElementById('lease_end').required = false;
                purchaseDate.style.display = 'block';
                document.getElementById('purchase_date').required = true;
            }
        }
    </script>

</head>
<body onload="updateFormVisibility()">

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


              <li class="onhover-dropdown">
                
               
              </li>
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
                  <li><a href="edit-profile.html"><i data-feather="settings"></i><span>Settings</span></a></li>
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
            <div class="logo-wrapper"><a href="dashboard.php"><img class="img-fluid" src="../../assets/img/logo.png" alt=""></a>
              <div class="toggle-sidebar">
                <svg class="sidebar-toggle"> 
                  <use href="../../assets/svg/icon-sprite.svg#toggle-icon"></use>
                </svg>
              </div>
            </div>
            <div class="logo-icon-wrapper"><a href="dasboard.php"><img class="img-fluid" src="assets/img/logo.png" alt=""></a></div>
            <nav class="sidebar-main">
              <div class="left-arrow" id="left-arrow"><i data-feather="arrow-left"></i></div>
              <div id="sidebar-menu">
                <ul class="sidebar-links" id="simple-bar">
                  <li class="back-btn"><a href="dashboard.php"><img class="img-fluid" src="../../assets/img/logo.png" alt=""></a>
                    <div class="mobile-back text-end"><span>Back</span><i class="fa fa-angle-right ps-2" aria-hidden="true"></i></div>
                  </li>
                  <li class="pin-title sidebar-main-title">
                    <div> 
                      <h6>Pinned</h6>
                    </div>
                  </li>
                  <li class="sidebar-main-title">
                    <div>
                      <h6 class="lan-1">General</h6>
                    </div>
                  </li>
                 <li class="sidebar-list"><i class="fa fa-thumb-tack"></i><a class="sidebar-link sidebar-title" href="dashboard.php">
                      
                      <svg class="fill-icon">
                        <use href="assets/svg/icon-sprite.svg#fill-home"></use>
                      </svg><span class="lan-3">Dashboard</span></a>
                   
                  </li>
                  <li class="sidebar-list"><i class="fa fa-thumb-tack"></i><a class="sidebar-link sidebar-title" href="#">
                      <svg class="stroke-icon">
                        <use href="assets/svg/icon-sprite.svg#stroke-widget"></use>
                      </svg>
                      <svg class="fill-icon">
                        <use href="assets/svg/icon-sprite.svg#fill-widget"></use>
                      </svg><span class="lan">Users</span></a>
                    <ul class="sidebar-submenu">
                      <li><a href="adminlist.php">Admin</a></li>
                      <li><a href="agents_list.php">Agents</a></li>
                    </ul>
                   
                  </li>
                  <li class="sidebar-list"><i class="fa fa-thumb-tack"></i><a class="sidebar-link sidebar-title" href="#">
                      <svg class="stroke-icon">
                        <use href="assets/svg/icon-sprite.svg#stroke-layout"></use>
                      </svg>
                      <svg class="fill-icon">
                        <use href="assets/svg/icon-sprite.svg#fill-layout"></use>
                      </svg><span class="lan-9977">Property Listings</span></a>
                    <ul class="sidebar-submenu">
                      <li><a href="propertyadd.php">Add Property</a></li>
                      <li><a href="propertyview.php">View Property</a></li>
                    </ul>
                  </li>
                  <li class="sidebar-list"><i class="fa fa-thumb-tack"></i><a class="sidebar-link sidebar-title" href="#">
                      <svg class="stroke-icon">
                        <use href="assets/svg/icon-sprite.svg#stroke-layout"></use>
                      </svg>
                      <svg class="fill-icon">
                        <use href="assets/svg/icon-sprite.svg#fill-layout"></use>
                      </svg><span class="lan-9977">Tenant</span></a>
                    <ul class="sidebar-submenu">
                      <li><a href="tenant_form.php">Add Tenants</a></li>
                      <li><a href="display_tenants.php">View Tenants</a></li>
                    </ul>
                  </li>

                  <li class="sidebar-main-title">
                    <div>
                      <h6 class="">Page Update</h6>
                    </div>
                  </li>

                  <li class="sidebar-list"><i class="fa fa-thumb-tack"></i><a class="sidebar-link sidebar-title link-nav" href="aboutview.php">
                      <svg class="stroke-icon">
                        <use href="assets/svg/icon-sprite.svg#stroke-file"></use>
                      </svg>
                      <svg class="fill-icon">
                        <use href="assets/svg/icon-sprite.svg#fill-file"></use>
                      </svg><span>About Us</span></a></li>
                  <li class="sidebar-list"><i class="fa fa-thumb-tack">        </i><a class="sidebar-link sidebar-title link-nav" href="contactview.php">
                      <svg class="stroke-icon">
                        <use href="assets/svg/icon-sprite.svg#stroke-board"></use>
                      </svg>
                      <svg class="fill-icon">
                        <use href="assets/svg/icon-sprite.svg#fill-board"></use>
                      </svg><span>Contact Us</span></a></li>
                      
                      <li class="sidebar-main-title">
                    <div>
                      <h6 class="">Logout</h6>
                    </div>
                  </li>
                </ul>
              </div>
              <div class="right-arrow" id="right-arrow"><i data-feather="arrow-right"></i></div>
            </nav>
          </div>
        </div>
                <!-- Page Sidebar Ends-->
        <div class="page-body">
          <div class="container-fluid">        
            <div class="page-title">
              <div class="row">
                <div class="col-sm-6 p-0">
                  <h3>Welcome Admin </h3>
                </div>
                <div class="col-sm-6 p-0">
                  <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.html">
                        <svg class="stroke-icon">
                          <use href="assets/svg/icon-sprite.svg#stroke-home"></use>
                        </svg></a></li>
                    <li class="breadcrumb-item">Dashboard</li>
                    <li class="breadcrumb-item active">Add Property</li>
                  </ol>
                </div>
              </div>
            </div>
          </div>
					<!-- /Page Header -->
					
					<div class="row">
						<div class="col-md-12">
							<div class="card">
								<div class="card-header">
									<h4 class="card-title">Update Property Details</h4>
									<?php echo $error; ?>
									<?php echo $msg; ?>
								</div>
								<form method="post" enctype="multipart/form-data">
								
								<?php
									
									$pid=$_REQUEST['id'];
									$query=mysqli_query($con,"select * from property where pid='$pid'");
									while($row=mysqli_fetch_row($query))
									{
								?>
												
								<div class="card-body">
									<h5 class="card-title">Property Detail</h5>
										<div class="row">
											<div class="col-xl-12">
												<div class="form-group row">
													<label class="col-lg-2 col-form-label">Title</label>
													<div class="col-lg-9">
														<input type="text" class="form-control" name="title" required value="<?php echo $row['1']; ?>">
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-2 col-form-label">Content</label>
													<div class="col-lg-9">
														<textarea class="tinymce form-control" name="content" rows="10" cols="30"><?php echo $row['2']; ?></textarea>
													</div>
												</div>
												
											</div>
											<div class="col-xl-6">
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Property Type</label>
													<div class="col-lg-9">
														<select class="form-control" required name="ptype">
															<option value="">Select Type</option>
															<option value="appartment">Apartment</option>
															<option value="flat">Flat</option>
															<option value="bunglow">Bunglow</option>
															<option value="duplex">Duplex</option>
															<option value="villa">Villa</option>
															<option value="office">Office</option>
														</select>
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Selling Type</label>
													<div class="col-lg-9">
														<select class="form-control" required name="stype">
															<option value="">Select Status</option>
															<option value="rent">Rent</option>
															<option value="sale">Sale</option>
																<option value="shortlet">Shortlet</option>
														</select>
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Bathroom</label>
													<div class="col-lg-9">
														<input type="text" class="form-control" name="bath" required value="<?php echo $row['7']; ?>">
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Kitchen</label>
													<div class="col-lg-9">
														<input type="text" class="form-control" name="kitc" required value="<?php echo $row['7']; ?>">
													</div>
												</div>
												
											</div>   
											<div class="col-xl-6">
											
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Bedroom</label>
													<div class="col-lg-9">
														<input type="text" class="form-control" name="bed" required value="<?php echo $row['6']; ?>">
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Balcony</label>
													<div class="col-lg-9">
														<input type="text" class="form-control" name="balc" required value="<?php echo $row['7']; ?>">
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Hall</label>
													<div class="col-lg-9">
														<input type="text" class="form-control" name="hall" required value="<?php echo $row['9']; ?>">
													</div>
												</div>
												
											</div>
										</div>
										<h4 class="card-title">Price & Location</h4>
										<div class="row">
											<div class="col-xl-6">
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Floor</label>
													<div class="col-lg-9">
														<select class="form-control" required name="floor">
															<option value="">Select Floor</option>
															<option value="1st Floor">1st Floor</option>
															<option value="2nd Floor">2nd Floor</option>
															<option value="3rd Floor">3rd Floor</option>
															<option value="4th Floor">4th Floor</option>
															<option value="5th Floor">5th Floor</option>
														</select>
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Price</label>
													<div class="col-lg-9">
														<input type="text" class="form-control" name="price" required value="<?php echo $row['11']; ?>">
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">City</label>
													<div class="col-lg-9">
														<input type="text" class="form-control" name="city" required value="<?php echo $row['13']; ?>">
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">State</label>
													<div class="col-lg-9">
														<input type="text" class="form-control" name="state" required value="<?php echo $row['14']; ?>">
													</div>
												</div>
											</div>
											<div class="col-xl-6">
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Total Floor</label>
													<div class="col-lg-9">
														<select class="form-control" required name="totalfl">
															<option value="">Select Floor</option>
															<option value="1 Floor">1 Floor</option>
															<option value="2 Floor">2 Floor</option>
															<option value="3 Floor">3 Floor</option>
															<option value="4 Floor">4 Floor</option>
															<option value="5 Floor">5 Floor</option>
															<option value="6 Floor">6 Floor</option>
															<option value="7 Floor">7 Floor</option>
															<option value="8 Floor">8 Floor</option>
															<option value="9 Floor">9 Floor</option>
															<option value="10 Floor">10 Floor</option>
															<option value="11 Floor">11 Floor</option>
															<option value="12 Floor">12 Floor</option>
															<option value="13 Floor">13 Floor</option>
															<option value="14 Floor">14 Floor</option>
															<option value="15 Floor">15 Floor</option>
														</select>
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Area Size</label>
													<div class="col-lg-9">
														<input type="text" class="form-control" name="asize" required value="<?php echo $row['12']; ?>">
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Address</label>
													<div class="col-lg-9">
														<input type="text" class="form-control" name="loc" required value="<?php echo $row['14']; ?>">
													</div>
												</div>
												
											</div>
										</div>
										
											<div class="form-group row">
											<label class="col-lg-2 col-form-label">Feature</label>
											<div class="col-lg-9">
											<p class="alert alert-danger">*  Change Content to <b>Yes</b> Or <b>No</b> or Details</p>
											
											<textarea class="tinymce form-control" name="feature" rows="10" cols="30">
												<!---feature area start--->
												<div class="col-md-4">
														<ul>
														<li class="mb-3"><span class="text-secondary font-weight-bold">Property Age : </span>10 Years</li>
														<li class="mb-3"><span class="text-secondary font-weight-bold">Swiming Pool : </span>Yes</li>
														<li class="mb-3"><span class="text-secondary font-weight-bold">Parking : </span>Yes</li>
														<li class="mb-3"><span class="text-secondary font-weight-bold">GYM : </span>Yes</li>
														</ul>
													</div>
													<div class="col-md-4">
														<ul>
														<li class="mb-3"><span class="text-secondary font-weight-bold">Type : </span>Apartment</li>
														<li class="mb-3"><span class="text-secondary font-weight-bold">Security : </span>Yes</li>
														<li class="mb-3"><span class="text-secondary font-weight-bold">Dining Capacity : </span>10 People</li>
														
														
														</ul>
													</div>
													<div class="col-md-4">
														<ul>
														
														<li class="mb-3"><span class="text-secondary font-weight-bold">Water Supply : </span>Yes</li>
														</ul>
													</div>
												<!---feature area end---->
											</textarea>
											</div>
										</div>
												
										<h4 class="card-title">Image & Status</h4>
										<div class="row">
											<div class="col-xl-6">
												
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Image</label>
													<div class="col-lg-9">
														<input class="form-control" name="aimage" type="file" required="">
														<img src="property/<?php echo $row['17'];?>" alt="pimage" height="150" width="180">
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Image 2</label>
													<div class="col-lg-9">
														<input class="form-control" name="aimage2" type="file" required="">
														<img src="property/<?php echo $row['18'];?>" alt="pimage" height="150" width="180">
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Image 4</label>
													<div class="col-lg-9">
														<input class="form-control" name="aimage4" type="file" required="">
														<img src="property/<?php echo $row['19'];?>" alt="pimage" height="150" width="180">
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Status</label>
													<div class="col-lg-9">
														<select class="form-control"  required name="status">
															<option value="">Select Status</option>
															<option value="available">Available</option>
															<option value="sold out">Sold Out</option>
														</select>
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Basement Floor Plan Image</label>
													<div class="col-lg-9">
														<input class="form-control" name="fimage1" type="file">
														<img src="property/<?php echo $row['24'];?>" alt="pimage" height="150" width="180">
													</div>
												</div>
											</div>
											<div class="col-xl-6">
												
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Image 1</label>
													<div class="col-lg-9">
														<input class="form-control" name="aimage1" type="file" required="">
														<img src="property/<?php echo $row['25'];?>" alt="pimage" height="150" width="180">
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">image 3</label>
													<div class="col-lg-9">
														<input class="form-control" name="aimage3" type="file" required="">
														<img src="property/<?php echo $row['26'];?>" alt="pimage" height="150" width="180">
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Uid</label>
													<div class="col-lg-9">
														<input type="text" class="form-control" name="uid" required value="<?php echo $row['22']; ?>">
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Floor Plan Image</label>
													<div class="col-lg-9">
														<input class="form-control" name="fimage" type="file">
														<img src="property/<?php echo $row['23'];?>" alt="pimage" height="150" width="180">
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Ground Floor Plan Image</label>
													<div class="col-lg-9">
														<input class="form-control" name="fimage2" type="file">
														<img src="property/<?php echo $row['27'];?>" alt="pimage" height="150" width="180">
													</div>
												</div>
											</div>
										</div>

										
											<input type="submit" value="Submit" class="btn btn-primary"name="add" style="margin-left:200px;">
										
									</div>
								</form>
								
								<?php
									} 
								?>
												
							</div>
						</div>
					</div>
				
				</div>			
			</div>
			<!-- /Main Wrapper -->

 <!-- footer start-->
        <footer class="footer">
          <div class="container-fluid">
            <div class="row">
              <div class="col-md-6 p-0 footer-copyright">
                <p class="mb-0">Copyright 2024 © Real Estate Web App.</p>
              </div>
              <div class="col-md-6 p-0">
                <p class="heart mb-0">Hand crafted by Charles Uche
                  
                </p>
              </div>
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
    <script src="assets/js/chart/morris-chart/morris.js"> </script>
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
    <script src="../../assets/js/jquery-3.2.1.min.js"></script>
		<script src="../../assets/plugins/tinymce/tinymce.min.js"></script>
		<script src="../../assets/plugins/tinymce/init-tinymce.min.js"></script>
		<!-- Bootstrap Core JS -->
        <script src="../../assets/js/popper.min.js"></script>
        <script src="../../assets/js/bootstrap.min.js"></script>
		
		<!-- Slimscroll JS -->
        <script src="../../assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
		
		<!-- Custom JS -->
		<script  src="../../assets/js/script.js"></script>
  </body>
</html>