<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="A. Halili Business Aid Professional Services Inc. (AHBA Development): outsourcing, recruitment, payroll, training and contracting services in Makati City.">
  <title>AHBA Development | Business Solutions, Makati City</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Anton&family=Hanken+Grotesk:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap">

  <!-- CSS -->
  <link rel="stylesheet" href="/dashboard/style.css">
</head>
<body>

<canvas id="scene" aria-hidden="true"></canvas>
<div class="scene-fallback" id="fallback" hidden></div>
<div id="tags" aria-hidden="true"></div>

<header class="bar">
  <a class="brand" href="#home" data-go="home" aria-label="AHBA Development, back to top"><img src="/AHBALOGO.png" alt="AHBA Development logo" height="34"></a>
  <a class="brand partner" id="partner" href="/fiber" target="_blank" rel="noopener" aria-label="SkyTruFiber"><img src="/dashboard/skytrufiber.png" alt="SkyTruFiber logo" height="34"></a>
  <nav class="links" aria-label="Main navigation">
    <a href="#services" data-go="services">Services</a>
    <a href="#about" data-go="about">About</a>
    <a href="#management" data-go="management">Management</a>
    <a href="#contact" data-go="contact">Contact</a>
  </nav>
  <button class="theme-btn" id="themeBtn" type="button" aria-label="Switch between light and dark theme">Theme</button>
</header>

<main>
  <section id="home" class="film">
    <div class="wrap film-top">
      <p class="eyebrow">Welcome to</p>
      <h1>A. Halili Business Aid Professional Services Inc.</h1>
    </div>
    <div class="wrap film-bottom">
      <div class="cap swap" id="cap" aria-live="polite">
        <p class="cap-k" id="capK">Established 2003</p>
        <h2 class="cap-t" id="capT">Over 20 years of experience</h2>
        <p class="cap-d" id="capD">Watch what we provide.</p>
        <a class="btn" id="cta" href="#services" data-go="services" hidden>Explore our services</a>
      </div>
      <div class="ctl" id="ctl">
        <div class="rail" id="rail" role="group" aria-label="Services in the film"></div>
        <div class="btns">
          <button class="ghost" id="skip" type="button">Skip</button>
          <button class="ghost" id="replay" type="button">Replay</button>
        </div>
      </div>
    </div>
  </section>

  <section id="services" class="page">
    <div class="wrap">
      <div class="panel right">
        <p class="eyebrow">What we provide</p>
        <h2>Our Services</h2>
        <ul class="svc" id="svc">
          <li tabindex="0"><h3>Recruitment &amp; prescreening</h3><p>Candidates sourced and screened before they reach you.</p></li>
          <li tabindex="0"><h3>Interview processing</h3><p>Interviews arranged and handled end to end.</p></li>
          <li tabindex="0"><h3>Psychological examinations &amp; evaluation</h3><p>Structured assessment to match people to roles.</p></li>
          <li tabindex="0"><h3>Training &amp; development programs</h3><p>Workers prepared for the job before day one.</p></li>
          <li tabindex="0"><h3>Payroll processing</h3><p>Pay runs handled accurately and on schedule.</p></li>
          <li tabindex="0"><h3>Industrial &amp; construction contracting</h3><p>Skilled crews for plants and building sites.</p></li>
          <li tabindex="0"><h3>Full outsourcing solutions</h3><p>One partner to run the functions you hand over.</p></li>
        </ul>
      </div>
    </div>
  </section>

  <section id="about" class="page">
    <div class="wrap">
      <div class="panel wide">
        <p class="eyebrow">About us</p>
        <h2>Providing experienced business solutions</h2>
        <p>With over 20 years of experience in the industry, <b>A. Halili Business Aid Professional Services Inc.</b> (AHBA Development) is committed to delivering high-quality, value-added services nationwide.</p>
        <div class="stats">
          <div class="stat"><strong>20<em>+</em></strong><span>Years experience</span></div>
          <div class="stat"><strong>10,000<em>+</em></strong><span>Workers deployed</span></div>
          <div class="stat"><strong>300<em>+</em></strong><span>Business partners</span></div>
        </div>
        <ol class="timeline">
          <li><time datetime="2003">2003</time><span>Established, with a reputation for reliability and compliance.</span></li>
          <li><time datetime="2015">2015</time><span>Became a licensed and fully compliant service provider.</span></li>
        </ol>
        <p class="statement">Customized service is our core approach, ensuring efficient, scalable operations.</p>
        <dl class="creds">
          <div><dt>SEC Reg.</dt><dd>CS200902226</dd></div>
          <div><dt>DOLE Reg.</dt><dd>NCR-MPFO-72600-5-15-12-016-LR</dd></div>
          <div><dt>TIN</dt><dd>007-246-379-000</dd></div>
        </dl>
      </div>
    </div>
  </section>

  <section id="management" class="page">
    <div class="wrap">
      <div class="panel team-head right"><p class="eyebrow">Management</p><h2>Our Leadership Team</h2></div>
      <div class="team">
        <article class="leader"><div class="frame" aria-hidden="true"><span>CEO</span></div><div class="name-box"><h3>Alex G. Halili</h3><p>Chief Executive Officer</p></div></article>
        <article class="leader"><div class="frame" aria-hidden="true"><span>CFO</span></div><div class="name-box"><h3>Amy A. Halili</h3><p>Chief Financial Officer</p></div></article>
        <article class="leader"><div class="frame" aria-hidden="true"><span>COO</span></div><div class="name-box"><h3>Allec Zandre A. Halili</h3><p>Chief Operating Officer</p></div></article>
      </div>
    </div>
  </section>

  <section id="contact" class="page">
    <div class="wrap">
      <div class="panel">
        <p class="eyebrow">Contact</p>
        <h2>Get in Touch</h2>
        <dl class="contact-list">
          <div><dt>Head Office</dt><dd>2607 Cityland 10 Tower 1, HV Dela Costa Ave., Bel Air, Makati City <a class="maplink" href="https://www.google.com/maps/search/?api=1&amp;query=Cityland+10+Tower+1+HV+Dela+Costa+Ave+Bel+Air+Makati+City" target="_blank" rel="noopener">Open map</a></dd></div>
          <div><dt>Email</dt><dd><span id="emailTxt">admin@ahba.ph</span><button class="copy" type="button" data-copy="admin@ahba.ph" data-target="emailTxt">Copy</button></dd></div>
          <div><dt>Phone</dt><dd><span id="phoneTxt">+63 998 961 5050</span><button class="copy" type="button" data-copy="+63 998 961 5050" data-target="phoneTxt">Copy</button></dd></div>
        </dl>
      </div>
    </div>
  </section>
</main>

<footer>
  <div class="wrap">
    <div class="foot"><span>1454 Newton Street, Barangay San Isidro, Makati City</span><span>ahbadevelopment@ahba.ph</span></div>
  </div>
</footer>

<!-- 3D library: your own copy first, the CDN only if that file is missing -->
<script src="/dashboard/three.min.js"></script>
<script>window.THREE || document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"><\/script>');</script>
<!-- JS -->
<script src="/dashboard/script.js"></script>

</body>
</html>
