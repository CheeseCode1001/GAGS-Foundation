// ============ GSAP & MOTION SETUP ============
// Wait for all scripts to load
document.addEventListener('DOMContentLoaded', () => {
  // Initialize Lucide icons
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }
  initMobileMenu();
  initHeaderScroll();
  initStaggerHeadings();
  initScrollReveal();
  initCounters();
  initGallery();
  initFAQ();
  initDonationAmounts();
  initSmoothScroll();
});

// ============ MOBILE MENU ============
function initMobileMenu() {
  const hamburger = document.querySelector('.hamburger');
  const mobileMenu = document.querySelector('.mobile-menu');
  if (!hamburger || !mobileMenu) return;

  hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('active');
    mobileMenu.classList.toggle('open');
    document.body.style.overflow = mobileMenu.classList.contains('open') ? 'hidden' : '';
  });

  // Close menu on link click
  mobileMenu.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      hamburger.classList.remove('active');
      mobileMenu.classList.remove('open');
      document.body.style.overflow = '';
    });
  });
}

// ============ HEADER SCROLL ============
function initHeaderScroll() {
  const header = document.querySelector('.site-header');
  if (!header) return;

  window.addEventListener('scroll', () => {
    header.classList.toggle('scrolled', window.scrollY > 50);
  });
}

// ============ TEXT STAGGER ANIMATION (GSAP) ============
function initStaggerHeadings() {
  if (typeof gsap === 'undefined') return;

  // Register ScrollTrigger if available
  if (typeof ScrollTrigger !== 'undefined') {
    gsap.registerPlugin(ScrollTrigger);
  }

  const headings = document.querySelectorAll('h1.stagger-text, h2.stagger-text');

  headings.forEach(heading => {
    // Split text into words, then characters
    const text = heading.textContent;
    heading.innerHTML = '';

    const words = text.split(' ');
    words.forEach((word, wordIndex) => {
      const wordSpan = document.createElement('span');
      wordSpan.classList.add('word-wrap');
      wordSpan.style.display = 'inline-block';
      wordSpan.style.overflow = 'hidden';
      wordSpan.style.verticalAlign = 'top';

      const chars = word.split('');
      chars.forEach(char => {
        const charSpan = document.createElement('span');
        charSpan.classList.add('char');
        charSpan.textContent = char;
        charSpan.style.display = 'inline-block';
        wordSpan.appendChild(charSpan);
      });

      heading.appendChild(wordSpan);

      // Add space between words
      if (wordIndex < words.length - 1) {
        const space = document.createElement('span');
        space.innerHTML = '&nbsp;';
        space.style.display = 'inline-block';
        heading.appendChild(space);
      }
    });

    const allChars = heading.querySelectorAll('.char');

    if (typeof ScrollTrigger !== 'undefined') {
      gsap.fromTo(allChars,
        { y: 50, opacity: 0 },
        {
          y: 0,
          opacity: 1,
          duration: 0.6,
          stagger: 0.03,
          ease: 'power3.out',
          scrollTrigger: {
            trigger: heading,
            start: 'top 85%',
            toggleActions: 'play none none none'
          }
        }
      );
    } else {
      gsap.fromTo(allChars,
        { y: 50, opacity: 0 },
        {
          y: 0,
          opacity: 1,
          duration: 0.6,
          stagger: 0.03,
          ease: 'power3.out',
          delay: 0.3
        }
      );
    }
  });
}

// ============ SCROLL REVEAL ============
function initScrollReveal() {
  if (typeof gsap === 'undefined') return;

  const reveals = document.querySelectorAll('.reveal');

  if (typeof ScrollTrigger !== 'undefined') {
    reveals.forEach(el => {
      gsap.fromTo(el,
        { y: 40, opacity: 0 },
        {
          y: 0,
          opacity: 1,
          duration: 0.8,
          ease: 'power2.out',
          scrollTrigger: {
            trigger: el,
            start: 'top 88%',
            toggleActions: 'play none none none'
          }
        }
      );
    });
  } else {
    // Fallback: IntersectionObserver
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });

    reveals.forEach(el => observer.observe(el));
  }
}

// ============ COUNTER ANIMATION ============
function initCounters() {
  const counters = document.querySelectorAll('.counter');
  if (!counters.length) return;

  const animateCounter = (el) => {
    const target = parseInt(el.getAttribute('data-target'), 10);
    const suffix = el.getAttribute('data-suffix') || '';
    const duration = 2000;
    const start = 0;
    const startTime = performance.now();

    function update(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      // Ease out cubic
      const eased = 1 - Math.pow(1 - progress, 3);
      const current = Math.floor(start + (target - start) * eased);
      el.textContent = current.toLocaleString() + suffix;

      if (progress < 1) {
        requestAnimationFrame(update);
      }
    }
    requestAnimationFrame(update);
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        animateCounter(entry.target);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.3 });

  counters.forEach(counter => observer.observe(counter));
}

// ============ GALLERY LIGHTBOX ============
function initGallery() {
  const galleryItems = document.querySelectorAll('.gallery-item');
  const lightbox = document.querySelector('.lightbox');
  const lightboxImg = document.querySelector('.lightbox img');
  const lightboxClose = document.querySelector('.lightbox-close');

  if (!galleryItems.length || !lightbox) return;

  galleryItems.forEach(item => {
    item.addEventListener('click', () => {
      const img = item.querySelector('img');
      if (img) {
        lightboxImg.src = img.src;
        lightboxImg.alt = img.alt;
        lightbox.classList.add('open');
        document.body.style.overflow = 'hidden';
      }
    });
  });

  if (lightboxClose) {
    lightboxClose.addEventListener('click', closeLightbox);
  }

  lightbox.addEventListener('click', (e) => {
    if (e.target === lightbox) closeLightbox();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeLightbox();
  });

  function closeLightbox() {
    lightbox.classList.remove('open');
    document.body.style.overflow = '';
  }

  // Gallery filters
  const filterBtns = document.querySelectorAll('.gallery-filter-btn');
  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const filter = btn.getAttribute('data-filter');
      galleryItems.forEach(item => {
        const category = item.getAttribute('data-category');
        if (filter === 'all' || category === filter) {
          item.style.display = 'block';
          gsap.fromTo(item, { opacity: 0, scale: 0.9 }, { opacity: 1, scale: 1, duration: 0.4 });
        } else {
          item.style.display = 'none';
        }
      });
    });
  });
}

// ============ FAQ ACCORDION ============
function initFAQ() {
  const faqItems = document.querySelectorAll('.faq-item');
  faqItems.forEach(item => {
    const question = item.querySelector('.faq-question');
    if (!question) return;

    question.addEventListener('click', () => {
      const isOpen = item.classList.contains('open');
      // Close all
      faqItems.forEach(i => i.classList.remove('open'));
      // Toggle current
      if (!isOpen) item.classList.add('open');
    });
  });
}

// ============ DONATION AMOUNT SELECT ============
function initDonationAmounts() {
  const amounts = document.querySelectorAll('.donation-amount');
  const customInput = document.querySelector('.custom-amount-input');

  amounts.forEach(amount => {
    amount.addEventListener('click', () => {
      amounts.forEach(a => a.classList.remove('selected'));
      amount.classList.add('selected');
      if (customInput) {
        customInput.value = amount.getAttribute('data-amount') || '';
      }
    });
  });
}

// ============ SMOOTH SCROLL ============
function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });
}
