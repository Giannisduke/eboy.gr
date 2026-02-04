document.addEventListener('DOMContentLoaded', function () {
  const emblaNode = document.querySelector('.embla');
  if (!emblaNode) return;

  // --- Plugins που ήδη έχεις ---
  const autoplay = EmblaCarouselAutoplay({
    delay: 3000,
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

  // Προσοχή: το Embla θέλει viewport node, όχι root.
  // Αν το `.embla` είναι root, συνήθως το viewport είναι `.embla__viewport`.
  const viewportNode = emblaNode.querySelector('.embla__viewport') || emblaNode;

  const embla = EmblaCarousel(viewportNode, { loop: true }, [autoplay, classNames]);

  // --- GSAP helpers ---
  function getSlideEls() {
    return embla.slideNodes(); // Embla slide elements
  }

  function setInitial(slideEl) {
    const a = slideEl.querySelector('.img-a');
    const b = slideEl.querySelector('.img-b');
    if (!a || !b) return;

    // Reset σε "κρυφή" κατάσταση ώστε να ξαναπαίζει όταν ξαναέρθει
    gsap.killTweensOf([a, b]);
    gsap.set([a, b], { opacity: 0, willChange: 'transform, opacity' });
    gsap.set(a, { y: 18, scale: 1.02 });
    gsap.set(b, { y: 28, scale: 1.02 });
  }

  function animateIn(slideEl) {
    const a = slideEl.querySelector('.img-a');
    const b = slideEl.querySelector('.img-b');
    if (!a || !b) return;

    gsap.killTweensOf([a, b]);

    const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });
    tl.to(a, { opacity: 1, y: 0, scale: 1, duration: 0.6 }, 0)
      .to(b, { opacity: 1, y: 0, scale: 1, duration: 0.7 }, 0.12);

    return tl;
  }

  function resetAllExcept(activeIndex) {
    const slides = getSlideEls();
    slides.forEach((slideEl, i) => {
      if (i === activeIndex) return;
      setInitial(slideEl);
    });
  }

  // --- Bind events ---
  function runForSelected() {
    const index = embla.selectedScrollSnap();
    const slides = getSlideEls();
    resetAllExcept(index);
    animateIn(slides[index]);
  }

  // Initial state σε όλα
  getSlideEls().forEach(setInitial);

  // Παίξε στο πρώτο slide
  runForSelected();

  // Κάθε φορά που αλλάζει slide (autoplay ή drag)
  embla.on('select', runForSelected);

  // Αν αλλάξει layout / responsive και γίνει reInit
  embla.on('reInit', () => {
    getSlideEls().forEach(setInitial);
    runForSelected();
  });
});