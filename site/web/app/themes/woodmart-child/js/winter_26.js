document.addEventListener('DOMContentLoaded', function () {
  const emblaRoot =
    document.querySelector('#monk-embla') ||
    document.querySelector('#monk_slider .embla') ||
    document.querySelector('.embla');

  if (!emblaRoot) return;

  const viewport = emblaRoot.querySelector('.embla__viewport') || emblaRoot;

  // --- Plugins ---
  const classNames = EmblaCarouselClassNames({
    snapped: 'is-snapped',
    inView: 'is-in-view',
    dragging: 'is-dragging',
    loop: 'is-loop',
    draggable: 'is-draggable',
  });

  const fade = (typeof EmblaCarouselFade === 'function') ? EmblaCarouselFade() : null;

  const embla = EmblaCarousel(
    viewport,
    {
      loop: true,
      draggable: false, // προαιρετικό για "καθαρό" fade
    },
    fade ? [classNames, fade] : [classNames]
  );

  // -----------------------
  // GSAP timelines per slide
  // -----------------------
  const tls = new Map();

  function getAnimTargets(slideEl) {
    const a = slideEl.querySelector('.img-a');
    const b = slideEl.querySelector('.img-b');
    const agili = slideEl.querySelector('.agili');
    const slice_off = slideEl.querySelector('.slice_off');
    const imgs = slideEl.querySelectorAll('img');
    const first = a || imgs[0] || null;
    const second = b || imgs[1] || null;
    return { first, second, agili, slice_off };
  }

  function createTimeline(slideEl) {
    const { first, second, agili, slice_off } = getAnimTargets(slideEl);
    if (!first) return null;

    gsap.set(first, { opacity: 0, x: -80, willChange: 'transform, opacity' });
    if (second) gsap.set(second, { opacity: 0, x: 80, willChange: 'transform, opacity' });
    if (agili) gsap.set(agili, { opacity: 0, y: 40, willChange: 'transform, opacity' });
    if (slice_off) gsap.set(slice_off, { opacity: 0, scale: 0, transformOrigin: '50% 50%', willChange: 'transform, opacity' });

    const tl = gsap.timeline({ paused: true, defaults: { ease: 'power3.out' } });
    tl.to(first, { opacity: 1, x: 0, duration: 0.55 }, 0);
    if (second) tl.to(second, { opacity: 1, x: 0, duration: 0.65 }, 0.1);
    if (agili) tl.to(agili, { opacity: 1, y: 0, duration: 0.55 }, 0.2);
    if (slice_off) tl.to(slice_off, { opacity: 1, scale: 1, duration: 0.7, ease: 'elastic.out(1, 0.5)' }, 0.25);

    return tl;
  }

  function ensureTimelines() {
    embla.slideNodes().forEach((slideEl) => {
      if (!tls.has(slideEl)) {
        const tl = createTimeline(slideEl);
        if (tl) tls.set(slideEl, tl);
      }
    });
  }

  function playIn(index) {
    const slideEl = embla.slideNodes()[index];
    const tl = tls.get(slideEl);
    if (!tl) return;
    tl.timeScale(1).play(0);
  }

  function reverseOut(index) {
    const slideEl = embla.slideNodes()[index];
    const tl = tls.get(slideEl);
    if (!tl) return Promise.resolve();

    // Αν είναι ήδη στο 0 (π.χ. δεν έχει παιχτεί ακόμα), μην καθυστερείς άσκοπα
    if (tl.progress() === 0) return Promise.resolve();

    return new Promise((resolve) => {
      // καθάρισε προηγούμενο callback για σιγουριά
      tl.eventCallback('onReverseComplete', null);

      tl.eventCallback('onReverseComplete', () => {
        tl.eventCallback('onReverseComplete', null);
        resolve();
      });

      tl.timeScale(1.2).reverse();
    });
  }

  // -----------------------
  // NAV: arrows + dots
  // -----------------------
  const prevBtn = emblaRoot.querySelector('.embla__prev');
  const nextBtn = emblaRoot.querySelector('.embla__next');
  const dotsRoot = emblaRoot.querySelector('.embla__dots');

  function buildDots() {
    if (!dotsRoot) return [];
    dotsRoot.innerHTML = '';
    return embla.scrollSnapList().map((_, i) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'embla__dot';
      btn.setAttribute('aria-label', `Go to slide ${i + 1}`);
      dotsRoot.appendChild(btn);
      return btn;
    });
  }

  function updateDots(dots) {
    if (!dots || !dots.length) return;
    const selected = embla.selectedScrollSnap();
    dots.forEach((dot, i) => dot.classList.toggle('is-active', i === selected));
  }

  // -----------------------
  // Controlled transitions (reverse -> embla scroll -> playIn)
  // -----------------------
  let isTransitioning = false;
  let prevIndex = embla.selectedScrollSnap();
  let dots = [];

  async function goTo(action) {
    if (isTransitioning) return;
    isTransitioning = true;

    // 1) reverse current (και ΜΟΝΟ μετά scroll, άρα το fade ξεκινά μετά το reverse)
    await reverseOut(prevIndex);

    // 2) τώρα κάνε embla scroll (αυτό ξεκινά το fade)
    action();

    // 3) περιμένουμε το select (όταν “κλειδώσει” το νέο snap)
    //    και εκεί θα κάνουμε playIn + unlock.
  }

  function onSelect() {
    const index = embla.selectedScrollSnap();
    prevIndex = index;

    playIn(index);
    updateDots(dots);

    isTransitioning = false;

    // autoplay next schedule (δες πιο κάτω)
    scheduleNext();
  }

  embla.on('select', onSelect);

  // Arrows
  if (prevBtn) prevBtn.addEventListener('click', (e) => {
    e.preventDefault();
    goTo(() => embla.scrollPrev());
    stopAutoplay();
  });

  if (nextBtn) nextBtn.addEventListener('click', (e) => {
    e.preventDefault();
    goTo(() => embla.scrollNext());
    stopAutoplay();
  });

  // Dots
  dots = buildDots();
  dots.forEach((btn, i) => {
    btn.addEventListener('click', () => {
      if (i === embla.selectedScrollSnap()) return;
      goTo(() => embla.scrollTo(i));
      stopAutoplay();
    });
  });

  // -----------------------
  // Autoplay (controlled)
  // -----------------------
  const AUTOPLAY_DELAY = 2000;
  let autoplayTimer = null;
  let autoplayEnabled = true;

  function stopAutoplay() {
    autoplayEnabled = false;           // αν θες να σταματά μόνιμα μετά από interaction
    if (autoplayTimer) clearTimeout(autoplayTimer);
    autoplayTimer = null;
  }

  function scheduleNext() {
    if (!autoplayEnabled) return;
    if (autoplayTimer) clearTimeout(autoplayTimer);

    autoplayTimer = setTimeout(() => {
      goTo(() => embla.scrollNext());
    }, AUTOPLAY_DELAY);
  }

  // -----------------------
  // Init changes + reInit handling
  // -----------------------
  ensureTimelines();
  playIn(prevIndex);
  updateDots(dots);
  scheduleNext();

  embla.on('reInit', () => {
    ensureTimelines();
    dots = buildDots();
    prevIndex = embla.selectedScrollSnap();
    playIn(prevIndex);
    updateDots(dots);
    scheduleNext();
  });
});