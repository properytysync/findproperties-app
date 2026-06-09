<?php
session_start();
require("config.php");
require_once __DIR__ . "/_auth.php";

require_login();

$error = "";
$msg = "";

if (isset($_POST['add'])) {
    // Retrieve and sanitize form inputs
    $title = mysqli_real_escape_string($con, $_POST['title']);
    $content = mysqli_real_escape_string($con, $_POST['pcontent']);
    $ptype = mysqli_real_escape_string($con, $_POST['ptype']);
    $bed = mysqli_real_escape_string($con, $_POST['bed']);
    $balc = mysqli_real_escape_string($con, $_POST['balc']);
    $toilet = mysqli_real_escape_string($con, $_POST['toilet']);
    $stype = mysqli_real_escape_string($con, $_POST['stype']);
    $bath = mysqli_real_escape_string($con, $_POST['bath']);
    $kitc = mysqli_real_escape_string($con, $_POST['kitc']);
    $price = mysqli_real_escape_string($con, $_POST['price']);
    $city = mysqli_real_escape_string($con, $_POST['city']);
    $asize = mysqli_real_escape_string($con, $_POST['asize']);
    $loc = mysqli_real_escape_string($con, $_POST['loc']);
    $state = mysqli_real_escape_string($con, $_POST['state']);
    $status = mysqli_real_escape_string($con, $_POST['status']);
    $uid = mysqli_real_escape_string($con, $_POST['uid']);
    $feature = mysqli_real_escape_string($con, $_POST['feature']);
    $totalfloor = mysqli_real_escape_string($con, $_POST['totalfloor']);

    // Handle Featured Property checkbox
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // Handle file uploads
    $aimage = mysqli_real_escape_string($con, $_FILES['aimage']['name']);
    $aimage1 = mysqli_real_escape_string($con, $_FILES['aimage1']['name']);
    $aimage2 = mysqli_real_escape_string($con, $_FILES['aimage2']['name']);
    $aimage3 = mysqli_real_escape_string($con, $_FILES['aimage3']['name']);
    $aimage4 = mysqli_real_escape_string($con, $_FILES['aimage4']['name']);
    
    $fimage = mysqli_real_escape_string($con, $_FILES['fimage']['name']);
    $fimage1 = mysqli_real_escape_string($con, $_FILES['fimage1']['name']);
    $fimage2 = mysqli_real_escape_string($con, $_FILES['fimage2']['name']);

    // Move uploaded files
    $temp_names = [
        $_FILES['aimage']['tmp_name'],
        $_FILES['aimage1']['tmp_name'],
        $_FILES['aimage2']['tmp_name'],
        $_FILES['aimage3']['tmp_name'],
        $_FILES['aimage4']['tmp_name'],
        $_FILES['fimage']['tmp_name'],
        $_FILES['fimage1']['tmp_name'],
        $_FILES['fimage2']['tmp_name']
    ];

    $file_names = [
        $aimage,
        $aimage1,
        $aimage2,
        $aimage3,
        $aimage4,
        $fimage,
        $fimage1,
        $fimage2
    ];

    foreach ($temp_names as $index => $temp_name) {
        move_uploaded_file($temp_name, "property/" . $file_names[$index]);
    }

    // Prepare and execute SQL query
    $sql = "INSERT INTO property (title, pcontent, type, stype, bedroom, bathroom, balcony, kitchen, toilet, size, price, location, city, state, feature, pimage, pimage1, pimage2, pimage3, pimage4, uid, status, mapimage, topmapimage, groundmapimage, totalfloor, is_featured)
            VALUES ('$title', '$content', '$ptype', '$stype', '$bed', '$bath', '$balc', '$kitc', '$toilet', '$asize', '$price', '$loc', '$city', '$state', '$feature', '$aimage', '$aimage1', '$aimage2', '$aimage3', '$aimage4', '$uid', '$status', '$fimage', '$fimage1', '$fimage2', '$totalfloor', '$is_featured')";

    if (mysqli_query($con, $sql)) {
        $msg = "<p class='alert alert-success'>Property Inserted Successfully</p>";
    } else {
        $error = "<p class='alert alert-warning'>Property Not Inserted. Some Error: " . mysqli_error($con) . "</p>";
    }
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
    <title>Add Property</title>
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
              <div class="toggle-sidebar">
                
              </div>
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
                <div class="col-sm-6 p-0">
                 
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
          <!-- Container-fluid starts-->
          <div class="container-fluid default-dashboard">
<div class="row">
						<div class="col-md-12">
							<div class="card">
								<div class="card-header">
									<h4 class="card-title">Add Property Details</h4>
								</div>
								<form method="post" enctype="multipart/form-data">
								<div class="card-body">
									<h5 class="card-title">Property Detail</h5>
									<?php echo $error; ?>
									<?php echo $msg; ?>
									
										<div class="row">
											<div class="col-xl-12">
												<div class="form-group row">
													<label class="col-lg-2 col-form-label">Title</label>
													<div class="col-lg-9">
														<input type="text" class="form-control" name="title" required placeholder="Enter Title">
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-2 col-form-label">Content</label>
													<div class="col-lg-9">
														<textarea class="tinymce form-control" name="pcontent" rows="10" cols="30"></textarea>
													</div>
												</div>
												
											</div>
											<div class="col-xl-6">
												<div class="form-group row">
    <label class="col-lg-3 col-form-label">Property Type</label>
    <div class="col-lg-9">
        <select class="form-control" required name="ptype">
            <option value="">Select Type</option>
            <option value="apartment">Apartment</option>
            <option value="flat">Flat</option>
            <option value="bunglow">Bungalow</option>
            <option value="duplex">Duplex</option>
            <option value="villa">Villa</option>
            <option value="office">Office</option>
            <option value="land">Land</option>
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
														<input type="text" class="form-control" name="bath" required placeholder="Enter Bathroom (only no 1 to 10)">
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Kitchen</label>
													<div class="col-lg-9">
														<input type="text" class="form-control" name="kitc" required placeholder="Enter Kitchen (only no 1 to 10)">
													</div>
												</div>
												
											</div>   
											<div class="col-xl-6">
												
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Bedroom</label>
													<div class="col-lg-9">
														<input type="text" class="form-control" name="bed" required placeholder="Enter Bedroom  (only no 1 to 10)">
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Balcony</label>
													<div class="col-lg-9">
														<input type="text" class="form-control" name="balc" required placeholder="Enter Balcony  (only no 1 to 10)">
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">toilet</label>
													<div class="col-lg-9">
														<input type="text" class="form-control" name="toilet" required placeholder="Enter toilet  (only no 1 to 10)">
													</div>
												</div>
												
											</div>
										</div>
										<h4 class="card-title">Price & Location</h4>
										<div class="row">
											<div class="col-xl-6">
												
											<div class="form-group row">
													<label class="col-lg-3 col-form-label">Price</label>
													<div class="col-lg-9">
														<input type="number" class="form-control" name="price" required placeholder="Enter Price" min="0" step="1" pattern="\d*">
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">City</label>
													<div class="col-lg-9">
														<input type="text" class="form-control" name="city" required placeholder="Enter City">
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">State</label>
													<div class="col-lg-9">
														<input type="text" class="form-control" name="state" required placeholder="Enter State">
													</div>
												</div>
											</div>
											<div class="col-xl-6">
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Total Floor</label>
													<div class="col-lg-9">
														<select class="form-control" required name="totalfloor">
															<option value="">Select Floor</option>
															<option value="1 Floor">No Floor</option>
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
														<input type="text" class="form-control" name="asize" required placeholder="Enter Area Size (in sqrt)">
													</div>
												</div>
												<div class="form-group row">
													<label class="col-lg-3 col-form-label">Address</label>
													<div class="col-lg-9">
														<input type="text" class="form-control" name="loc" required placeholder="Enter Address">
													</div>
												</div>
												
											</div>
										</div>
										
										<div class="form-group row">
										
										<div class="form-group">
    <label for="is_featured">Mark as Featured Property:</label>
    <input type="checkbox" name="is_featured" id="is_featured" value="1">
</div>

											<label class="col-lg-2 col-form-label">Feature</label>
											<div class="col-lg-9">
											<p class="alert alert-danger">*  Change Content to <b>Yes</b> Or <b>No</b> or Details</p>
											
										<textarea id="feature" class="tinymce form-control" name="feature" rows="10" cols="30">
    <!---feature area start--->
    <div class="col-md-4">
        <ul>
            <li class="mb-3"><span class="text-secondary font-weight-bold">Property Age : </span>10 Years</li>
            <li class="mb-3"><span class="text-secondary font-weight-bold">Swimming Pool : </span>Yes</li>
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
    <!---feature area end--->
</textarea>

											</div>
										</div>
												
										<h4 class="card-title">Image & Status</h4>
<div class="row">
    <div class="col-xl-6">
        <div class="form-group row">
            <label class="col-lg-3 col-form-label">Image</label>
            <div class="col-lg-9">
                <input class="form-control" name="aimage" type="file">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-lg-3 col-form-label">Image 2</label>
            <div class="col-lg-9">
                <input class="form-control" name="aimage2" type="file">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-lg-3 col-form-label">Image 4</label>
            <div class="col-lg-9">
                <input class="form-control" name="aimage4" type="file">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-lg-3 col-form-label">Status</label>
            <div class="col-lg-9">
                <select class="form-control" required name="status">
                    <option value="">Select Status</option>
                    <option value="available">Available</option>
                    <option value="sold out">Sold Out</option>
                </select>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-lg-3 col-form-label">Bedroom</label>
            <div class="col-lg-9">
                <input class="form-control" name="fimage1" type="file">
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="form-group row">
            <label class="col-lg-3 col-form-label">Image 1</label>
            <div class="col-lg-9">
                <input class="form-control" name="aimage1" type="file">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-lg-3 col-form-label">Image 3</label>
            <div class="col-lg-9">
                <input class="form-control" name="aimage3" type="file">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-lg-3 col-form-label">Uid</label>
            <div class="col-lg-9">
                <input type="text" class="form-control" name="uid" required placeholder="Enter User Id (only number)">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-lg-3 col-form-label">Bathroom</label>
            <div class="col-lg-9">
                <input class="form-control" name="fimage" type="file">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-lg-3 col-form-label">Kitchen Image</label>
            <div class="col-lg-9">
                <input class="form-control" name="fimage2" type="file">
            </div>
        </div>
    </div>
</div>

										
											<input type="submit" value="Submit" class="btn btn-primary"name="add" style="margin-left:200px;">
										
								</div>
								</form>
							</div>
						</div>
					</div>
          </div>
          <!-- Container-fluid Ends-->
        </div>
        <!-- footer start-->
        <footer class="footer">
          <div class="container-fluid">
            <div class="row">
              <div class="col-md-6 p-0 footer-copyright">
    <p class="mb-0">Copyright &copy; <?php echo date("Y"); ?> Propertysync Website Admin. All rights reserved.</p>
</div>

              <div class="col-md-6 p-0">
               
              </div>
            </div>
          </div>
        </footer>
      </div>
    </div>
    <script>
        
// Get references to the Property Type dropdown and TinyMCE editor
const propertyTypeDropdown = document.querySelector('[name="ptype"]');

// Add an event listener to the dropdown for property type changes
propertyTypeDropdown.addEventListener('change', function () {
    const selectedType = this.value.toLowerCase(); // Get the selected property type

    let updatedContent = '';

    if (selectedType === 'land') {
        // Content for land
        updatedContent = `
        <!---feature area start--->
        <div class="col-md-4">
            <ul>
                <li class="mb-3"><span class="text-secondary font-weight-bold">Access Road : </span>Yes</li>
                <li class="mb-3"><span class="text-secondary font-weight-bold">Electricity : </span>Yes</li>
                <li class="mb-3"><span class="text-secondary font-weight-bold">Water Supply : </span>Yes</li>
                <li class="mb-3"><span class="text-secondary font-weight-bold">Fenced : </span>Yes</li>
            </ul>
        </div>
        <div class="col-md-4">
            <ul>
                <li class="mb-3"><span class="text-secondary font-weight-bold">Soil Type : </span>Clay</li>
                <li class="mb-3"><span class="text-secondary font-weight-bold">Title Document : </span>Yes</li>
                <li class="mb-3"><span class="text-secondary font-weight-bold">Topography : </span>Flat</li>
            </ul>
        </div>
        <div class="col-md-4">
            <ul>
                <li class="mb-3"><span class="text-secondary font-weight-bold">Dining Capacity : </span>No</li>
                <li class="mb-3"><span class="text-secondary font-weight-bold">GYM : </span>No</li>
                <li class="mb-3"><span class="text-secondary font-weight-bold">Swimming Pool : </span>No</li>
            </ul>
        </div>
        <!---feature area end--->
        `;
    } else {
        // Default content for apartments
        updatedContent = `
        <!---feature area start--->
        <div class="col-md-4">
            <ul>
                <li class="mb-3"><span class="text-secondary font-weight-bold">Property Age : </span>10 Years</li>
                <li class="mb-3"><span class="text-secondary font-weight-bold">Swimming Pool : </span>Yes</li>
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
        <!---feature area end--->
        `;
    }

    // Update the TinyMCE editor content dynamically
    tinymce.get('feature').setContent(updatedContent);
});


    </script>
    
    <script>
        
        // Get references to Property Type dropdown and Feature textarea
const propertyTypeDropdown = document.querySelector('[name="ptype"]');
const featureTextarea = document.querySelector('[name="feature"]');

// Add an event listener to the Property Type dropdown
propertyTypeDropdown.addEventListener('change', function () {
    const selectedType = this.value.toLowerCase(); // Get the selected property type

    if (selectedType === 'land') {
        // Update the Feature textarea for "Land"
        featureTextarea.value = `
        <!---feature area start--->
        <div class="col-md-4">
            <ul>
                <li class="mb-3"><span class="text-secondary font-weight-bold">Access Road : </span>Yes</li>
                <li class="mb-3"><span class="text-secondary font-weight-bold">Electricity : </span>Yes</li>
                <li class="mb-3"><span class="text-secondary font-weight-bold">Water Supply : </span>Yes</li>
                <li class="mb-3"><span class="text-secondary font-weight-bold">Fenced : </span>Yes</li>
            </ul>
        </div>
        <div class="col-md-4">
            <ul>
                <li class="mb-3"><span class="text-secondary font-weight-bold">Soil Type : </span>Clay</li>
                <li class="mb-3"><span class="text-secondary font-weight-bold">Title Document : </span>Yes</li>
                <li class="mb-3"><span class="text-secondary font-weight-bold">Topography : </span>Flat</li>
            </ul>
        </div>
        <!---feature area end--->
        `.trim();
    } else {
        // Default content for other property types
        featureTextarea.value = `
        <!---feature area start--->
        <div class="col-md-4">
            <ul>
                <li class="mb-3"><span class="text-secondary font-weight-bold">Property Age : </span>10 Years</li>
                <li class="mb-3"><span class="text-secondary font-weight-bold">Swimming Pool : </span>Yes</li>
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
        <!---feature area end--->
        `.trim();
    }
});

    </script>
    
        <script>
        
        // Get references to form elements
const propertyTypeSelect = document.querySelector('[name="ptype"]');
const featuresBox = document.querySelector('#features'); // The textarea for features
const fieldsToDisable = ['bath', 'kitc', 'bed', 'balc', 'toilet', 'totalfloor']; // Field names to disable for "Land"

// Add event listener to Property Type dropdown
propertyTypeSelect.addEventListener('change', function () {
    const selectedType = this.value.toLowerCase(); // Get the selected property type (e.g., "land")

    if (selectedType === 'land') {
        // Disable specific fields and clear their values
        fieldsToDisable.forEach(fieldName => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.disabled = true;
                field.removeAttribute('required'); // Make it non-required
                field.value = ''; // Clear value
            }
        });

        // Update features box content for "Land"
        featuresBox.value = `
        Property Features for Land:
        - Size: Enter the size in square meters or acres
        - Access Road: Yes/No
        - Electricity: Yes/No
        - Water Supply: Yes/No
        - Type of Land: Residential/Commercial/Agricultural/Other
        `;
    } else {
        // Enable all fields and make them required for other property types
        fieldsToDisable.forEach(fieldName => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.disabled = false;
                field.setAttribute('required', 'required'); // Make it required again
            }
        });

        // Update features box content for other property types
        featuresBox.value = `
        Property Features:
        - Property Age: 10 Years
        - Swimming Pool: Yes/No
        - Parking: Yes/No
        - GYM: Yes/No
        - Type: ${selectedType.charAt(0).toUpperCase() + selectedType.slice(1)}
        - Security: Yes/No
        - Dining Capacity: 10 People
        - Water Supply: Yes/No
        `;
    }
});

    </script>
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
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
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
    <script src="assets/js/jquery-3.2.1.min.js"></script>
		<script src="assets/plugins/tinymce/tinymce.min.js"></script>
		<script src="assets/plugins/tinymce/init-tinymce.min.js"></script>
		<!-- Bootstrap Core JS -->
        <script src="assets/js/popper.min.js"></script>
        <script src="assets/js/bootstrap.min.js"></script>
		
		<!-- Slimscroll JS -->
        <script src="assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
		
		<!-- Custom JS -->
		<script  src="assets/js/script.js"></script>
  </body>
</html>