<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AHBA Development</title>

  <!-- CSS -->
  <link rel="stylesheet" href="/dashboard/style.css">
</head>

<body>

<header>
  <div class="logo-group">
    <a href="javascript:void(0)" onclick="smoothScroll('#home')">
      <img src="/AHBALOGO.png" height="72" alt="AHBA Logo">
    </a>

    <a href="/fiber" target="_blank">
      <img src="/SKYTRUFIBER.png" height="60" alt="SkyTruFiber">
    </a>
  </div>

  <!-- HAMBURGER (MOBILE) -->
  <div class="hamburger" onclick="toggleMenu()">
    <span></span>
    <span></span>
    <span></span>
  </div>

  <!-- NAV -->
  <nav id="mobileNav">
    <a href="javascript:void(0)" onclick="smoothScroll('#home'); toggleMenu()">HOME</a>
    <a href="javascript:void(0)" onclick="smoothScroll('#about'); toggleMenu()">ABOUT</a>
    <a href="javascript:void(0)" onclick="smoothScroll('#services'); toggleMenu()">SERVICES</a>
    <a href="javascript:void(0)" onclick="smoothScroll('#management'); toggleMenu()">MANAGEMENT</a>
    <a href="javascript:void(0)" onclick="smoothScroll('#gallery'); toggleMenu()">GALLERY</a>
    <a href="javascript:void(0)" onclick="smoothScroll('#contact'); toggleMenu()">CONTACT</a>
  </nav>
</header>

<!-- ================= HOME ================= -->

<section id="home" class="section">
  <div class="hero-content reveal">
    <p>Welcome to</p>
    <h1>A. Halili Business Aid Professional Services Inc.</h1>
    <p>All-in-one business solutions</p>
    <a href="javascript:void(0)" onclick="smoothScroll('#experience')" class="btn">Learn More</a>
  </div>
</section>

<!-- ================= EXPERIENCE ================= -->

<section id="experience" class="section reveal experience-section">
  <div class="exp-box reveal">
    <div class="exp-title">
      <span class="arrow">→</span>
      <h2>Providing experienced<br>business solutions</h2>
    </div>

    <p>
      With over 20 years of experience in the industry,
      <b>A. Halili Business Aid Professional Services Inc.</b> (AHBA Development)
      is committed to delivering high-quality, value-added services nationwide.
      <br><br>
      SEC Reg. <b>CS200902226</b> | DOLE Reg. <b>NCR-MPFO-72600-5-15-12-016-LR</b> | TIN <b>007-246-379-000</b>
    </p>
  </div>

  <div class="stats-row reveal">
    <div class="stat-card reveal">
      <div class="icon-circle">🏆</div>
      <h3 class="counter" data-target="20">0</h3>
      <p>Years Experience</p>
    </div>

    <div class="stat-card reveal">
      <div class="icon-circle">👷</div>
      <h3 class="counter" data-target="10000">0</h3>
      <p>Workers Deployed</p>
    </div>

    <div class="stat-card reveal">
      <div class="icon-circle">🌐</div>
      <h3 class="counter" data-target="300">0</h3>
      <p>Business Partners</p>
    </div>
  </div>
</section>

<!-- ================= ABOUT ================= -->

<section id="about" class="section reveal">
  <div class="about-container">
    <h2>About Us</h2>
    <p>Established in 2003, A. Halili Business Aid Professional Services Inc. has built a reputation for reliability and compliance.</p>
    <p>In 2015, the company became a licensed and fully compliant service provider.</p>
    <p>Customized service is our core approach — ensuring efficient, scalable operations.</p>
  </div>
</section>

<!-- ================= SERVICES ================= -->

<section id="services" class="section reveal">
  <div class="services-text reveal">
    <h2>Our Services</h2>
    <ul>
      <li>Full outsourcing solutions</li>
      <li>Psychological examinations & evaluation</li>
      <li>Payroll processing</li>
      <li>Recruitment & prescreening</li>
      <li>Interview processing</li>
      <li>Training & development programs</li>
      <li>Industrial & construction contracting</li>
    </ul>
  </div>

  <div class="services-img reveal">
    <img src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=600&q=80" alt="Services">
  </div>
</section>

<!-- ================= MANAGEMENT ================= -->

<section id="management" class="section reveal team-section">
  <h2 class="team-title">Our Leadership Team</h2>

  <div class="team-grid">
    <div class="leader-card reveal">
      <img src="/dashboard/alex.jpg" alt="Alex Halili">
      <h3>Alex G. Halili</h3>
      <span>Chief Executive Officer</span>
    </div>

    <div class="leader-card reveal">
      <img src="/dashboard/amy.jpg" alt="Amy Halili">
      <h3>Amy A. Halili</h3>
      <span>Chief Financial Officer</span>
    </div>

    <div class="leader-card reveal">
      <img src="/dashboard/allec.jpg" alt="Allec Halili">
      <h3>Allec Zandre A. Halili</h3>
      <span>Chief Operating Officer</span>
    </div>
  </div>
</section>

<!-- ================= GALLERY ================= -->

<section id="gallery" class="section reveal gallery-section">
  <h2 class="gallery-title">Gallery</h2>

  <div class="slider">
    <div class="slides">
      <img src="/dashboard/gallery/1.jpg" class="slide active" onclick="openModal(this)">
      <img src="/dashboard/gallery/2.jpg" class="slide" onclick="openModal(this)">
      <img src="/dashboard/gallery/3.jpg" class="slide" onclick="openModal(this)">
      <img src="/dashboard/gallery/4.jpg" class="slide" onclick="openModal(this)">
    </div>

    <button class="prev" onclick="changeSlide(-1)">❮</button>
    <button class="next" onclick="changeSlide(1)">❯</button>
  </div>

  <div class="dots">
    <span class="dot active" onclick="goToSlide(0)"></span>
    <span class="dot" onclick="goToSlide(1)"></span>
    <span class="dot" onclick="goToSlide(2)"></span>
    <span class="dot" onclick="goToSlide(3)"></span>
  </div>

  <div class="masonry reveal">
    <img src="/dashboard/gallery/5.jpg" onclick="openModal(this)">
    <img src="/dashboard/gallery/6.jpg" onclick="openModal(this)">
    <img src="/dashboard/gallery/7.jpg" onclick="openModal(this)">
    <img src="/dashboard/gallery/8.jpg" onclick="openModal(this)">
    <img src="/dashboard/gallery/9.jpg" onclick="openModal(this)">
    <img src="/dashboard/gallery/10.jpg" onclick="openModal(this)">
    <img src="/dashboard/gallery/11.jpg" onclick="openModal(this)">
    <img src="/dashboard/gallery/12.jpg" onclick="openModal(this)">
  </div>

  <video class="gallery-video" controls>
    <source src="/dashboard/gallery/video.mp4" type="video/mp4">
  </video>
</section>

<!-- ================= CONTACT ================= -->

<section id="contact" class="section reveal contact-section">
  <div class="contact-box reveal">
    <div class="contact-title">
      <span class="arrow">→</span>
      <h2>Get in Touch</h2>
    </div>

    <div class="contact-info">
      <div class="contact-item">
        <div class="icon-circle">📍</div>
        <div>
          <h4>Head Office</h4>
          2607 Cityland 10 Tower 1, HV Dela Costa Ave., Bel Air, Makati City
        </div>
      </div>

      <div class="contact-item">
        <div class="icon-circle">✉️</div>
        <div>
          <h4>Email</h4>
          <a href="mailto:admin@ahba.ph">admin@ahba.ph</a>
        </div>
      </div>

      <div class="contact-item">
        <div class="icon-circle">📞</div>
        <div>
          <h4>Phone</h4>
          <a href="tel:+639989615050">+63 998 961 5050</a>
        </div>
      </div>
    </div>
  </div>

  <div class="map-container reveal">
    <iframe
      width="100%"
      height="350"
      style="border:0;"
      loading="lazy"
      allowfullscreen
      src="https://maps.google.com/maps?q=Cityland%2010%20Tower%201%20HV%20Dela%20Costa%20Ave%20Bel%20Air%20Makati%20City&t=m&z=17&output=embed">
    </iframe>
  </div>
</section>

<footer>
  📍 1454 Newton Street, Barangay San Isidro, Makati City | ✉️ ahbadevelopment@ahba.ph
</footer>

<!-- JS -->
<script src="/dashboard/script.js"></script>

<script>
function toggleMenu() {
  document.getElementById("mobileNav").classList.toggle("open");
}
</script>

</body>
</html>
