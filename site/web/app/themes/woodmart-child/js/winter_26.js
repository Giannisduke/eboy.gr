document.addEventListener('DOMContentLoaded', function () {
  const emblaRoot =
    document.querySelector('#monk-embla') ||
    document.querySelector('#monk_slider .embla') ||
    document.querySelector('.embla');

  if (!emblaRoot) return;

  const viewport = emblaRoot.querySelector('.embla__viewport') || emblaRoot;

  // --- Embla plugins ---
  const autoplay = EmblaCarouselAutoplay({
    delay: 7000,
    stopOnInteraction: false,
    playOnInit: true,
  });

  const classNames = EmblaCarouselClassNames({
    snapped: 'is-snapped',
    inView: 'is-in-view',
    dragging: 'is-dragging',
    loop: 'is-loop',
    draggable: 'is-draggable',
  });

  // --- Embla init ---
  const embla = EmblaCarousel(
    viewport,
    { loop: true, align: 'center', duration: 35 },
    [autoplay, classNames]
  );

  // -----------------------
  // GSAP: per-slide timelines
  // -----------------------
  const tls = new Map();

  function getAnimTargets(slideEl) {
    const a = slideEl.querySelector('.img-a');
    const b = slideEl.querySelector('.img-b');

    const imgs = slideEl.querySelectorAll('img');
    const first = a || imgs[0] || null;
    const second = b || imgs[1] || null;

    return { first, second };
  }

  function createTimeline(slideEl) {
    const { first, second } = getAnimTargets(slideEl);
    if (!first) return null;

    gsap.killTweensOf([first, second].filter(Boolean));

    // initial hidden/off
    gsap.set(first, { opacity: 0, x: -140, willChange: 'transform, opacity' });
    if (second) gsap.set(second, { opacity: 0, x: 140, willChange: 'transform, opacity' });

    const tl = gsap.timeline({ paused: true, defaults: { ease: 'power3.out' } });
    tl.to(first, { opacity: 1, x: 0, duration: 0.6 }, 0);

    if (second) {
      tl.to(second, { opacity: 1, x: 0, duration: 0.7 }, 0.12);
    }

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

  function playOut(index) {
    const slideEl = embla.slideNodes()[index];
    const tl = tls.get(slideEl);
    if (!tl) return;
    tl.timeScale(1.2).reverse();
  }

  // -----------------------
  // NAV: arrows + dots
  // -----------------------
  const prevBtn = emblaRoot.querySelector('.embla__prev');
  const nextBtn = emblaRoot.querySelector('.embla__next');
  const dotsRoot = emblaRoot.querySelector('.embla__dots');

  function setupArrows() {
    if (prevBtn) {
      prevBtn.addEventListener('click', (e) => {
        e.preventDefault();
        embla.scrollPrev();
      });
    }
    if (nextBtn) {
      nextBtn.addEventListener('click', (e) => {
        e.preventDefault();
        embla.scrollNext();
      });
    }
  }

  function buildDots() {
    if (!dotsRoot) return [];
    dotsRoot.innerHTML = '';
    return embla.scrollSnapList().map((_, i) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'embla__dot';
      btn.setAttribute('aria-label', `Go to slide ${i + 1}`);
      btn.addEventListener('click', () => embla.scrollTo(i));
      dotsRoot.appendChild(btn);
      return btn;
    });
  }

  function updateDots(dots) {
    if (!dots || !dots.length) return;
    const selected = embla.selectedScrollSnap();
    dots.forEach((dot, i) => dot.classList.toggle('is-active', i === selected));
  }

  function updateArrows() {
    if (!prevBtn || !nextBtn) return;
    if (embla.options.loop) {
      prevBtn.disabled = false;
      nextBtn.disabled = false;
    } else {
      prevBtn.disabled = !embla.canScrollPrev();
      nextBtn.disabled = !embla.canScrollNext();
    }
  }

  // -----------------------
  // Orchestration: reverse old, play new
  // -----------------------
  let prevIndex = embla.selectedScrollSnap();
  let dots = [];

  function onSelect() {
    const index = embla.selectedScrollSnap();
    if (index !== prevIndex) {
      playOut(prevIndex); // reverse πριν φύγει
      playIn(index);      // play όταν μπει
      prevIndex = index;
    }
    updateDots(dots);
    updateArrows();
  }

  // Init everything
  ensureTimelines();
  setupArrows();
  dots = buildDots();

  // First slide
  playIn(prevIndex);
  updateDots(dots);
  updateArrows();

  // Bind events
  embla.on('select', onSelect);
  embla.on('reInit', () => {
    ensureTimelines();
    dots = buildDots();
    prevIndex = embla.selectedScrollSnap();
    playIn(prevIndex);
    updateDots(dots);
    updateArrows();
  });
});