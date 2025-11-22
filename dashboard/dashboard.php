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
    <a href="/#home" onclick="window.scrollTo({top:0, behavior:'smooth'}); return false;">
      <img src="AHBALOGO.png" height="72">
    </a>
    <a href="/SKYTRUFIBER/skytrufiber.php" target="_blank">
      <img src="SKYTRUFIBER.png" height="60">
    </a>
  </div>

  <nav>
    <a href="/#home">HOME</a>
    <a href="/#about">ABOUT</a>
    <a href="/#services">SERVICES</a>
    <a href="/#management">MANAGEMENT</a>
    <a href="/#gallery">GALLERY</a>
    <a href="/#contact">CONTACT</a>
  </nav>
</header>

<section id="home" class="section">
  <div class="hero-content reveal">
    <p>Welcome to</p>
    <h1>A.Halili Business Aid Professional Services Inc.</h1>
    <p>All-in-one business solutions</p>
    <a href="javascript:void(0)" onclick="smoothScroll('#experience')" class="btn">Learn More</a>
  </div>
</section>

<section id="experience" class="section reveal experience-section">

  <div class="exp-box reveal">
    <div class="exp-title"><span class="arrow">→</span>
      <h2>Providing experienced<br>business solutions</h2>
    </div>
    <p>
      With over 20 years of experience in the industry,
      <b>A.Halili Business Aid Professional Services Inc.</b> with the registered trademark of
      <b>AHBA Development</b>, is committed to delivering high-quality value-added services nationwide.
      SEC Reg. <b>CS200902226</b> | DOLE Reg.<b> NCR-MPFO-72600-5-15-12-016-LR</b> | TIN <b>007-246-379-000</b>
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

<section id="about" class="section reveal">
  <div class="about-container">
    <h2>About Us</h2>
    <p>
      Established in 2003, A. HALILI BUSINESS AID PROFESSIONAL SERVICES INC. is a vast company that provides
      manpower support in need of their client. It was subsequently incorporated on February 2009. Business Aid
      believes that besides thorough screening and hiring qualified individual for job vacancies, there must be proper
      training, motivation and evaluation just to assure that the client would get the best value and performance out of their personnel.
    </p>
    <p>
      In 2015, the company became a Licensed and compliant service provider and/or contractor under the DOLE Department Order 18-A.
      And in 2017, the company also a compliant under the DOLE Department Order No.174.
    </p>
    <p>
      Customized service is our basic approach — making operations simpler yet efficient.
    </p>
  </div>
</section>

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
    <img src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=600&q=80">
  </div>
</section>

<section id="management" class="section reveal team-section">
  <h2 class="team-title">Our Leadership Team</h2>
  <div class="team-card reveal"><img src="alex.jpg" class="team-photo"><div><h3>Alex G. Halili, CEO</h3><p>...</p></div></div>
  <div class="team-card reveal"><img src="amy.jpg" class="team-photo"><div><h3>Amy A. Halili, CFO</h3><p>...</p></div></div>
  <div class="team-card reveal"><img src="allec.jpg" class="team-photo"><div><h3>Allec Zandre A. Halili, COO</h3><p>...</p></div></div>
</section>

<section id="contact" class="section reveal contact-section">
  <div class="contact-box reveal">
    <div class="contact-title"><span class="arrow">→</span><h2>Get in Touch</h2></div>

    <div class="contact-info">
      <div class="contact-item"><div class="icon-circle">📍</div><div><h4>Head Office</h4>2607 Cityland 10 Tower 1 HV Dela Costa Ave., Bel Air, Makati City</div></div>
      <div class="contact-item"><div class="icon-circle">✉️</div><div><h4>Email</h4><a href="mailto:admin@ahba.ph">admin@ahba.ph</a></div></div>
      <div class="contact-item"><div class="icon-circle">📞</div><div><h4>Phone</h4><a href="tel:+639989615050">+63 998 961 5050</a></div></div>
    </div>
  </div>

  <div class="map-container reveal">
    <iframe width="100%" height="350" style="border:0;" loading="lazy" allowfullscreen
      src="https://maps.google.com/maps?q=Cityland%2010%20Tower%201%20HV%20Dela%20Costa%20Ave%20Bel%20Air%20Makati%20City&t=m&z=17&output=embed&iwloc=near"></iframe>
  </div>
</section>

<footer>
  📍 1454 Newton Street, Barangay San Isidro, Makati City | ✉️ ahbadevelopment@ahba.ph
</footer>

<script>
// Smooth scroll for Learn More
function smoothScroll(target) {
  const el = document.querySelector(target);
  window.scrollTo({
    top: el.offsetTop - 150,
    behavior: 'smooth'
  });
}

// One-time Reveal Animation
window.addEventListener("scroll", () => {
  document.querySelectorAll(".reveal").forEach(el => {
    const top = el.getBoundingClientRect().top;
    if (top < window.innerHeight - 10 && !el.classList.contains("active")) {
      el.classList.add("active");
    }
  });
});

// Counter Animation
const counters = document.querySelectorAll('.counter');
const speed = 250;

const runCounter = () => {
  counters.forEach(counter => {
    const update = () => {
      const target = +counter.getAttribute('data-target');
      const count = +counter.innerText;
      const increment = target / speed;

      if (count < target) {
        counter.innerText = Math.ceil(count + increment);
        setTimeout(update, 20);
      } else {
        counter.innerText = target.toLocaleString();
      }
    };
    update();
  });
};

window.addEventListener('scroll', () => {
  const statsPosition = document.querySelector('.stats-row').getBoundingClientRect().top;
  if (statsPosition < window.innerHeight - 80) {
    runCounter();
  }
});
</script>

</body>
</html>
