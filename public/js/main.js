/**
 * Appart Hôtel Tricastin — Main JS
 * Gère : navbar scroll, scroll-reveal animations, smooth scroll
 */

document.addEventListener('DOMContentLoaded', () => {
  // =============================================
  // NAVBAR : ajout de la classe "scrolled" au scroll
  // =============================================
  const navbar = document.getElementById('mainNav');
  if (navbar) {
    const handleScroll = () => {
      navbar.classList.toggle('scrolled', window.scrollY > 50);
    };
    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll();// État initial
  } 

  // =============================================
  // SCROLL REVEAL : animation des éléments au scroll
  // =============================================
    const revealElements = document.querySelectorAll(
      '.appart-card, .feature-card, .appart-detail-card, .form-card, ' +
      '.intro-text, .intro-gallery, .about-image, .contact-info-card, ' +
      '.info-mini-card, .stats-banner-item'
    );

    if (revealElements.length > 0 && 'IntersectionObserver' in window) {
      const revealObserver = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              entry.target.style.opacity = '1';
              entry.target.style.transform = 'translateY(0)';
              revealObserver.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.1, rootMargin: '0px 0px -50px 0px' }
      );
      revealElements.forEach((el, index) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = `opacity 0.6s ease ${index * 0.05}s, transform 0.6s ease ${index * 0.05}s`;
        revealObserver.observe(el);
      });
  }
  // =============================================
  // FERMETURE NAVBAR MOBILE au clic sur un lien
  // =============================================
  const navLinks = document.querySelectorAll('#navbarNav .nav-link');
  const navCollapse = document.getElementById('navbarNav');

  navLinks.forEach(link => {
    link.addEventListener('click', () => {
      if (navCollapse && navCollapse.classList.contains('show')) {
        const bsCollapse = bootstrap.Collapse.getInstance(navCollapse);
        if (bsCollapse) bsCollapse.hide();
      }
    });
  });

  // =============================================
  // AUTO-DISMISS FLASH MESSAGES après 5s
  // =============================================
  document.querySelectorAll('.flash-message').forEach(flash => {
    setTimeout(() => {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(flash);
      bsAlert.close();
    }, 5000);
  });


  // =============================================
  // SMOOTH SCROLL pour les ancres internes
  // =============================================
  document.querySelectorAll('a[href*="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
    const href = this.getAttribute('href');
    // Ne traiter que les ancres sur la même page
      if (href.startsWith('#') || href.includes(window.location.pathname + '#')) {
        const hash = href.includes('#') ? '#' + href.split('#')[1] : href;
        const target = document.querySelector(hash);
        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    });
  });

  // =============================================
  // CARROUSEL TEMOIGNAGES MULTI-CARDS
  // =============================================
  const track = document.getElementById('testimonialTrack');
  const prevBtn = document.getElementById('carouselPrev');
  const nextBtn = document.getElementById('carouselNext');

  if (track && prevBtn && nextBtn) {
    let currentIndex = 0;
    const cards = track.querySelectorAll('.testimonial-card-multi');
    const totalCards = cards.length;

    // Nombre de cards visibles
    function getVisibleCount() {
      if (window.innerWidth < 576) return 1;
      if (window.innerWidth < 992) return 2;
      return 4;
    }

    function getMaxIndex() {
      return Math.max(0, totalCards - getVisibleCount());
    }

    function updatePosition() {
      if (cards.length === 0) return;
      const card = cards[0];
      const gap = 20;
      const cardWidth = card.offsetWidth + gap;
      track.style.transform = 'translateX(-' + (currentIndex * cardWidth) + 'px)';
    }

    nextBtn.addEventListener('click', function () {
      currentIndex = currentIndex < getMaxIndex() ? currentIndex + 1 : 0;
      updatePosition();
    });

    prevBtn.addEventListener('click', function () {
      currentIndex = currentIndex > 0 ? currentIndex - 1 : getMaxIndex();
      updatePosition();
    });

    window.addEventListener('resize', function () {
      if (currentIndex > getMaxIndex()) {
        currentIndex = getMaxIndex();
      }
      updatePosition();
    });

    // Auto-scroll
    setInterval(function () {
      currentIndex = currentIndex < getMaxIndex() ? currentIndex + 1 : 0;
      updatePosition();
    }, 4000);
  }

});

