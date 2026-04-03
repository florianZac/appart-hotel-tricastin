/**
 * Appart Hôtel Poitiers — Main JS
 * Gère : navbar scroll, scroll-reveal animations, smooth scroll
 */

document.addEventListener('DOMContentLoaded', () => {
    // =============================================
    // NAVBAR : ajout de la classe "scrolled" au scroll
    // =============================================
    const navbar = document.getElementById('mainNav');
    if (navbar) {
        const handleScroll = () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        };
        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll(); // État initial
    }

    // =============================================
    // SCROLL REVEAL : animation des éléments au scroll
    // =============================================
    const revealElements = document.querySelectorAll(
        '.appart-card, .feature-card, .appart-detail-card, .form-card, ' +
        '.intro-text, .intro-gallery, .about-image, .contact-info-card, ' +
        '.info-mini-card'
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
            if (navCollapse.classList.contains('show')) {
                const bsCollapse = bootstrap.Collapse.getInstance(navCollapse);
                if (bsCollapse) bsCollapse.hide();
            }
        });
    });

    // =============================================
    // AUTO-DISMISS FLASH MESSAGES après 5s
    // =============================================
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(flash => {
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
});
