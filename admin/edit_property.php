<?php
// Include the database connection file
include 'config.php';

// Fetch the property ID from the query string
$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;

// Fetch property details from the database
$query = mysqli_query($conn, "SELECT * FROM property WHERE pid = '$pid'");

if (!$query) {
    die("Query failed: " . mysqli_error($conn));
}

if (mysqli_num_rows($query) > 0) {
    $property = mysqli_fetch_assoc($query);
} else {
    die("Property not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Property</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">
    <style>
        body {
            background-color: #f5f7fb;
            font-family: 'Outfit', sans-serif;
        }
        .container {
            max-width: 1200px;
            margin: 40px auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #1a237e;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
        }
        .form-section {
            margin-bottom: 30px;
        }
        .form-section h4 {
            color: #1a237e;
            font-size: 1.25rem;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
        }
        .form-group label {
            font-weight: 500;
            color: #333;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 10px;
            transition: border-color 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #1a237e;
            box-shadow: 0 0 5px rgba(26, 35, 126, 0.2);
        }
        .form-control[readonly] {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
        .img-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .img-preview img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        .btn-primary, .btn-secondary {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
        }
        .btn-primary {
            background-color: #1a237e;
            border-color: #1a237e;
        }
        .btn-primary:hover {
            background-color: #3949ab;
            border-color: #3949ab;
        }
        .btn-secondary {
            margin-bottom: 20px;
        }
        .form-check {
            margin-top: 10px;
        }
        .alert-info {
            border-radius: 8px;
        }
        .row.g-3 {
            margin-bottom: 20px;
        }
        textarea.form-control {
            min-height: 150px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Property</h2>
        <a href="propertyview.php" class="btn btn-secondary">Back to Property View</a>

        <form action="update_property.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="pid" value="<?php echo htmlspecialchars($property['pid']); ?>" />

            <!-- Property Details Section -->
            <div class="form-section">
                <h4>Property Details</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($property['title']); ?>" required />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="type">Property Type</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="">Select Type</option>
                                <option value="apartment" <?php echo $property['type'] === 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                                <option value="flat" <?php echo $property['type'] === 'flat' ? 'selected' : ''; ?>>Flat</option>
                                <option value="bunglow" <?php echo $property['type'] === 'bunglow' ? 'selected' : ''; ?>>Bungalow</option>
                                <option value="duplex" <?php echo $property['type'] === 'duplex' ? 'selected' : ''; ?>>Duplex</option>
                                <option value="villa" <?php echo $property['type'] === 'villa' ? 'selected' : ''; ?>>Villa</option>
                                <option value="office" <?php echo $property['type'] === 'office' ? 'selected' : ''; ?>>Office</option>
                                <option value="land" <?php echo $property['type'] === 'land' ? 'selected' : ''; ?>>Land</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="pcontent">Description</label>
                            <textarea class="form-control" id="pcontent" name="pcontent" required><?php echo htmlspecialchars($property['pcontent']); ?></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="stype">Selling Type</label>
                            <select class="form-select" id="stype" name="stype" required>
                                <option value="">Select Status</option>
                                <option value="rent" <?php echo $property['stype'] === 'rent' ? 'selected' : ''; ?>>Rent</option>
                                <option value="sale" <?php echo $property['stype'] === 'sale' ? 'selected' : ''; ?>>Sale</option>
                                <option value="shortlet" <?php echo $property['stype'] === 'shortlet' ? 'selected' : ''; ?>>Shortlet</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="available" <?php echo $property['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="sold out" <?php echo $property['status'] === 'sold out' ? 'selected' : ''; ?>>Sold Out</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="bedroom">Bedrooms</label>
                            <input type="number" class="form-control" id="bedroom" name="bedroom" value="<?php echo htmlspecialchars($property['bedroom']); ?>" min="0" max="10" required />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="bathroom">Bathrooms</label>
                            <input type="number" class="form-control" id="bathroom" name="bathroom" value="<?php echo htmlspecialchars($property['bathroom']); ?>" min="0" max="10" required />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="balcony">Balconies</label>
                            <input type="number" class="form-control" id="balcony" name="balcony" value="<?php echo htmlspecialchars($property['balcony']); ?>" min="0" max="10" required />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="kitchen">Kitchens</label>
                            <input type="number" class="form-control" id="kitchen" name="kitchen" value="<?php echo htmlspecialchars($property['kitchen']); ?>" min="0" max="10" required />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="toilet">Toilets</label>
                            <input type="number" class="form-control" id="toilet" name="toilet" value="<?php echo htmlspecialchars($property['toilet']); ?>" min="0" max="10" required />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="size">Area Size (sqft)</label>
                            <input type="number" class="form-control" id="size" name="size" value="<?php echo htmlspecialchars($property['size']); ?>" min="0" required />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Price & Location Section -->
            <div class="form-section">
                <h4>Price & Location</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="price">Price</label>
                            <input type="number" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($property['price']); ?>" min="0" step="1" required />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($property['location']); ?>" required />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($property['city']); ?>" required />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="state">State</label>
                            <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlspecialchars($property['state']); ?>" required />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="totalfloor">Total Floors</label>
                            <select class="form-select" id="totalfloor" name="totalfloor" required>
                                <option value="">Select Floor</option>
                                <option value="No Floor" <?php echo $property['totalfloor'] === 'No Floor' ? 'selected' : ''; ?>>No Floor</option>
                                <option value="1 Floor" <?php echo $property['totalfloor'] === '1 Floor' ? 'selected' : ''; ?>>1 Floor</option>
                                <option value="2 Floor" <?php echo $property['totalfloor'] === '2 Floor' ? 'selected' : ''; ?>>2 Floor</option>
                                <option value="3 Floor" <?php echo $property['totalfloor'] === '3 Floor' ? 'selected' : ''; ?>>3 Floor</option>
                                <option value="4 Floor" <?php echo $property['totalfloor'] === '4 Floor' ? 'selected' : ''; ?>>4 Floor</option>
                                <option value="5 Floor" <?php echo $property['totalfloor'] === '5 Floor' ? 'selected' : ''; ?>>5 Floor</option>
                                <option value="6 Floor" <?php echo $property['totalfloor'] === '6 Floor' ? 'selected' : ''; ?>>6 Floor</option>
                                <option value="7 Floor" <?php echo $property['totalfloor'] === '7 Floor' ? 'selected' : ''; ?>>7 Floor</option>
                                <option value="8 Floor" <?php echo $property['totalfloor'] === '8 Floor' ? 'selected' : ''; ?>>8 Floor</option>
                                <option value="9 Floor" <?php echo $property['totalfloor'] === '9 Floor' ? 'selected' : ''; ?>>9 Floor</option>
                                <option value="10 Floor" <?php echo $property['totalfloor'] === '10 Floor' ? 'selected' : ''; ?>>10 Floor</option>
                                <option value="11 Floor" <?php echo $property['totalfloor'] === '11 Floor' ? 'selected' : ''; ?>>11 Floor</option>
                                <option value="12 Floor" <?php echo $property['totalfloor'] === '12 Floor' ? 'selected' : ''; ?>>12 Floor</option>
                                <option value="13 Floor" <?php echo $property['totalfloor'] === '13 Floor' ? 'selected' : ''; ?>>13 Floor</option>
                                <option value="14 Floor" <?php echo $property['totalfloor'] === '14 Floor' ? 'selected' : ''; ?>>14 Floor</option>
                                <option value="15 Floor" <?php echo $property['totalfloor'] === '15 Floor' ? 'selected' : ''; ?>>15 Floor</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="views">Views (Read-Only)</label>
                            <input type="number" class="form-control" id="views" value="<?php echo htmlspecialchars($property['views']); ?>" readonly />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Features Section -->
            <div class="form-section">
                <div class="row g-3">
                    <div class="col-md-12">

                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured" value="1" <?php echo $property['is_featured'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_featured">Mark as Featured Property</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Images Section -->
            <div class="form-section">
                <h4>Images</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pimage">Main Image</label>
                            <input type="file" class="form-control-file" id="pimage" name="pimage" accept="image/*" />
                            <?php if (!empty($property['pimage'])): ?>
                                <div class="img-preview">
                                    <img src="property/<?php echo htmlspecialchars($property['pimage']); ?>" alt="Main Image" />
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pimage1">Image 1</label>
                            <input type="file" class="form-control-file" id="pimage1" name="pimage1" accept="image/*" />
                            <?php if (!empty($property['pimage1'])): ?>
                                <div class="img-preview">
                                    <img src="property/<?php echo htmlspecialchars($property['pimage1']); ?>" alt="Image 1" />
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pimage2">Image 2</label>
                            <input type="file" class="form-control-file" id="pimage2" name="pimage2" accept="image/*" />
                            <?php if (!empty($property['pimage2'])): ?>
                                <div class="img-preview">
                                    <img src="property/<?php echo htmlspecialchars($property['pimage2']); ?>" alt="Image 2" />
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pimage3">Image 3</label>
                            <input type="file" class="form-control-file" id="pimage3" name="pimage3" accept="image/*" />
                            <?php if (!empty($property['pimage3'])): ?>
                                <div class="img-preview">
                                    <img src="property/<?php echo htmlspecialchars($property['pimage3']); ?>" alt="Image 3" />
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pimage4">Image 4</label>
                            <input type="file" class="form-control-file" id="pimage4" name="pimage4" accept="image/*" />
                            <?php if (!empty($property['pimage4'])): ?>
                                <div class="img-preview">
                                    <img src="property/<?php echo htmlspecialchars($property['pimage4']); ?>" alt="Image 4" />
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="mapimage">Map Image</label>
                            <input type="file" class="form-control-file" id="mapimage" name="mapimage" accept="image/*" />
                            <?php if (!empty($property['mapimage'])): ?>
                                <div class="img-preview">
                                    <img src="property/<?php echo htmlspecialchars($property['mapimage']); ?>" alt="Map Image" />
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="topmapimage">Top Map Image</label>
                            <input type="file" class="form-control-file" id="topmapimage" name="topmapimage" accept="image/*" />
                            <?php if (!empty($property['topmapimage'])): ?>
                                <div class="img-preview">
                                    <img src="property/<?php echo htmlspecialchars($property['topmapimage']); ?>" alt="Top Map Image" />
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="groundmapimage">Ground Map Image</label>
                            <input type="file" class="form-control-file" id="groundmapimage" name="groundmapimage" accept="image/*" />
                            <?php if (!empty($property['groundmapimage'])): ?>
                                <div class="img-preview">
                                    <img src="property/<?php echo htmlspecialchars($property['groundmapimage']); ?>" alt="Ground Map Image" />
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Details Section -->
            <div class="form-section">
                <h4>Additional Details</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="uid">User ID</label>
                            <input type="number" class="form-control" id="uid" name="uid" value="<?php echo htmlspecialchars($property['uid']); ?>" required />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="date">Date Added (Read-Only)</label>
                            <input type="text" class="form-control" id="date" value="<?php echo htmlspecialchars($property['date']); ?>" readonly />
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Update Property</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz9ATKxIep9tiCxS/Z9fNfEXiDAYTujMAeBAsjFuCZSmKbSSUnQlmh/jp3" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <script>
        // Disable fields for land type
        document.getElementById('type').addEventListener('change', function() {
            const landFields = ['bedroom', 'bathroom', 'balcony', 'kitchen', 'toilet', 'totalfloor'];
            const isLand = this.value === 'land';
            landFields.forEach(field => {
                const element = document.getElementById(field);
                element.disabled = isLand;
                if (isLand) {
                    element.value = '';
                    element.removeAttribute('required');
                } else {
                    element.setAttribute('required', 'required');
                }
            });
        });
    </script>
</body>
</html>