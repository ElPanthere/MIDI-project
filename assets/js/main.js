
// v9.2 theme init + animated toggle
(function(){
  const root = document.documentElement;
  const setTheme = (t) => root.setAttribute('data-theme', (t === 'light' ? 'light' : 'dark'));
  const getTheme = () => root.getAttribute('data-theme') || 'dark';
  // Init: prefer stored, else dark
  let stored = localStorage.getItem('theme');
  if (stored !== 'light' && stored !== 'dark') stored = null;
  if (stored) setTheme(stored); else setTheme('dark');

  const updateIcons = () => {
    const btn = document.querySelector('.theme-toggle');
    if (!btn) return;
    // icons crossfade handled by CSS; nothing to do here
  };

  document.addEventListener('DOMContentLoaded', updateIcons);

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.theme-toggle');
    if (!btn) return;
    // Animated swap
    root.classList.add('thematize'); // avoid double transitions
    const next = getTheme() === 'light' ? 'dark' : 'light';
    setTheme(next);
    localStorage.setItem('theme', next);
    // allow CSS transitions to run
    setTimeout(() => { root.classList.remove('thematize'); updateIcons(); }, 10);
  });
})();


// ====== Simple interactivity: mobile nav, smooth scroll, faq toggles, form ======
document.addEventListener('DOMContentLoaded', () => {
  // Smooth scroll
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const id = a.getAttribute('href');
      if (id.length > 1) {
        e.preventDefault();
        document.querySelector(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // FAQ toggles
  document.querySelectorAll('.faq-item button').forEach(btn => {
    btn.addEventListener('click', () => {
      const p = btn.parentElement.querySelector('p');
      const expanded = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', String(!expanded));
      p.style.display = expanded ? 'none' : 'block';
    });
  });

  // Contact form (client-side)
  const form = document.querySelector('form#contact-form');
  if (form) {
    form.addEventListener('submit', (e) => {
      const consent = form.querySelector('#consent');
      if (consent && !consent.checked) {
        e.preventDefault();
        alert('Veuillez accepter la politique de confidentialité (RGPD).');
        return;
      }
    });
  }
});

// ===== v6: mobile nav + reveal on scroll =====
document.addEventListener('DOMContentLoaded', () => {
  // Mobile nav
  const toggle = document.querySelector('.nav-toggle');
  const links = document.querySelector('.nav-links');
  if (toggle && links) {
    toggle.addEventListener('click', () => {
      links.classList.toggle('open');
      toggle.setAttribute('aria-expanded', links.classList.contains('open'));
    });
  }

  // Reveal on scroll (respect reduced motion)
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!reduce && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.classList.add('in-view');
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.12 });
    document.querySelectorAll('.reveal').forEach(el => io.observe(el));
  } else {
    document.querySelectorAll('.reveal').forEach(el => el.classList.add('in-view'));
  }
});




// ===== v8: extra parallax layers =====
document.addEventListener('DOMContentLoaded', () => {
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const hero = document.querySelector('.hero-parallax');
  if (!hero || reduce) return;
  // layers already in DOM? ensure exist
  const stars = hero.querySelector('.hero-stars') || hero.appendChild(Object.assign(document.createElement('div'), {className:'hero-stars'}));
  const dots  = hero.querySelector('.hero-dots')  || hero.appendChild(Object.assign(document.createElement('div'), {className:'hero-dots'}));
  const grid  = hero.querySelector('.hero-grid');
  const layer = hero.querySelector('.hero-layer');
  const onScroll = () => {
    const y = window.scrollY || document.documentElement.scrollTop;
    if (layer) layer.style.transform = `translateY(${Math.min(80, y * 0.18)}px)`;
    if (grid)  grid.style.transform  = `translateY(${Math.min(60, y * 0.10)}px)`;
    stars.style.transform = `translateY(${Math.min(40, y * 0.06)}px)`;
    dots.style.transform  = `translateY(${Math.min(30, y * 0.04)}px)`;
  };
  document.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
});

// ===== v8: Inline validation + shake =====
document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('form#contact-form');
  if (!form) return;
  const fields = ['name','email','message'];
  const get = id => form.querySelector('#'+id);

  const ensureErrorNode = (el) => {
    let n = el.parentElement.querySelector('.input-error');
    if (!n) { n = document.createElement('div'); n.className = 'input-error'; el.parentElement.appendChild(n); }
    return n;
  };

  const validate = () => {
    let ok = true;
    fields.forEach(id => {
      const el = get(id);
      if (!el) return;
      let good = !!el.value.trim();
      if (id === 'email') good = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value.trim());
      const err = ensureErrorNode(el);
      if (!good) {
        ok = false;
        el.classList.add('invalid'); el.classList.remove('valid');
        err.textContent = id === 'email' ? 'Email invalide' : 'Champ requis';
      } else {
        el.classList.remove('invalid'); el.classList.add('valid');
        err.textContent = '';
      }
    });
    return ok;
  };

  fields.forEach(id => get(id)?.addEventListener('input', validate));
  form.addEventListener('submit', (e) => {
    if (!validate()) {
      e.preventDefault();
      form.classList.remove('shake');
      // force reflow to restart animation
      void form.offsetWidth;
      form.classList.add('shake');
    }
  });
});







// v9.2 functional theme toggle with smooth icon swap
document.addEventListener('DOMContentLoaded', () => {
  const root = document.documentElement;
  const btn = document.querySelector('.theme-toggle');
  if (!btn) return;
  // Init default
  let stored = localStorage.getItem('theme');
  if (stored !== 'light' && stored !== 'dark') stored = null;
  if (stored) root.setAttribute('data-theme', stored);
  else root.setAttribute('data-theme', 'dark');
  const updateIcon = () => {
    const t = root.getAttribute('data-theme') || 'dark';
    btn.querySelector('.theme-icon').textContent = t === 'light' ? '☀️' : '🌙';
  };
  updateIcon();
  // Toggle click
  btn.addEventListener('click', () => {
    btn.classList.add('switching');
    setTimeout(() => {
      const current = root.getAttribute('data-theme') || 'dark';
      const next = current === 'light' ? 'dark' : 'light';
      root.setAttribute('data-theme', next);
      localStorage.setItem('theme', next);
      updateIcon();
      btn.classList.remove('switching');
    }, 150);
  });
});
