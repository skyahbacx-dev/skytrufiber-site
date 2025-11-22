<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AHBA Development</title>

  <style>
    * { margin:0; padding:0; box-sizing:border-box; scroll-behavior:smooth; }
    body { font-family:"Segoe UI", Arial, sans-serif; color:#222; }

    /* ===== HEADER & FOOTER SHELL ===== */
    header, footer {
      position:fixed; left:0; width:100%;
      background:#00cc00; color:#fff;
      display:flex; justify-content:space-between; align-items:center;
      padding:10px 55px; z-index:999;
    }
    header { top:0; }
    footer { bottom:0; text-align:center; padding:8px; font-size:14px; }

    nav a {
      margin-left:16px; color:#fff; text-decoration:none;
      font-weight:600; letter-spacing:.3px; transition:.3s;
    }
    nav a:hover { color:#003300; }

    /* ===== PAGE SECTIONS ===== */
    .section {
      min-height:90vh;
      padding-top:110px;
      padding-bottom:70px;
      display:flex; justify-content:center; align-items:center;
    }

    /* ===== HERO ===== */
    #home {
      background:url('https://images.unsplash.com/photo-1506377247377-2a5b3b417ebb?auto=format&fit=crop&w=1600&q=80')
                center/cover no-repeat;
      position:relative;
    }
    #home::after { content:""; position:absolute; inset:0; background:rgba(0,0,0,.35); }

    .hero-content {
      position:relative; text-align:left; max-width:650px;
      background:#fff; padding:32px 36px; border-radius:14px;
      animation:fadeIn 1.5s ease-in-out;
      box-shadow:0 4px 14px rgba(0,0,0,.2);
    }
    .hero-content h1 { font-size:30px; font-weight:800; margin-bottom:6px; }
    .hero-content p { font-size:17px; margin-bottom:12px; }

    .btn {
      padding:10px 20px; display:inline-block;
      background:#00cc00; color:#fff; border-radius:8px;
      font-weight:700; text-decoration:none; transition:.25s;
    }
    .btn:hover { background:#009900; transform:translateY(-3px); }

    /* ===== ANIMATIONS ===== */
    @keyframes fadeIn { from{opacity:0; transform:translateY(25px);} to{opacity:1; transform:translateY(0);} }
    @keyframes fadeUp { from{opacity:0; transform:translateY(45px);} to{opacity:1; transform:translateY(0);} }

    .reveal { opacity:0; transform:translateY(40px); transition:1s ease; }
    .reveal.active { opacity:1; transform:translateY(0); }

    /* ===== ABOUT ===== */
    #about { background:#f7fff7; padding:60px 40px; }
    .about-container { max-width:850px; }
    .about-container h2 {
      font-size:34px; margin-bottom:18px; font-weight:800; color:#006600;
    }
    .about-container p { margin-bottom:14px; line-height:1.75; font-size:17px; }

    /* ===== SERVICES ===== */
    #services {
      background:#0b2f2f; color:white;
      padding:70px 40px;
      display:flex; gap:45px; align-items:center;
    }
    .services-text h2 { font-size:34px; margin-bottom:18px; font-weight:800; }
    .services-text ul { margin-left:15px; line-height:1.9; font-size:17px; }
    .services-img img { width:310px; border-radius:12px; }
  </style>
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
    <a href="#gallery">GALLERY</a>
    <a href="#contact">CONTACT</a>
  </nav>
</header>

<!-- ===== HERO SECTION ===== -->
<section id="home" class="section">
  <div class="hero-content">
    <p>Welcome to</p>
    <h1>A.Halili Business Aid Professional Services Inc.</h1>
    <p>All-in-one business solutions</p>
    <a href="#about" class="btn">Learn More</a>
  </div>
</section>

<!-- ===== ABOUT SECTION ===== -->
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

<!-- ===== SERVICES SECTION ===== -->
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
    <img src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=600&q=80" alt="Service person">
  </div>
</section>

<!-- ===== FOOTER ===== -->
<footer>
  📍 1454 Newton Street, Barangay San Isidro, Makati City &nbsp; | &nbsp; ✉️ ahbadevelopment@ahba.ph
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
