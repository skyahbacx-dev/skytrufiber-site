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
      padding:12px 80px; z-index:999;
    }
    header { top:0; }
    footer { bottom:0; text-align:center; padding:10px; font-size:14px; }

    nav a {
      margin-left:20px; color:#fff; text-decoration:none;
      font-weight:600; letter-spacing:.3px;
      transition:color .3s;
    }
    nav a:hover { color:#003300; }

    .section {
      min-height:100vh;
      padding-top:120px;
      padding-bottom:80px;
      display:flex; justify-content:center; align-items:center;
    }

    /* ===== HERO ===== */
    #home {
      background:url('https://images.unsplash.com/photo-1506377247377-2a5b3b417ebb?auto=format&fit=crop&w=1600&q=80')
                center/cover no-repeat;
      position:relative;
    }
    #home::after {
      content:\"\"; position:absolute; inset:0; background:rgba(0,0,0,.35);
    }
    .hero-content {
      position:relative; text-align:left; max-width:700px;
      background:#fff; padding:40px; border-radius:14px;
      animation:fadeIn 1.5s ease-in-out;
      box-shadow:0 5px 20px rgba(0,0,0,.25);
    }
    .hero-content h1 { font-size:34px; font-weight:800; margin-bottom:8px; }
    .hero-content p { font-size:18px; margin-bottom:14px; }

    .btn {
      display:inline-block; padding:12px 26px;
      background:#00cc00; color:#fff; font-weight:700;
      text-decoration:none; border-radius:8px;
      transition:.25s;
    }
    .btn:hover { background:#009900; transform:translateY(-3px); }

    @keyframes fadeIn { from{ opacity:0; transform:translateY(20px); } to{ opacity:1; transform:translateY(0); } }

    /* ===== ABOUT ===== */
    #about {
      background:#f7fff7; padding:90px 40px;
      justify-content:center; align-items:flex-start;
    }
    .about-container {
      max-width:900px; animation:fadeInUp .7s ease;
    }
    .about-container h2 {
      font-size:36px; font-weight:800; margin-bottom:20px; color:#006600;
    }
    .about-container p { margin-bottom:15px; line-height:1.75; font-size:17px; }

    /* ===== SERVICES ===== */
    #services {
      background:#0b2f2f; color:white;
      padding:80px 40px;
      display:flex; gap:60px; align-items:center;
    }
    .services-text h2 { font-size:36px; margin-bottom:20px; }
    .services-text ul { margin-left:20px; line-height:1.9; font-size:18px; }
    .services-img img { width:350px; border-radius:12px; }

    @keyframes fadeInUp {
      0% { opacity:0; transform:translateY(40px); }
      100% { opacity:1; transform:translateY(0); }
    }

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
    <a href="#about" class="btn">Ask Us How</a>
  </div>
</section>

<!-- ===== ABOUT SECTION ===== -->
<section id="about" class="section">
  <div class="about-container">
    <h2>About Us</h2>
    <p>Established in 2003, A. HALILI BUSINESS AID PROFESSIONAL SERVICES INC. is a vast company that provides manpower support ...</p>
    <p>In 2015, the company became a Licensed and compliant service provider and/or contractor ...</p>
    <p>Nowadays, Business Aid provides cost-effective and value-added services ...</p>
  </div>
</section>

<!-- ===== SERVICES SECTION ===== -->
<section id="services" class="section">
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

<footer>
  📍 1454 Newton Street, Barangay San Isidro, Makati City &nbsp; | &nbsp; ✉️ ahbadevelopment@ahba.ph
</footer>

</body>
</html>
