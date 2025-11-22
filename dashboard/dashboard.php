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
    <a href="#home"><img src="../AHBALOGO.png" height="70"></a>
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

<!-- ===== HERO ===== -->
<section id="home" class="section">
  <div class="hero-content">
    <p>Welcome to</p>
    <h1>A.Halili Business Aid Professional Services Inc.</h1>
    <p>All-in-one business solutions</p>
    <a href="#about" class="btn">Learn More</a>
  </div>
</section>

<!-- ===== ABOUT ===== -->
<section id="about" class="section reveal">
  <div class="about-container">
    <h2>About Us</h2>

    <p>
      Established in 2003, A. HALILI BUSINESS AID PROFESSIONAL SERVICES INC. is a company that provides
      reliable and professional manpower support to meet the needs of its clients. It was subsequently
      incorporated in February 2009. Business Aid believes that beyond thorough screening and hiring
      qualified individuals for job vacancies, there must be proper training, motivation, and evaluation
      to ensure that clients receive the best value and performance from their personnel.
    </p>

    <p>
      In 2015, the company became a licensed and compliant service provider and/or contractor under the
      DOLE Department Order 18-A. By 2017, the company was also fully compliant under DOLE Department
      Order No. 174. The company caters to projects requiring Contractor or Sub-Contractor support across
      industries such as Construction, Manufacturing, and other service-oriented sectors.
    </p>

    <p>
      Business Aid operates with a simple yet highly efficient administration system. Client companies
      are immediately and respectfully attended to by our customer relations department, with a deep
      understanding of client needs and requirements. Customized service is our basic approach in dealing
      with our valued clients — providing access to a wide range of multidisciplinary services.
    </p>

    <p>
      Today, Business Aid continues to deliver cost-effective and value-added services, ensuring that
      clients receive the best possible solutions at competitive rates.
    </p>
  </div>
</section>

<!-- ===== SERVICES ===== -->
<section id="services" class="section reveal">
  <div class="services-text">
    <h2>Our Services</h2>
    <ul>
      <li>Full outsourcing solutions</li>
      <li>Intellectual & psychological examinations</li>
      <li>Payroll processing</li>
      <li>Recruitment & prescreening of applicants</li>
      <li>Initial interview screening</li>
      <li>Training & development programs</li>
      <li>Contractor in construction & manufacturing</li>
    </ul>
  </div>

  <div class="services-img">
    <img src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=600&q=80">
  </div>
</section>

<!-- ===== MANAGEMENT ===== -->
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

<footer>
  📍 1454 Newton Street, Barangay San Isidro, Makati City | ✉️ ahbadevelopment@ahba.ph
</footer>

<script>
  window.addEventListener('scroll', () => {
    document.querySelectorAll('.reveal').forEach(el => {
      const top = el.getBoundingClientRect().top;
      if (top < window.innerHeight - 80) el.classList.add('active');
    });
  });
</script>

</body>
</html>
