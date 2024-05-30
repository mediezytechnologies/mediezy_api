<?php
use Illuminate\Support\Facades\Auth;

?>


<ul class="sidebar-nav" id="sidebar-nav">

  <li class="nav-item mt-3">
    <a class="nav-link collapsed " href="<?php echo e(url('/dashboard')); ?>">
      <i> <img src="<?php echo e(url('assets/images/dashboard.png')); ?>" style="width:20px; height:20px;"></i>
      <span>Dashboard</span>
    </a>
  </li>




  <li class="nav-item <?php echo e((Auth::check() && trim(Auth::user()->user_role) === '2') ? 'd-none' : ''); ?>">
    <a class="nav-link collapsed" data-bs-target="#task" data-bs-toggle="collapse" href="#">
      <i> <img src="<?php echo e(url('assets/images/taskv.png')); ?>" style="width:20px; height:20px;"></i>
      <span>MASTER</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>
    <ul id="task" class="nav-content collapse " data-bs-parent="#sidebar-nav">
      <li class="nav-item ">
        <a class="nav-link collapsed" href="<?php echo e(url('/specialize')); ?>">
          <i> <img src="<?php echo e(url('assets/images/taskcat.png')); ?>" style="width:20px; height:20px;"> </i>
          <span>Specialization</span>
        </a>
      </li>
      <li class="nav-item ">
        <a class="nav-link collapsed" href="<?php echo e(url('/Specialization')); ?>">
          <i> <img src="<?php echo e(url('assets/images/taskcat.png')); ?>" style="width:20px; height:20px;"> </i>
          <span>Specifcation</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" href="<?php echo e(url('/Subspecialization')); ?>">
          <i> <img src="<?php echo e(url('assets/images/taskcat.png')); ?>" style="width:20px; height:20px;"> </i>
          <span>Sub Specification</span>
        </a>
      </li>
      <li class="nav-item ">
        <a class="nav-link collapsed" href="<?php echo e(url('/Docter')); ?>">
          <i> <img src="<?php echo e(url('assets/images/taskcat.png')); ?>" style="width:20px; height:20px;"> </i>
          <span>Doctors</span>
        </a>
      </li>

      <li class="nav-item ">
        <a class="nav-link collapsed" href="<?php echo e(url('/banner')); ?>">
          <i> <img src="<?php echo e(url('assets/images/taskcat.png')); ?>" style="width:20px; height:20px;"> </i>
          <span>Banner</span>
        </a>
      </li>

      <li class="nav-item ">
        <a class="nav-link collapsed" href="<?php echo e(route('articles.list')); ?>">
          <i> <img src="<?php echo e(url('assets/images/taskcat.png')); ?>" style="width:20px; height:20px;"> </i>
          <span>App Articles</span>
        </a>
      </li>







    </ul>
  </li>
  <!-- task Dropdown End -->






  
</ul><?php /**PATH /home/1163996.cloudwaysapps.com/tdtjxymxyy/public_html/resources/views/sidebar.blade.php ENDPATH**/ ?>