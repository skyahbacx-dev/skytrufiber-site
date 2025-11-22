<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AHBA Development</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>

<header>
  <div class="logo-group">
    <a href="#home"><img src="../AHBALOGO.png" height="72"></a>
    <a href="../SKYTRUFIBER/skytrufiber.php" target="_blank">
      <img src="../SKYTRUFIBER.png" height="60">
    </a>
  </div>

  <nav>
    <a href="#home">HOME</a>
    <a href="#about">ABOUT</a>
    <a href="#services">SERVICES</a>
    <a href="#management">MANAGEMENT</a>
    <a href="#gallery">GALLERY</a>
    <a href="#contact">CONTACT</a>
  </nav>
</header>

<section id="home" class="section">
  <div class="hero-content">
    <p>Welcome to</p>
    <h1>A.Halili Business Aid Professional Services Inc.</h1>
    <p>All-in-one business solutions</p>
    <a href="#about" class="btn">Learn More</a>
  </div>
</section>

<section id="experience" class="section reveal experience-section">
  <div class="exp-box">
    <div class="exp-title">
      <span class="arrow">→</span>
      <h2>Providing experienced<br>business solutions</h2>
    </div>
    <p>
      With over 20 years of experience in the industry, <b>A.Halili Business Aid Professional Services Inc.</b>
      with the registered trademark of <b>AHBA Development</b> ...
      SEC Reg. <b>CS200902226</b> | DOLE Reg. <b>NCR-MPFO-72600-5-15-12-016-LR</b> | TIN <b>007-246-379-000</b>
    </p>
  </div>
</section>

<section id="about" class="section reveal">
  <div class="about-container">
    <h2>About Us</h2>
    <p>
      Established in 2003, A. HALILI BUSINESS AID PROFESSIONAL SERVICES INC. is a company that provides
      reliable and professional manpower support to meet the needs of its clients...
    </p>
    <p>In 2015, the company became a licensed and compliant service provider...</p>
    <p>Business Aid operates with a simple yet highly efficient administration...</p>
    <p>Today, Business Aid continues to deliver cost-effective and value-added services...</p>
  </div>
</section>

<section id="services" class="section reveal">
  <div class="services-text">
    <h2>Our Services</h2>
    <ul>
      <li>Full outsourcing solutions</li>
      <li>Intellectual & psychological examinations</li>
      <li>Payroll processing</li>
      <li>Recruitment & prescreening</li>
      <li>Initial interview screening</li>
      <li>Training & development</li>
      <li>Construction & manufacturing contracting</li>
    </ul>
  </div>
  <div class="services-img">
    <img src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=600&q=80">
  </div>
</section>

<section id="management" class="section reveal team-section">
  <h2 class="team-title">Our Leadership Team</h2>

  <div class="team-card">
    <img src="alex.jpg" class="team-photo">
    <div>
      <h3>Alex G. Halili, CEO</h3>
      <p>Our president has over 3 decades of experience ...</p>
    </div>
  </div>

  <div class="team-card">
    <img src="amy.jpg" class="team-photo">
    <div>
      <h3>Amy A. Halili, CFO</h3>
      <p>A B.S. Accountancy graduate with more than 25 years ...</p>
    </div>
  </div>

  <div class="team-card">
    <img src="allec.jpg" class="team-photo">
    <div>
      <h3>Allec Zandre A. Halili, COO</h3>
      <p>A B.S. Entrepreneurship graduate ...</p>
    </div>
  </div>
</section>

<section id="contact" class="section reveal contact-section">
  <div class="contact-box">
    <div class="contact-title">
      <span class="arrow">→</span><h2>Get in Touch</h2>
    </div>

    <div class="contact-info">
      <div class="contact-item">
        <div class="icon-circle">📍</div>
        <div>
          <h4>Head Office</h4>
          <p>2607 Cityland 10 Tower 1 HV Dela Costa Ave.<br>Bel Air, Makati City</p>
        </div>
      </div>

      <div class="contact-item">
        <div class="icon-circle">✉️</div>
        <div>
          <h4>Email</h4>
          <p><a href="mailto:admin@ahba.ph">admin@ahba.ph</a></p>
        </div>
      </div>

      <div class="contact-item">
        <div class="icon-circle">📞</div>
        <div>
          <h4>Phone</h4>
          <p><a href="tel:+639989615050">+63 998 961 5050</a></p>
        </div>
      </div>
    </div>
  </div>

  <div class="map-container">
    <iframe
      width="100%"
      height="220"
      style="border:0;"
      loading="lazy"
      allowfullscreen
      src="https://maps.google.com/maps?q=Cityland%2010%20Tower%201%20HV%20Dela%20Costa%20Ave%20Bel%20Air%20Makati%20City&t=m&z=17&output=embed&iwloc=near">
    </iframe>
  </div>
</section>

<footer>
  📍 1454 Newton Street, Barangay San Isidro, Makati City &nbsp; | &nbsp; ✉️ ahbadevelopment@ahba.ph
</footer>

<script>
window.addEventListener("scroll", () => {
  document.querySelectorAll(".reveal").forEach(el => {
    const top = el.getBoundingClientRect().top;
    if (top < window.innerHeight - 80) el.classList.add("active");
  });
});
</script>

</body>
</html>
