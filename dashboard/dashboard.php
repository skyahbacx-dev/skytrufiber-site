<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="/AHBALOGO.png">
  <link rel="apple-touch-icon" href="/AHBALOGO.png">
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
  <a class="brand partner" id="partner" href="https://customer.ahbadevt.com" target="_blank" rel="noopener">
    <img src="/SKYTRUFIBER.png" height="60" alt="SkyTruFiber">
  </a>
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
          <div class="stat"><strong>1,000<em>+</em></strong><span>Workers deployed</span></div>
          <div class="stat"><strong>Multiple</strong><span>Business partners</span></div>
        </div>
        <ol class="timeline">
          <li><time datetime="2003">2003</time><span>Established, beginning our commitment to reliable business solutions.</span></li>
          <li><time datetime="2009">2009</time><span>Incorporated as A. Halili Business Aid Professional Services Inc.</span></li>
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
      <div class="contact-layout">
        <div class="panel contact-panel">
          <p class="eyebrow">Contact</p>
          <h2>Get in Touch</h2>
          <p class="contact-intro">We'd love to hear from you. Visit our head office or get in touch with our team.</p>
          <dl class="contact-list">
            <div><dt>Head Office</dt><dd>2F 3497 Honda St., Brgy. Pinagkaisahan, Makati City <a class="maplink" href="https://www.google.com/maps/search/?api=1&amp;query=2F+3497+Honda+St+Brgy+Pinagkaisahan+Makati+City" target="_blank" rel="noopener">Get directions ↗</a></dd></div>
            <div><dt>Email</dt><dd><span id="emailTxt">admin@ahba.ph</span><button class="copy" type="button" data-copy="admin@ahba.ph" data-target="emailTxt">Copy</button></dd></div>
            <div><dt>Phone</dt><dd><span id="phoneTxt">+63 998 961 5050</span><button class="copy" type="button" data-copy="+63 998 961 5050" data-target="phoneTxt">Copy</button></dd></div>
          </dl>
        </div>
        <aside class="map-card" aria-label="Map showing AHBA Development head office location">
          <div class="map-card-top"><span class="map-pin">⌖</span><div><strong>Find our office</strong><small>Makati City, Philippines</small></div><span class="map-live"><i></i> LOCATION</span></div>
          <div class="map-frame">
            <iframe title="Map to 2F 3497 Honda Street, Barangay Pinagkaisahan, Makati City" src="https://maps.google.com/maps?q=2F%203497%20Honda%20St.%2C%20Brgy.%20Pinagkaisahan%2C%20Makati%20City&t=&z=16&ie=UTF8&iwloc=&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            <div class="map-float-label"><span>AHBA</span><small>Head Office</small></div>
          </div>
          <div class="map-card-bottom"><div><strong>2F 3497 Honda St.</strong><small>Brgy. Pinagkaisahan, Makati City</small></div><a href="https://www.google.com/maps/search/?api=1&amp;query=2F+3497+Honda+St+Brgy+Pinagkaisahan+Makati+City" target="_blank" rel="noopener" aria-label="Open office location in Google Maps">Open in Maps <span>↗</span></a></div>
        </aside>
      </div>
    </div>
  </section>
</main>

<footer>
  <div class="wrap">
    <div class="foot"><span>ahbadevelopment@ahba.ph</span></div>
  </div>
</footer>

<!-- Keep navigation responsive while the optional 3D library loads. -->
<script>
(function () {
  var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  document.querySelectorAll('[data-go]').forEach(function (link) {
    link.addEventListener('click', function (event) {
      var target = document.getElementById(link.getAttribute('data-go'));
      if (!target) return;
      event.preventDefault();
      target.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'start' });
    });
  });
})();
</script>

<!-- Load Three.js without document.write (which browsers may block after parsing). -->
<script>
(function () {
  var sources = [
    'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js',
    'https://cdn.jsdelivr.net/npm/three@0.128.0/build/three.min.js'
  ];
  var index = 0;

  function loadApp() {
    var app = document.createElement('script');
    app.src = '/dashboard/script.js';
    app.async = false;
    document.body.appendChild(app);
  }

  function loadThree() {
    if (window.THREE) {
      loadApp();
      return;
    }
    if (index >= sources.length) {
      console.error('AHBA: Three.js could not be loaded from either CDN. Starting the built-in fallback.');
      loadApp();
      return;
    }

    var library = document.createElement('script');
    library.src = sources[index++];
    library.async = true;
    library.onload = function () {
      if (window.THREE) loadApp();
      else loadThree();
    };
    library.onerror = loadThree;
    document.head.appendChild(library);
  }

  loadThree();
})();
</script>

</body>
</html>
