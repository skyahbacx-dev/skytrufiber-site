// Smooth Scroll Function
function smoothScroll(target) {
  const el = document.querySelector(target);
  const y = el.getBoundingClientRect().top + window.pageYOffset - 150;
  window.scrollTo({ top: y, behavior: "smooth" });
}

// Reveal Animation - One Time Only
window.addEventListener("scroll", () => {
  document.querySelectorAll(".reveal").forEach((el) => {
    if (el.getBoundingClientRect().top < window.innerHeight - 10 && !el.classList.contains("active")) {
      el.classList.add("active");
    }
  });
});

// Stats Counter Animation
const counters = document.querySelectorAll(".counter");
const speed = 250;

const runCounter = () => {
  counters.forEach((counter) => {
    const update = () => {
      const target = +counter.getAttribute("data-target");
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

window.addEventListener("scroll", () => {
  const statsPosition = document.querySelector(".stats-row").getBoundingClientRect().top;
  if (statsPosition < window.innerHeight - 80) runCounter();
});

/* ------------------ SLIDESHOW / GALLERY ------------------ */
let slideIndex = 0;
const slides = document.querySelectorAll(".slide");
const dots = document.querySelectorAll(".dot");

function showSlide(n) {
  slides.forEach((slide) => slide.classList.remove("active"));
  dots.forEach((dot) => dot.classList.remove("active"));

  slideIndex = (n + slides.length) % slides.length;
  slides[slideIndex].classList.add("active");
  dots[slideIndex].classList.add("active");
}

function changeSlide(step) {
  showSlide(slideIndex + step);
}

function goToSlide(n) {
  showSlide(n);
}

setInterval(() => {
  changeSlide(1);
}, 3500);

showSlide(slideIndex);

/* ------------------ FULLSCREEN MODAL VIEWER ------------------ */
let modal;

function openModal(img) {
  if (!modal) {
    modal = document.createElement("div");
    modal.id = "modal";
    modal.innerHTML = `<span onclick="closeModal()">&times;</span><img src="${img.src}">`;
    document.body.appendChild(modal);
  } else {
    modal.querySelector("img").src = img.src;
  }
  modal.style.display = "flex";
}

function closeModal() {
  modal.style.display = "none";
}

window.addEventListener("click", (e) => {
  if (modal && e.target === modal) closeModal();
});
