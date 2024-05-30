<!-- Sidebar Navigation Left -->

<body class="ms-body ms-aside-left-open ms-primary-theme ms-has-quickbar">
    <!-- Setting Panel -->
    <div class="ms-toggler ms-settings-toggle ms-d-block-lg">
        <i class="flaticon-gear"></i>
    </div>
    <div class="ms-settings-panel ms-d-block-lg">
        <div class="row">
            <div class="col-xl-4 col-md-4">
                <h4 class="section-title">Customize</h4>
                <div>
                    <label class="ms-switch">
                        <input type="checkbox" id="dark-mode">
                        <span class="ms-switch-slider round"></span>
                    </label>
                    <span> Dark Mode </span>
                </div>

            </div>
            <div class="col-xl-4 col-md-4">
                <h4 class="section-title">Keyboard Shortcuts</h4>
                <p class="ms-directions mb-0"><code>Esc</code> Close Quick Bar</p>
                <p class="ms-directions mb-0"><code>Alt + (1 -> 6)</code> Open Quick Bar Tab</p>
                <p class="ms-directions mb-0"><code>Alt + Q</code> Enable Quick Bar Configure Mode</p>
            </div>
        </div>
    </div>
    <!-- Preloader -->
    <!-- <div id="preloader-wrap">
    <div class="spinner spinner-8">
      <div class="ms-circle1 ms-child"></div>
      <div class="ms-circle2 ms-child"></div>
      <div class="ms-circle3 ms-child"></div>
      <div class="ms-circle4 ms-child"></div>
      <div class="ms-circle5 ms-child"></div>
      <div class="ms-circle6 ms-child"></div>
      <div class="ms-circle7 ms-child"></div>
      <div class="ms-circle8 ms-child"></div>
      <div class="ms-circle9 ms-child"></div>
      <div class="ms-circle10 ms-child"></div>
      <div class="ms-circle11 ms-child"></div>
      <div class="ms-circle12 ms-child"></div>
    </div>
  </div> -->
    <!-- Overlays -->
    <div class="ms-aside-overlay ms-overlay-left ms-toggler" data-bs-target="#ms-side-nav" data-bs-toggle="slideLeft"></div>
    <div class="ms-aside-overlay ms-overlay-right ms-toggler" data-bs-target="#ms-recent-activity" data-bs-toggle="slideRight"></div>



    <aside id="ms-side-nav" class="side-nav fixed ms-aside-scrollable ms-aside-left">
        <!-- Logo -->
        <div class="logo-sn ms-d-block-lg">
            <a class="ps-0 ms-0 text-center" href="<?php echo e(asset('index.html')); ?>">
                <img src="<?php echo e(asset('admin/assets/img/mediezylogowhite.png')); ?>" alt="logo">
            </a>

            <a href="#" class="text-center ms-logo-img-link"> <img src="<?php echo e(asset('assets/img/dashboard/adminavathar.png')); ?>" alt="logo"></a>
            <h5 class="text-center text-white mt-2">Dr.Samuel</h5>
            <h6 class="text-center text-white mb-3">Admin</h6>
        </div>
        <!-- Navigation -->
        <ul class="accordion ms-main-aside fs-14" id="side-nav-accordion">
            <!-- Dashboard -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#dashboard" aria-expanded="false" aria-controls="dashboard">
                    <span><i class="material-icons fs-16">dashboard</i>Dashboard </span>
                </a>
                <ul id="dashboard" class="collapse" aria-labelledby="dashboard" data-bs-parent="#side-nav-accordion">
                    <li> <a href="index.html">Docfindboard</a> </li>
                </ul>
            </li>
            <!-- /Dashboard -->
            <!-- Doctor -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#doctor" aria-expanded="false" aria-controls="doctor">
                    <span><i class="fas fa-stethoscope"></i>Doctor</span>
                </a>
                <ul id="doctor" class="collapse" aria-labelledby="doctor" data-bs-parent="#side-nav-accordion">
                    <li> <a href="pages/doctor/add-doctor.html">Add Doctor</a> </li>
                    <li> <a href="pages/doctor/doctor-list.html">Doctor List</a> </li>
                </ul>
            </li>
            <!-- Doctor -->
            <!-- Medicines -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#medicine" aria-expanded="false" aria-controls="medicine">
                    <span><i class="fas fa-database"></i>Medicines</span>
                </a>
                <ul id="medicine" class="collapse" aria-labelledby="medicine" data-bs-parent="#side-nav-accordion">
                    <li> <a href="<?php echo e(route('medicine_add')); ?>">Add Medicine</a> </li>
                    <li> <a href="pages/patient/patient-list.html">Medicines List</a> </li>
                </ul>
            </li>


            <!-- Patient -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#patient" aria-expanded="false" aria-controls="patient">
                    <span><i class="fas fa-user"></i>Patient</span>
                </a>
                <ul id="patient" class="collapse" aria-labelledby="patient" data-bs-parent="#side-nav-accordion">
                    <li> <a href="pages/patient/add-patient.html">Add Patient</a> </li>
                    <li> <a href="pages/patient/patient-list.html">Patient List</a> </li>
                </ul>
            </li>
            <!-- /Patient -->
            <!-- Department -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#department" aria-expanded="false" aria-controls="department">
                    <span><i class="fas fa-sitemap"></i>Department</span>
                </a>
                <ul id="department" class="collapse" aria-labelledby="department" data-bs-parent="#side-nav-accordion">
                    <li> <a href="pages/department/add-department.html">Add Department</a> </li>
                    <li> <a href="pages/department/department-list.html">Department List</a> </li>
                </ul>
            </li>
            <!-- /Department -->
            <!-- Schedule -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#schedule" aria-expanded="false" aria-controls="schedule">
                    <span><i class="fas fa-list-alt"></i>Doctor Schedule</span>
                </a>
                <ul id="schedule" class="collapse" aria-labelledby="schedule" data-bs-parent="#side-nav-accordion">
                    <li> <a href="pages/doctor-schedule/add-schedule.html">Add Schedule</a> </li>
                    <li> <a href="pages/doctor-schedule/schedule-list.html">Schedule List</a> </li>
                </ul>
            </li>
            <!-- /Schedule -->
            <!-- Appointment -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#appointment" aria-expanded="false" aria-controls="appointment">
                    <span><i class="far fa-check-square"></i>Appointment</span>
                </a>
                <ul id="appointment" class="collapse" aria-labelledby="appointment" data-bs-parent="#side-nav-accordion">
                    <li> <a href="pages/appointment/add-appointment.html">Add Appointment</a> </li>
                    <li> <a href="pages/appointment/appointment-list.html">Appointment List</a> </li>
                </ul>
            </li>
            <!-- /Appointment -->
            <!-- Payment -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#payment" aria-expanded="false" aria-controls="payment">
                    <span><i class="fas fa-credit-card"></i>Payment</span>
                </a>
                <ul id="payment" class="collapse" aria-labelledby="payment" data-bs-parent="#side-nav-accordion">
                    <li> <a href="pages/payment/add-payment.html">Add Payment</a> </li>
                    <li> <a href="pages/payment/payment-list.html">Payment List</a> </li>
                    <li> <a href="pages/payment/payment-invoice.html">Payment Invoice</a> </li>
                </ul>
            </li>
            <!-- /Payment -->
            <!-- Report -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#report" aria-expanded="false" aria-controls="report">
                    <span><i class="fas fa-file-alt"></i>Report</span>
                </a>
                <ul id="report" class="collapse" aria-labelledby="report" data-bs-parent="#side-nav-accordion">
                    <li> <a href="pages/report/patient-report.html">Patient Wise Report</a> </li>
                    <li> <a href="pages/report/doctor-report.html">Doctor Wise Report</a> </li>
                    <li> <a href="pages/report/total-report.html">Total Report</a> </li>
                </ul>
            </li>
            <!-- /Report -->
            <!-- Human Resource -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#human-resource" aria-expanded="false" aria-controls="human-resource">
                    <span><i class="far fa-user-circle"></i>Human Resource</span>
                </a>
                <ul id="human-resource" class="collapse" aria-labelledby="human-resource" data-bs-parent="#side-nav-accordion">
                    <li> <a href="pages/human-resource/add-employee.html">Add Employee</a> </li>
                    <li> <a href="pages/human-resource/employee-list.html">Employee List</a> </li>
                    <li> <a href="pages/human-resource/add-nurse.html">Add Nurse</a> </li>
                    <li> <a href="pages/human-resource/nurse-list.html">Nurse List</a> </li>
                    <li> <a href="pages/human-resource/add-pharmacist.html">Add Pharmacist</a> </li>
                    <li> <a href="pages/human-resource/pharmacist-list.html">Pharmacist List</a> </li>
                    <li> <a href="pages/human-resource/add-representative.html">Add Representative</a> </li>
                    <li> <a href="pages/human-resource/representative-list.html">Representative List</a> </li>
                </ul>
            </li>
            <!-- /Human Resource -->
            <!-- Bed -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#bed" aria-expanded="false" aria-controls="bed">
                    <span><i class="fas fa-bed"></i>Bed Manager</span>
                </a>
                <ul id="bed" class="collapse" aria-labelledby="bed" data-bs-parent="#side-nav-accordion">
                    <li> <a href="pages/bed-manager/add-bed.html">Add Bed</a> </li>
                    <li> <a href="pages/bed-manager/bed-list.html">Bed List</a> </li>
                </ul>
            </li>
            <!-- /Bed-->
            <!-- Notice -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#notice" aria-expanded="false" aria-controls="notice">
                    <span><i class="far fa-file-alt"></i>Notice</span>
                </a>
                <ul id="notice" class="collapse" aria-labelledby="notice" data-bs-parent="#side-nav-accordion">
                    <li> <a href="pages/notice/add-notice.html">Add Notice</a> </li>
                    <li> <a href="pages/notice/notice-list.html">Notice List</a> </li>
                </ul>
            </li>
            <!-- /Notice -->
            <li class="menu-item">
                <a href="pages/widgets.html">
                    <span><i class="material-icons fs-16">widgets</i>Widgets</span>
                </a>
            </li>
            <!-- Basic UI Elements -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#basic-elements" aria-expanded="false" aria-controls="basic-elements">
                    <span><i class="material-icons fs-16">filter_list</i>Basic UI Elements</span>
                </a>
                <ul id="basic-elements" class="collapse" aria-labelledby="basic-elements" data-bs-parent="#side-nav-accordion">
                    <li> <a href="pages/ui-basic/accordions.html">Accordions</a> </li>
                    <li> <a href="pages/ui-basic/alerts.html">Alerts</a> </li>
                    <li> <a href="pages/ui-basic/buttons.html">Buttons</a> </li>
                    <li> <a href="pages/ui-basic/breadcrumbs.html">Breadcrumbs</a> </li>
                    <li> <a href="pages/ui-basic/badges.html">Badges</a> </li>
                    <li> <a href="pages/ui-basic/cards.html">Cards</a> </li>
                    <li> <a href="pages/ui-basic/progress-bars.html">Progress Bars</a> </li>
                    <li> <a href="pages/ui-basic/preloaders.html">Pre-loaders</a> </li>
                    <li> <a href="pages/ui-basic/pagination.html">Pagination</a> </li>
                    <li> <a href="pages/ui-basic/tabs.html">Tabs</a> </li>
                    <li> <a href="pages/ui-basic/typography.html">Typography</a> </li>
                </ul>
            </li>
            <!-- /Basic UI Elements -->
            <!-- Advanced UI Elements -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#advanced-elements" aria-expanded="false" aria-controls="advanced-elements">
                    <span><i class="material-icons fs-16">code</i>Advanced UI Elements</span>
                </a>
                <ul id="advanced-elements" class="collapse" aria-labelledby="advanced-elements" data-bs-parent="#side-nav-accordion">
                    <li> <a href="pages/ui-advanced/draggables.html">Draggables</a> </li>
                    <li> <a href="pages/ui-advanced/sliders.html">Sliders</a> </li>
                    <li> <a href="pages/ui-advanced/modals.html">Modals</a> </li>
                    <li> <a href="pages/ui-advanced/rating.html">Rating</a> </li>
                    <li> <a href="pages/ui-advanced/tour.html">Tour</a> </li>
                    <li> <a href="pages/ui-advanced/cropper.html">Cropper</a> </li>
                    <li> <a href="pages/ui-advanced/range-slider.html">Range Slider</a> </li>
                </ul>
            </li>
            <!-- /Advanced UI Elements -->
            <li class="menu-item">
                <a href="pages/animation.html">
                    <span><i class="material-icons fs-16">format_paint</i>Animations</span>
                </a>
            </li>
            <!-- Form Elements -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#form-elements" aria-expanded="false" aria-controls="form-elements">
                    <span><i class="material-icons fs-16">input</i>Form Elements</span>
                </a>
                <ul id="form-elements" class="collapse" aria-labelledby="form-elements" data-bs-parent="#side-nav-accordion">
                    <li> <a href="pages/form/form-elements.html">Form Elements</a> </li>
                    <li> <a href="pages/form/form-layout.html">Form Layouts</a> </li>
                    <li> <a href="pages/form/form-validation.html">Form Validation</a> </li>
                    <li> <a href="pages/form/form-wizard.html">Form Wizard</a> </li>
                </ul>
            </li>
            <!-- /Form Elements -->
            <!-- Charts -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#charts" aria-expanded="false" aria-controls="charts">
                    <span><i class="material-icons fs-16">equalizer</i>Charts</span>
                </a>
                <ul id="charts" class="collapse" aria-labelledby="charts" data-bs-parent="#side-nav-accordion">
                    <li> <a href="pages/charts/chartjs.html">Chart JS</a> </li>
                    <li> <a href="pages/charts/morris-charts.html">Morris Chart</a> </li>
                </ul>
            </li>
            <!-- /Charts -->
            <!-- Tables -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#tables" aria-expanded="false" aria-controls="tables">
                    <span><i class="material-icons fs-16">tune</i>Tables</span>
                </a>
                <ul id="tables" class="collapse" aria-labelledby="tables" data-bs-parent="#side-nav-accordion">
                    <li> <a href="pages/tables/basic-tables.html">Basic Tables</a> </li>
                    <li> <a href="pages/tables/data-tables.html">Data tables</a> </li>
                </ul>
            </li>
            <!-- /Tables -->
            <!-- Popups -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#popups" aria-expanded="false" aria-controls="popups">
                    <span><i class="material-icons fs-16">message</i>Popups</span>
                </a>
                <ul id="popups" class="collapse" aria-labelledby="popups" data-bs-parent="#side-nav-accordion">
                    <li> <a href="pages/popups/sweet-alerts.html">Sweet Alerts</a> </li>
                    <li> <a href="pages/popups/toast.html">Toast</a> </li>
                </ul>
            </li>
            <!-- /Popups -->
            <!-- Icons -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#icons" aria-expanded="false" aria-controls="icons">
                    <span><i class="material-icons fs-16">border_color</i>Icons</span>
                </a>
                <ul id="icons" class="collapse" aria-labelledby="icons" data-bs-parent="#side-nav-accordion">
                    <li> <a href="pages/icons/fontawesome.html">Fontawesome</a> </li>
                    <li> <a href="pages/icons/flaticons.html">Flaticons</a> </li>
                    <li> <a href="pages/icons/materialize.html">Materialize</a> </li>
                </ul>
            </li>
            <!-- /Icons -->
            <!-- Maps -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#maps" aria-expanded="false" aria-controls="maps">
                    <span><i class="material-icons fs-16">map</i>Maps</span>
                </a>
                <ul id="maps" class="collapse" aria-labelledby="maps" data-bs-parent="#side-nav-accordion">
                    <li> <a href="pages/maps/google-maps.html">Google Maps</a> </li>
                    <li> <a href="pages/maps/vector-maps.html">Vector Maps</a> </li>
                </ul>
            </li>
            <!-- /Maps -->
            <!-- Pages -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#pages" aria-expanded="false" aria-controls="pages">
                    <span><i class="material-icons fs-16">insert_drive_file</i>Pages</span>
                </a>
                <ul id="pages" class="collapse" aria-labelledby="pages" data-bs-parent="#side-nav-accordion">
                    <li class="menu-item">
                        <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#authentication" aria-expanded="false" aria-controls="authentication">Authentication</a>
                        <ul id="authentication" class="collapse" aria-labelledby="authentication" data-bs-parent="#pages">
                            <li> <a href="pages/prebuilt-pages/default-login.html">Default Login</a> </li>
                            <li> <a href="pages/prebuilt-pages/modal-login.html">Modal Login</a> </li>
                            <li> <a href="pages/prebuilt-pages/default-register.html">Default Registration</a> </li>
                            <li> <a href="pages/prebuilt-pages/modal-register.html">Modal Registration</a> </li>
                            <li> <a href="pages/prebuilt-pages/lock-screen.html">Lock Screen</a> </li>
                        </ul>
                    </li>
                    <li> <a href="pages/prebuilt-pages/coming-soon.html">Coming Soon</a> </li>
                    <li> <a href="pages/prebuilt-pages/error.html">Error Page</a> </li>
                    <li class="menu-item"> <a href="pages/prebuilt-pages/faq.html"> FAQ </a> </li>
                    <li class="menu-item"> <a href="pages/prebuilt-pages/portfolio.html"> Portfolio </a> </li>
                    <li class="menu-item"> <a href="pages/prebuilt-pages/user-profile.html"> User Profile </a> </li>
                    <li class="menu-item"> <a href="pages/prebuilt-pages/invoice.html"> Invoice </a> </li>
                </ul>
            </li>
            <!-- /Pages -->
            <!-- Bonus Pages -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#bonus" aria-expanded="false">
                    <span><i class="material-icons fs-16">dashboard</i>Bonus Pages </span>
                </a>
                <ul id="bonus" class="collapse" data-bs-parent="#side-nav-accordion">
                    <li> <a href="pages/dashboard/web-analytics.html"> Web Analytics </a> </li>
                    <li> <a href="pages/dashboard/social-media.html">Social Media Management</a> </li>
                    <li> <a href="pages/dashboard/project-management.html">Department Management</a> </li>
                    <li> <a href="pages/dashboard/client-management.html">Patient Management</a> </li>
                </ul>
            </li>
            <!-- /Bonus Pages -->
            <!-- Apps -->
            <li class="menu-item">
                <a href="#" class="has-chevron" data-bs-toggle="collapse" data-bs-target="#apps" aria-expanded="false" aria-controls="apps">
                    <span><i class="material-icons fs-16">phone_iphone</i>Apps</span>
                </a>
                <ul id="apps" class="collapse" aria-labelledby="apps" data-bs-parent="#side-nav-accordion">
                    <li> <a href="pages/apps/chat.html">Chat</a> </li>
                    <li> <a href="pages/apps/email.html">Email</a> </li>
                    <li> <a href="pages/apps/to-do-list.html">To-do List</a> </li>
                </ul>
            </li>
            <!-- /Apps -->
        </ul>
    </aside><?php /**PATH /home/1163996.cloudwaysapps.com/tdtjxymxyy/public_html/resources/views/admin/Dashboard/pages/layouts/sidebar_left.blade.php ENDPATH**/ ?>