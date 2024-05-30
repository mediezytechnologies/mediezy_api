@include('admin.Dashboard.pages.layouts.header')
@include('admin.Dashboard.pages.layouts.sidebar_left')
@include('admin.Dashboard.pages.layouts.sidebar_right')


<main class="body-content">
  <!-- Navigation Bar -->
  <nav class="navbar ms-navbar">
    <div class="ms-aside-toggler ms-toggler ps-0" data-bs-target="#ms-side-nav" data-bs-toggle="slideLeft">
      <span class="ms-toggler-bar bg-white"></span>
      <span class="ms-toggler-bar bg-white"></span>
      <span class="ms-toggler-bar bg-white"></span>
    </div>
    <div class="docfind-logo d-none d-xl-block">
      <a class="sigma_logo" href="../../../index.html">
        <img src="../../assets/img/docfind-logo.png" alt="logo">
      </a>
    </div>
    <div class="logo-sn logo-sm ms-d-block-sm">
      <a class="ps-0 ms-0 text-center navbar-brand me-0" href="../../index.html"><img src="../../assets/img/medboard-logo-84x41.png" alt="logo"> </a>
    </div>
    <ul class="ms-nav-list ms-inline mb-0" id="ms-nav-options">

      <li class="ms-nav-item  ms-d-none">
        <a href="#mymodal" class="text-white" data-bs-toggle="modal"><i class="flaticon-spreadsheet me-2"></i> Make an appointment</a>
      </li>

      <li class="ms-nav-item ms-d-none">
        <a href="#prescription" class="text-white" data-bs-toggle="modal"><i class="flaticon-pencil me-2"></i> Write a prescription</a>
      </li>

      <li class="ms-nav-item ms-d-none">
        <a href="#report1" class="text-white" data-bs-toggle="modal"><i class="flaticon-list me-2"></i> Generate Report</a>
      </li>

      <li class="ms-nav-item dropdown">
        <a href="#" class="text-disabled ms-has-notification" id="notificationDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="flaticon-bell"></i></a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
          <li class="dropdown-menu-header">
            <h6 class="dropdown-header ms-inline m-0"><span class="text-disabled">Notifications</span></h6>
            <span class="badge rounded-pill badge-info">4 New</span>
          </li>
          <li class="dropdown-divider"></li>
          <li class="ms-scrollable ms-dropdown-list">
            <a class="media p-2" href="#">
              <div class="media-body">
                <span>12 ways to improve your crypto dashboard</span>
                <p class="fs-10 my-1 text-disabled"><i class="material-icons">access_time</i> 30 seconds ago</p>
              </div>
            </a>
            <a class="media p-2" href="#">
              <div class="media-body">
                <span>You have newly registered users</span>
                <p class="fs-10 my-1 text-disabled"><i class="material-icons">access_time</i> 45 minutes ago</p>
              </div>
            </a>
            <a class="media p-2" href="#">
              <div class="media-body">
                <span>Your account was logged in from an unauthorized IP</span>
                <p class="fs-10 my-1 text-disabled"><i class="material-icons">access_time</i> 2 hours ago</p>
              </div>
            </a>
            <a class="media p-2" href="#">
              <div class="media-body">
                <span>An application form has been submitted</span>
                <p class="fs-10 my-1 text-disabled"><i class="material-icons">access_time</i> 1 day ago</p>
              </div>
            </a>
          </li>
          <li class="dropdown-divider"></li>
          <li class="dropdown-menu-footer text-center">
            <a href="#">View all Notifications</a>
          </li>
        </ul>
      </li>
      <li class="ms-nav-item ms-nav-user dropdown">
        <a href="#" id="userDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> <img class="ms-user-img ms-img-round float-end" src="../../assets/img/dashboard/doctor-3.jpg" alt="people"> </a>
        <ul class="dropdown-menu dropdown-menu-end user-dropdown" aria-labelledby="userDropdown">
          <li class="dropdown-menu-header">
            <h6 class="dropdown-header ms-inline m-0"><span class="text-disabled">Welcome, Dr Samuel Deo</span></h6>
          </li>
          <li class="dropdown-divider"></li>
          <li class="ms-dropdown-list">
            <a class="media fs-14 p-2" href="../prebuilt-pages/user-profile.html"> <span><i class="flaticon-user me-2"></i> Profile</span> </a>
            <a class="media fs-14 p-2" href="../apps/email.html"> <span><i class="flaticon-mail me-2"></i> Inbox</span> <span class="badge rounded-pill badge-info">3</span> </a>
            <a class="media fs-14 p-2" href="../prebuilt-pages/user-profile.html"> <span><i class="flaticon-gear me-2"></i> Account Settings</span> </a>
          </li>
          <li class="dropdown-divider"></li>
          <li class="dropdown-menu-footer">
            <a class="media fs-14 p-2" href="../prebuilt-pages/lock-screen.html"> <span><i class="flaticon-security me-2"></i> Lock</span> </a>
          </li>
          <li class="dropdown-menu-footer">
            <a class="media fs-14 p-2" href="../prebuilt-pages/default-login.html"> <span><i class="flaticon-shut-down me-2"></i> Logout</span> </a>
          </li>
        </ul>
      </li>
    </ul>
    <div class="ms-toggler ms-d-block-sm pe-0 ms-nav-toggler" data-bs-toggle="slideDown" data-bs-target="#ms-nav-options">
      <span class="ms-toggler-bar bg-white"></span>
      <span class="ms-toggler-bar bg-white"></span>
      <span class="ms-toggler-bar bg-white"></span>
    </div>
  </nav>
  <!-- Body Content Wrapper -->
  <div class="ms-content-wrapper">
    <div class="row">
      <div class="col-md-12">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb ps-0">
            <li class="breadcrumb-item"><a href="#"><i class="material-icons">home</i> Home</a></li>
            <li class="breadcrumb-item"><a href="#">Medicine</a></li>
            <li class="breadcrumb-item active" aria-current="page">Upload Medicine</li>
          </ol>
        </nav>
      </div>
      <div class="col-xl-12 col-md-12">
        <div class="ms-panel">
          <div class="ms-panel-header ms-panel-custome">
            <h6>Upload Medicine</h6>
            <a href="Medicine-list.html" class="ms-text-primary">Medicine List
            </a>
          </div>
          <div class="ms-panel-body">

            <form class="needs-validation" novalidate action="{{ route('uploadMedicineData') }}" method="post" enctype="multipart/form-data">
              @csrf
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="upload_file">Select Medicine (CSV Format only)</label>
                  <div class="input-group" style="padding-left: 5px;">
                    <!-- Change the id and name to 'upload_file' -->
                    <input type="file" class="form-control" id="upload_file" name="upload_file" required accept=".csv" style="width: 50%;">
                  </div>
                </div>
              </div>

              <button class="btn btn-warning mt-4 d-inline w-20" type="reset">Reset</button>
              <button class="btn btn-primary mt-4 d-inline w-20" type="submit">Upload medicines</button>
            </form>

          </div>
        </div>
      </div>
    </div>
  </div>
</main>
<!-- Reminder Modal -->
<div class="modal fade" id="reminder-modal" tabindex="-1" role="dialog" aria-labelledby="reminder-modal">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-secondary">
        <h5 class="modal-title has-icon text-white"> New Reminder</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <form>
        <div class="modal-body">
          <div class="ms-form-group">
            <label>Remind me about</label>
            <textarea class="form-control" name="reminder"></textarea>
          </div>
          <div class="ms-form-group">
            <span class="ms-option-name fs-14">Repeat Daily</span>
            <label class="ms-switch float-end">
              <input type="checkbox">
              <span class="ms-switch-slider round"></span>
            </label>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="ms-form-group">
                <input type="text" class="form-control datepicker" name="reminder-date" value="" />
              </div>
            </div>
            <div class="col-md-6">
              <div class="ms-form-group">
                <select class="form-control" name="reminder-time">
                  <option value="">12:00 pm</option>
                  <option value="">1:00 pm</option>
                  <option value="">2:00 pm</option>
                  <option value="">3:00 pm</option>
                  <option value="">4:00 pm</option>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-secondary shadow-none" data-bs-dismiss="modal">Upload Reminder</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Notes Modal -->
<div class="modal fade" id="notes-modal" tabindex="-1" role="dialog" aria-labelledby="notes-modal">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-secondary">
        <h5 class="modal-title has-icon text-white" id="NoteModal">New Note</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <form>
        <div class="modal-body">
          <div class="ms-form-group">
            <label>Note Title</label>
            <input type="text" class="form-control" name="note-title" value="">
          </div>
          <div class="ms-form-group">
            <label>Note Description</label>
            <textarea class="form-control" name="note-description"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-secondary shadow-none" data-bs-dismiss="modal">Upload Note</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="mymodal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog ms-modal-dialog-width">
    <div class="modal-content ms-modal-content-width">
      <div class="modal-header  ms-modal-header-radius-0">
        <h4 class="modal-title text-white">Make An Appointment</h4>
        <button type="button" class="close text-white" data-bs-dismiss="modal" aria-hidden="true">x</button>

      </div>
      <div class="modal-body p-0 text-start">
        <div class="col-xl-12 col-md-12">
          <div class="ms-panel ms-panel-bshadow-none">
            <div class="ms-panel-header">
              <h6>Medicine Information</h6>
            </div>
            <div class="ms-panel-body">
              <form class="needs-validation" novalidate>
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label for="validationCustom01">Medicine Name</label>
                    <div class="input-group">
                      <input type="text" class="form-control" id="validationCustom01" placeholder="Enter Name" required>

                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label for="validationCustom02">Date Of Birth</label>
                    <div class="input-group">
                      <input type="number" class="form-control" id="validationCustom02" required>

                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label for="validationCustom03">Disease</label>
                    <div class="input-group">
                      <input type="text" class="form-control" id="validationCustom03" placeholder="Disease" required>

                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-4 mb-2">
                    <label for="validationCustom04">Uploadress</label>
                    <div class="input-group">
                      <input type="text" class="form-control" id="validationCustom04" placeholder="Upload Uploadress" required>

                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label for="validationCustom05">Phone no.</label>
                    <div class="input-group">
                      <input type="text" class="form-control" id="validationCustom05" placeholder="Enter Phone No." required>

                    </div>

                  </div>

                  <div class="col-md-4 mb-3">
                    <label for="validationCustom06">Department Name</label>
                    <div class="input-group">
                      <input type="text" class="form-control" id="validationCustom06" placeholder="Enter Department Name" required>

                    </div>
                  </div>
                </div>



                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label for="validationCustom07">Appointment With</label>
                    <div class="input-group">
                      <input type="text" class="form-control" id="validationCustom07" placeholder="Enter Doctor Name" required>

                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label for="validationCustom08">Appointment Date</label>
                    <div class="input-group">
                      <input type="text" class="form-control" id="validationCustom08" placeholder="Enter Appointment Date" required>

                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label>Sex</label>
                    <ul class="ms-list d-flex">
                      <li class="ms-list-item ps-0">
                        <label class="ms-checkbox-wrap">
                          <input type="radio" name="radioExample" value="">
                          <i class="ms-checkbox-check"></i>
                        </label>
                        <span> Male </span>
                      </li>
                      <li class="ms-list-item">
                        <label class="ms-checkbox-wrap">
                          <input type="radio" name="radioExample" value="" checked="">
                          <i class="ms-checkbox-check"></i>
                        </label>
                        <span> Female </span>
                      </li>
                    </ul>
                  </div>
                </div>
                <button class="btn btn-warning mt-4 d-inline w-20" type="submit">Reset</button>
                <button class="btn btn-primary mt-4 d-inline w-20" type="submit">Upload Appointment</button>
              </form>
            </div>

          </div>
        </div>
      </div>

    </div>
  </div>
</div>


<!-- Modal -->
<div class="modal fade" id="prescription" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog ms-modal-dialog-width">
    <div class="modal-content ms-modal-content-width">
      <div class="modal-header  ms-modal-header-radius-0">
        <h4 class="modal-title text-white">Make a prescription</h4>
        <button type="button" class="close  text-white" data-bs-dismiss="modal" aria-hidden="true">x</button>

      </div>
      <div class="modal-body p-0 text-start">
        <div class="col-xl-12 col-md-12">
          <div class="ms-panel ms-panel-bshadow-none">
            <div class="ms-panel-header">
              <h6>Medicine Information</h6>
            </div>
            <div class="ms-panel-body">
              <form class="needs-validation" novalidate>
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label for="validationCustom09">Medicine Name</label>
                    <div class="input-group">
                      <input type="text" class="form-control" id="validationCustom09" placeholder="Enter Name" required>

                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label for="validationCustom10">Date Of Birth</label>
                    <div class="input-group">
                      <input type="number" class="form-control" id="validationCustom10" required>

                    </div>
                  </div>
                  <div class="col-md-4 mb-2">
                    <label for="validationCustom11">Uploadress</label>
                    <div class="input-group">
                      <input type="text" class="form-control" id="validationCustom11" placeholder="Upload Uploadress" required>

                    </div>
                  </div>

                </div>
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label for="validationCustom12">Phone no.</label>
                    <div class="input-group">
                      <input type="text" class="form-control" id="validationCustom12" placeholder="Enter Phone No." required>

                    </div>

                  </div>

                  <div class="col-md-4 mb-3">
                    <label for="validationCustom13">Medication</label>
                    <div class="input-group">
                      <input type="text" class="form-control" id="validationCustom13" placeholder="Acetaminophen" required>

                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label for="validationCustom14">Period Of medication</label>
                    <div class="input-group">
                      <input type="number" class="form-control" id="validationCustom14" placeholder="" required>

                    </div>
                  </div>
                </div>



                <div class="row">

                  <div class="col-md-4 mb-3">
                    <label for="validationCustom15">Appointment With</label>
                    <div class="input-group">
                      <input type="text" class="form-control" id="validationCustom15" placeholder="Enter Doctor Name" required>

                    </div>
                  </div>

                </div>
                <button class="btn btn-warning mt-4 d-inline w-20" type="submit">Save Prescription</button>
                <button class="btn btn-primary mt-4 d-inline w-20" type="submit">Save & Print</button>
              </form>
            </div>

          </div>
        </div>
      </div>

    </div>
  </div>
</div>


<!-- Modal -->
<div class="modal fade" id="report1" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog ms-modal-dialog-width">
    <div class="modal-content ms-modal-content-width">
      <div class="modal-header  ms-modal-header-radius-0">
        <h4 class="modal-title text-white">Generate report</h4>
        <button type="button" class="close  text-white" data-bs-dismiss="modal" aria-hidden="true">x</button>

      </div>
      <div class="modal-body p-0 text-start">
        <div class="col-xl-12 col-md-12">
          <div class="ms-panel ms-panel-bshadow-none">
            <div class="ms-panel-header">
              <h6>Medicine Information</h6>
            </div>
            <div class="ms-panel-body">
              <form class="needs-validation" novalidate>
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label for="validationCustom16">Medicine Name</label>
                    <div class="input-group">
                      <input type="text" class="form-control" id="validationCustom16" placeholder="Enter Name" required>

                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label for="validationCustom17">Date Of Birth</label>
                    <div class="input-group">
                      <input type="number" class="form-control" id="validationCustom17" required>

                    </div>
                  </div>
                  <div class="col-md-4 mb-2">
                    <label for="validationCustom22">Uploadress</label>
                    <div class="input-group">
                      <input type="text" class="form-control" id="validationCustom22" placeholder="Upload Uploadress" required>

                    </div>
                  </div>

                </div>
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label for="validationCustom18">Phone no.</label>
                    <div class="input-group">
                      <input type="text" class="form-control" id="validationCustom18" placeholder="Enter Phone No." required>

                    </div>

                  </div>

                  <div class="col-md-4 mb-3">
                    <label for="validationCustom19">Report Type</label>
                    <div class="input-group">
                      <input type="text" class="form-control" id="validationCustom19" placeholder="Diseases Report" required>

                    </div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label for="validationCustom23">Report Period</label>
                    <div class="input-group">
                      <input type="number" class="form-control" id="validationCustom23" placeholder="" required>

                    </div>
                  </div>
                </div>



                <div class="row">

                  <div class="col-md-4 mb-3">
                    <label for="validationCustom20">Appointment With</label>
                    <div class="input-group">
                      <input type="text" class="form-control" id="validationCustom20" placeholder="Enter Doctor Name" required>

                    </div>
                  </div>

                </div>
                <button class="btn btn-warning mt-4 d-inline w-20" type="submit">Generate Report</button>
                <button class="btn btn-primary mt-4 d-inline w-20" type="submit">Generate & Print</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@include('admin.Dashboard.pages.layouts.footer')