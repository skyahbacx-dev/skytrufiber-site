/* ================= SMOOTH SCROLL ================= */

function smoothScroll(target) {
  const el = document.querySelector(target);
  if (!el) return;

  const headerOffset = 140;
  const y = el.getBoundingClientRect().top + window.pageYOffset - headerOffset;

  window.scrollTo({
    top: y,
    behavior: "smooth"
  });
}

/* ================= MOBILE HAMBURGER ================= */

function toggleMenu() {
  const nav = document.getElementById("mobileNav");
  if (!nav) return;
  nav.classList.toggle("open");
}

/* Close mobile nav on resize */
window.addEventListener("resize", () => {
  if (window.innerWidth > 768) {
    const nav = document.getElementById("mobileNav");
    if (nav) nav.classList.remove("open");
  }
});

/* ================= REVEAL ANIMATION ================= */

const revealElements = document.querySelectorAll(".reveal");

function revealOnScroll() {
  revealElements.forEach(el => {
    if (el.classList.contains("active")) return;

    const rect = el.getBoundingClientRect();
    if (rect.top < window.innerHeight - 80) {
      el.classList.add("active");
    }
  });
}

window.addEventListener("scroll", revealOnScroll);
window.addEventListener("load", revealOnScroll);

/* ================= STATS COUNTER ================= */

const counters = document.querySelectorAll(".counter");
let countersStarted = false;

function runCounters() {
  if (countersStarted) return;
  countersStarted = true;

  counters.forEach(counter => {
    const target = +counter.getAttribute("data-target");
    const speed = 200;
    let count = 0;

    const increment = target / speed;

    const update = () => {
      count += increment;
      if (count < target) {
        counter.innerText = Math.ceil(count).toLocaleString();
        requestAnimationFrame(update);
      } else {
        counter.innerText = target.toLocaleString();
      }
    };

    update();
  });
}

window.addEventListener("scroll", () => {
  const stats = document.querySelector(".stats-row");
  if (!stats) return;

  const rect = stats.getBoundingClientRect();
  if (rect.top < window.innerHeight - 100) {
    runCounters();
  }
});

/* ================= SLIDESHOW / GALLERY ================= */

let slideIndex = 0;
let slideTimer;

const slides = document.querySelectorAll(".slide");
const dots = document.querySelectorAll(".dot");

function showSlide(n) {
  if (!slides.length) return;

  slides.forEach(slide => slide.classList.remove("active"));
  dots.forEach(dot => dot.classList.remove("active"));

  slideIndex = (n + slides.length) % slides.length;

  slides[slideIndex].classList.add("active");
  if (dots[slideIndex]) dots[slideIndex].classList.add("active");
}

function changeSlide(step) {
  showSlide(slideIndex + step);
  resetSlideTimer();
}

function goToSlide(n) {
  showSlide(n);
  resetSlideTimer();
}

function startSlideTimer() {
  slideTimer = setInterval(() => {
    changeSlide(1);
  }, 4000);
}

function resetSlideTimer() {
  clearInterval(slideTimer);
  startSlideTimer();
}

if (slides.length) {
  showSlide(slideIndex);
  startSlideTimer();
}

/* ================= FULLSCREEN MODAL ================= */

let modal = null;

function openModal(img) {
  if (!img) return;

  if (!modal) {
    modal = document.createElement("div");
    modal.id = "modal";
    modal.innerHTML = `
      <span id="modalClose">&times;</span>
      <img src="${img.src}">
    `;
    document.body.appendChild(modal);

    modal.addEventListener("click", e => {
      if (e.target === modal) closeModal();
    });

    document.getElementById("modalClose").addEventListener("click", closeModal);
  } else {
    modal.querySelector("img").src = img.src;
  }

  modal.style.display = "flex";
}

function closeModal() {
  if (modal) modal.style.display = "none";
}

/* Close modal on ESC */
window.addEventListener("keydown", e => {
  if (e.key === "Escape") closeModal();
});
