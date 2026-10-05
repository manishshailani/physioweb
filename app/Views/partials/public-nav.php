<?php
use App\Core\View;
$settings = $settings ?? [];
$clinicName = $settings['clinic_name'] ?? 'PhysioWeb Clinic';
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="/">
      <img src="/assets/img/logo.svg" alt="<?= View::e($clinicName) ?>" height="44">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
        <li class="nav-item"><a class="nav-link" href="/about">About</a></li>
        <li class="nav-item"><a class="nav-link" href="/services">Services</a></li>
        <li class="nav-item"><a class="nav-link" href="/team">Our Team</a></li>
        <li class="nav-item"><a class="nav-link" href="/gallery">Gallery</a></li>
        <li class="nav-item"><a class="nav-link" href="/blog">Blog</a></li>
        <li class="nav-item"><a class="nav-link" href="/testimonials">Testimonials</a></li>
        <li class="nav-item"><a class="nav-link" href="/contact">Contact</a></li>
        <li class="nav-item"><a class="nav-link" href="/patient/login">Patient Login</a></li>
        <li class="nav-item"><a class="btn btn-primary ms-lg-2" href="/book-appointment">Book Appointment</a></li>
      </ul>
    </div>
  </div>
</nav>
