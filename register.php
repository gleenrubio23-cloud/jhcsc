<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <link rel="apple-touch-icon" sizes="76x76" href="assets/img/apple-icon.png">
  <link rel="icon" type="image/png" href="assets/img/guidance logo.png">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <title>JHCSC-SADS | Register</title>
  <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, shrink-to-fit=no'
    name='viewport' />

  <!-- SEO Meta Tags -->
  <link rel="canonical" href="https://www.creative-tim.com/product/material-dashboard-pro" />
  <meta name="keywords" content="creative tim, html dashboard, bootstrap 4, material design, responsive" />
  <meta name="description"
    content="Material Dashboard PRO is a Premium Material Bootstrap 4 Admin with a fresh, new design inspired by Google's Material Design." />

  <!-- Fonts and Icons -->
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700|Roboto+Slab:400,700|Material+Icons" />
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/latest/css/font-awesome.min.css">

  <!-- CSS Files -->
  <link href="assets/css/material-dashboard.min.css?v=2.1.0" rel="stylesheet" />
  <link href="assets/demo/demo.css" rel="stylesheet" />

  <style>
    .custom-file-input:lang(en)~.custom-file-label::after {
      content: "Browse";
      background-color: #28a745;
      /* Green background */
      color: white;
      /* White text */
      border: 1px solid #28a745;
      /* Green border */
      padding: 5px 15px;
      border-radius: 3px;
    }

    /* Optional: Add hover effect */
    .custom-file-input:hover~.custom-file-label::after {
      background-color: #218838;
      /* Darker green on hover */
      border-color: #218838;
    }
  </style>
</head>

<body>
  <!-- Google Tag Manager (noscript) -->
  <noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NKDMSK6" height="0" width="0"
      style="display:none;visibility:hidden"></iframe>
  </noscript>

  <!-- Main Wrapper -->
  <div class="wrapper wrapper-full-page"
    style="background-image: url('assets/img/jhcsc.jpg'); background-size: cover; background-position: top center;">
    <div class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-10 ml-auto mr-auto">
            <div class="card">
              <div class="card-header card-header-success card-header-icon">
                <div class="card-text">
                  <h4 class="card-title">
                    <center>JHCSC Student Academic Discipline System Registration form</center>
                  </h4>
                </div>
                <h4 class="card-title">Personal Information</h4>
                <div class="card-body">
                  <form method="POST" action="functions/submit-form.php" enctype="multipart/form-data"
                    autocomplete="off">
                    <div class="row">
                      <div class="col-md">
                        <div class="form-group">
                          <label for="fname" class="bmd-label-floating">Firstname</label>
                          <input type="text" class="form-control" id="fname" name="firstname" required>
                        </div>
                      </div>
                      <div class="col-md">
                        <div class="form-group">
                          <label for="mname" class="bmd-label-floating">Middle Name</label>
                          <input type="text" class="form-control" id="mname" name="middlename">
                        </div>
                      </div>
                      <div class="col-md">
                        <div class="form-group">
                          <label for="lname" class="bmd-label-floating">Last Name</label>
                          <input type="text" class="form-control" id="lname" name="lastname" required>
                        </div>
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-md">
                        <div class="form-group">
                          <label for="birthdate" class="bmd-label-floating">Birth Date</label>
                          <input type="date" class="form-control" id="birthdate" name="birthdate" required>
                        </div>
                      </div>
                      <div class="col-md">
                        <div class="form-group">
                          <label for="gender" class="bmd-label-floating">Gender</label>
                          <div class="form-check form-check-inline">
                            <label class="form-check-label">
                              <input class="form-check-input" type="radio" name="gender" value="male" checked> Male
                              <span class="circle">
                                <span class="check"></span>
                              </span>
                            </label>
                          </div>
                          <div class="form-check form-check-inline">
                            <label class="form-check-label">
                              <input class="form-check-input" type="radio" name="gender" value="female"> Female
                              <span class="circle">
                                <span class="check"></span>
                              </span>
                            </label>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="nationality" class="bmd-label-floating">Nationality</label>
                      <input type="text" class="form-control" id="nationality" name="nationality" required>
                    </div>

                    <div class="form-group">
                      <label for="contact" class="bmd-label-floating">Contact No.</label>
                      <input type="tel" class="form-control" id="contact" name="contact" required>
                    </div>
                </div>
                <!-- End of Personal Information Section -->

                <!-- Parent/Guardian Information Section -->
                <h4 class="card-title">Parent/Guardian Information</h4>
                <div class="card-body">
                  <div class="form-group">
                    <label for="guardian_name" class="bmd-label-floating">Parent/Guardian Name</label>
                    <input type="text" class="form-control" id="guardian_name" name="guardian_name" required>
                  </div>
                  <div class="form-group">
                    <label for="relationship" class="bmd-label-floating">Relationship to student</label>
                    <input type="text" class="form-control" id="relationship" name="relationship" required>
                  </div>
                  <div class="form-group">
                    <label for="address" class="bmd-label-floating">Address (Purok, Barangay, Municipality,
                      Province)</label>
                    <input type="text" class="form-control" id="address" name="address" required>
                  </div>
                  <div class="form-group">
                    <label for="guardian_contact" class="bmd-label-floating">Contact No.</label>
                    <input type="tel" class="form-control" id="guardian_contact" name="guardian_contact" required>
                  </div>
                </div>
                <!-- End of Parent/Guardian Information Section -->

                <!-- Education Information Section -->
                <h4 class="card-title">Education Information</h4>
                <div class="card-body">
                  <div class="form-group">
                    <label for="course" class="bmd-label-floating">Select Course</label>
                    <select class="form-control" id="course" name="course" required>
                      <option value="" disabled selected>Select your course</option>
                      <option value="BSIT">BSIT</option>
                      <option value="BIT">BIT</option>
                      <!-- <option value="BTVTED">BTVTED</option>
                      <option value="BTLED">BTLED</option>-->
                    </select>
                  </div>
                  <div class="form-group">
                    <label for="section" class="bmd-label-floating">Select Section</label>
                    <select class="form-control" id="section" name="section" required>
                      <option value="" disabled selected>Select your section</option>
                      <option value="A">Section A</option>
                      <option value="B">Section B</option>
                      <option value="C">N/A</option>

                    </select>
                  </div>
                  <div class="form-group">
                    <label for="year" class="bmd-label-floating">Select Year</label>
                    <select class="form-control" id="year" name="year" required>
                      <option value="" disabled selected>Select your year</option>
                      <option value="1">First Year</option>
                      <option value="2">Second Year</option>
                      <option value="3">Third Year</option>
                      <option value="4">Fourth Year</option>
                    </select>
                  </div>
                </div>
                <!-- End of Education Information Section -->

                <!-- Account Section -->
                <h4 class="card-title">Account</h4>
                <div class="card-body">
                  <div class="form-group">
                    <label for="username" class="bmd-label-floating">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                  </div>
                  <div class="form-group">
                    <label for="email" class="bmd-label-floating">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                  </div>

                  <div class="form-group">
                    <label for="password" class="bmd-label-floating">Password</label>
                    <input type="password" class="form-control" id="password" name="password">
                  </div>
                  <div class="form-group">
                    <label for="inputGroupFile01" class="bmd-label-floating">Upload Photo</label>
                    <div class="custom-file">
                      <input type="file" class="custom-file-input" id="inputGroupFile01" name="photo"
                        onchange="displayFileName()">
                      <label class="custom-file-label" for="inputGroupFile01">Choose file</label>
                    </div>
                  </div>
                </div>
                <!-- End of Account Section -->

                <!-- Submit Button -->
                <div class="card-footer text-center">
                  <button type="submit" class="btn btn-fill btn-success">Submit</button>
                </div>

                <!-- Login Link -->
                <p class="card-title">Already have an account? <a href="index.php"
                    class="text-success font-weight-bold">Login Here</a></p>

                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    function displayFileName() {
      // Get the selected file name
      var fileName = document.getElementById("inputGroupFile01").files[0].name;
      // Set the file name in the label
      document.querySelector('.custom-file-label').textContent = fileName;
    }
  </script>

  <!-- JS Files -->
  <script src="assets/js/core/jquery.min.js"></script>
  <script src="assets/js/core/popper.min.js"></script>
  <script src="assets/js/core/bootstrap-material-design.min.js"></script>
  <script src="assets/js/plugins/perfect-scrollbar.jquery.min.js"></script>
  <script src="assets/js/material-dashboard.min.js?v=2.1.0" type="text/javascript"></script>
  <script src="assets/demo/demo.js"></script>

  <!-- Optional Scripts -->
  <script src="assets/js/plugins/moment.min.js"></script>
  <script src="assets/js/plugins/sweetalert2.js"></script>
  <script src="assets/js/plugins/jquery.validate.min.js"></script>
  <script src="assets/js/plugins/bootstrap-selectpicker.js"></script>

</body>

</html>