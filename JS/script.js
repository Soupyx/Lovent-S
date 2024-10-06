let currentIndex = 0;
const slides = document.querySelectorAll('.my-slide');
const bullets = document.querySelectorAll('.my-bullet');

function updateCarousel() {
    slides.forEach((slide, index) => {
        if (index === currentIndex) {
            slide.style.opacity = 0; // Initialement invisible
            slide.style.display = 'block'; // Assure que la slide est affichée

            // Animation de fade-in
            setTimeout(() => {
                slide.style.transition = 'opacity 0.8s ease'; // Transition pour l'opacité
                slide.style.opacity = 1; // Passe à visible avec fondu
            }, 50); // Délai léger pour laisser la transition s'appliquer
        } else {
            // Si ce n'est pas la slide actuelle, on la fade-out
            slide.style.transition = 'opacity 0.8s ease'; // Transition pour l'opacité
            slide.style.opacity = 0; // Disparait
            setTimeout(() => {
                slide.style.display = 'none'; // Cache complètement après le fade-out
            }, 800); // Délai correspond à la durée de la transition
        }
    });
    // Met à jour les bullets
    bullets.forEach((bullet, index) => {
        bullet.classList.toggle('active', index === currentIndex);
    });
}

function changeSlide(direction) {
    currentIndex = (currentIndex + direction + slides.length) % slides.length;
    updateCarousel();
}

function currentSlide(index) {
    currentIndex = index;
    updateCarousel();
}


