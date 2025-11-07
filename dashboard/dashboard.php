<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AHBA Development</title>
  <style>
    /* ===== General Page Style ===== */
    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
      font-family: "Segoe UI", Arial, sans-serif;
      color: #222;
      overflow: hidden;
    }

    body {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      background: linear-gradient(to bottom right, #c8f8c8, #e7ffe7);
      position: relative;
    }

    /* ===== Transparent Logo Background ===== */
    body::before {
      content: "";
      position: absolute;
      inset: 0;
      background: url('AHBALOGO.png') no-repeat center center;
      background-size: 700px auto;
      opacity: 0.08;
      z-index: 0;
    }

    /* ===== Header ===== */
    header {
      background-color: #00cc00;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 80px;
      box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
      position: relative;
      z-index: 1;
    }

    /* ===== Logo Group ===== */
    .logo-group {
      display: flex;
      align-items: center;
      gap: 40px;
      position: relative;
    }

    .logo-group a img {
      height: 80px;
      transition: transform 0.2s ease, opacity 0.2s ease;
      display: block;
    }

    .logo-group a img:hover {
      transform: scale(1.05);
      opacity: 0.9;
    }

    /* ===== SkyTruFiber Dropdown ===== */
    .stf-dropdown {
      position: relative;
      display: inline-block;
    }

    .stf-trigger {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
    }

    .stf-caret {
      font-size: 12px;
      color: #fff;
      background: rgba(0, 0, 0, 0.15);
      padding: 2px 6px;
      border-radius: 10px;
      line-height: 1;
      margin-left: 6px;
      user-select: none;
    }

    .stf-menu {
      position: absolute;
      top: 90px;
      left: 0;
      min-width: 210px;
      background: #ffffff;
      border-radius: 10px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      padding: 8px;
      display: none;
      z-index: 9999;
    }

    .stf-menu::before {
      content: "";
      position: absolute;
      top: -8px;
      left: 20px;
      width: 0;
      height: 0;
      border-left: 8px solid transparent;
      border-right: 8px solid transparent;
      border-bottom: 8px solid #ffffff;
      filter: drop-shadow(0 -1px 1px rgba(0,0,0,0.08));
    }

    .stf-item {
      display: block;
      padding: 10px 12px;
      border-radius: 8px;
      color: #006600;
      text-decoration: none;
      font-weight: 600;
      letter-spacing: .2px;
      transition: background .2s ease, transform .05s ease;
      white-space: nowrap;
    }

    .stf-item:hover {
      background: #e6ffe6;
      transform: translateX(2px);
    }

    /* Show on hover or focus */
    .stf-dropdown:hover .stf-menu,
    .stf-dropdown:focus-within .stf-menu {
      display: block;
    }

    /* ===== Navigation ===== */
    nav {
      display: flex;
      gap: 20px;
    }

    nav a {
      color: #fff;
      text-decoration: none;
      font-weight: 600;
      font-size: 14px;
      letter-spacing: 0.4px;
      transition: color 0.3s ease;
    }

    nav a:hover {
      color: #003300;
    }

    /* ===== Hero Section ===== */
    .hero {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 0 20px;
      position: relative;
      z-index: 1;
    }

    .hero h1 {
      font-size: 46px;
      font-weight: 800;
      color: #006600;
      margin-bottom: 15px;
      text-shadow: 1px 1px 3px rgba(255,255,255,0.8);
    }

    .hero p {
      font-size: 18px;
      color: #333;
      max-width: 800px;
      line-height: 1.7;
    }

    /* ===== Footer ===== */
    footer {
      background-color: #009900;
      color: #fff;
      text-align: center;
      padding: 12px;
      font-size: 14px;
      position: relative;
      z-index: 1;
    }

    footer p {
      margin: 4px 0;
    }

    /* ===== Responsive Design ===== */
    @media (max-width: 768px) {
      header {
        flex-direction: column;
        padding: 15px 30px;
      }

      .logo-group {
        flex-direction: column;
        gap: 15px;
      }

      nav {
        margin-top: 10px;
        flex-wrap: wrap;
        justify-content: center;
      }

      .hero h1 {
        font-size: 32px;
      }

      .hero p {
        font-size: 15px;
        padding: 0 10px;
      }

      .stf-menu {
        top: 70px;
      }
    }
  </style>
</head>
<body>
  <header>
    <div class="logo-group">
      <a href="dashboard.php" class="stf-trigger">
        <img src="AHBALOGO.png" alt="AHBA Development Logo">
      </a>

      <a href="https://www.homeaiddepot.com/" target="_blank" class="stf-trigger">
        <img src="HOMEAID.png" alt="HomeAid Depot Logo">
      </a>

      <!-- SkyTruFiber Dropdown -->
      <div class="stf-dropdown" tabindex="0">
        <a href="SKYTRUFIBER/skytrufiber.php" target="_blank" class="stf-trigger" aria-haspopup="true" aria-expanded="false">
          <img src="SKYTRUFIBER.png" alt="Sky TruFiber Logo">
          <span class="stf-caret">▼</span>
        </a>
        <div class="stf-menu" role="menu" aria-label="SkyTruFiber menu">
          <a class="stf-item" role="menuitem" href="SKYTRUFIBER/services.php" target="_blank">Services</a>
          <a class="stf-item" role="menuitem" href="SKYTRUFIBER/offers.php" target="_blank">Offers</a>
          <a class="stf-item" role="menuitem" href="SKYTRUFIBER/partnerships.php" target="_blank">Partnerships</a>
        </div>
      </div>
    </div>

    <nav>
      <a href="#">HOME</a>
      <a href="#">ABOUT</a>
      <a href="#">SERVICES</a>
      <a href="#">GALLERY</a>
      <a href="#">CONTACT</a>
    </nav>
  </header>

  <section class="hero">
    <h1>Welcome to AHBA Development</h1>
    <p>
      Providing trusted manpower and business solutions across the Philippines — committed to 
      excellence, integrity, and service. Proudly serving clients nationwide with professionalism and care.
    </p>
  </section>

  <footer>
    <p>📍 1454 Newton Street, Barangay San Isidro, Makati City</p>
    <p>✉️ ahbadevelopment@ahba.ph</p>
  </footer>
</body>
</html>
