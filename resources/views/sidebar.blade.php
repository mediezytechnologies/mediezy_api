@php
use Illuminate\Support\Facades\Auth;

@endphp


<ul class="sidebar-nav" id="sidebar-nav">

  <li class="nav-item mt-3">
    <a class="nav-link collapsed " href="{{ url('/dashboard') }}">
      <i> <img src="{{url('assets/images/dashboard.png')}}" style="width:20px; height:20px;"></i>
      <span>Dashboard</span>
    </a>
  </li>




  <li class="nav-item {{ (Auth::check() && trim(Auth::user()->user_role) === '2') ? 'd-none' : '' }}">
    <a class="nav-link collapsed" data-bs-target="#task" data-bs-toggle="collapse" href="#">
      <i> <img src="{{url('assets/images/taskv.png')}}" style="width:20px; height:20px;"></i>
      <span>MASTER</span><i class="bi bi-chevron-down ms-auto"></i>
    </a>
    <ul id="task" class="nav-content collapse " data-bs-parent="#sidebar-nav">
      <li class="nav-item ">
        <a class="nav-link collapsed" href="{{url('/specialize')}}">
          <i> <img src="{{url('assets/images/taskcat.png')}}" style="width:20px; height:20px;"> </i>
          <span>Specialization</span>
        </a>
      </li>
      <li class="nav-item ">
        <a class="nav-link collapsed" href="{{url('/Specialization')}}">
          <i> <img src="{{url('assets/images/taskcat.png')}}" style="width:20px; height:20px;"> </i>
          <span>Specifcation</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" href="{{url('/Subspecialization')}}">
          <i> <img src="{{url('assets/images/taskcat.png')}}" style="width:20px; height:20px;"> </i>
          <span>Sub Specification</span>
        </a>
      </li>
      <li class="nav-item ">
        <a class="nav-link collapsed" href="{{url('/Docter')}}">
          <i> <img src="{{url('assets/images/taskcat.png')}}" style="width:20px; height:20px;"> </i>
          <span>Doctors</span>
        </a>
      </li>

      <li class="nav-item ">
        <a class="nav-link collapsed" href="{{url('/banner')}}">
          <i> <img src="{{url('assets/images/taskcat.png')}}" style="width:20px; height:20px;"> </i>
          <span>Banner</span>
        </a>
      </li>

      <li class="nav-item ">
        <a class="nav-link collapsed" href="{{ route('articles.list') }}">
          <i> <img src="{{url('assets/images/taskcat.png')}}" style="width:20px; height:20px;"> </i>
          <span>App Articles</span>
        </a>
      </li>







    </ul>
  </li>
  <!-- task Dropdown End -->






  {{-- <form method="POST" action="{{ route('logout') }}">
  @csrf
  <button type="submit" class="nav-link" style="border: none; background: none;">
    <i><img src="{{url('assets/images/logout.png')}}" style="width:20px; height:20px;"></i>
    <span>Log Out</span>
  </button>
  </form> --}}
</ul>