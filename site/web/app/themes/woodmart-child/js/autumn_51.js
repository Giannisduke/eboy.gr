document.addEventListener('DOMContentLoaded', function() {
   gsap.from(".letter", {duration: 1.5, scale: 0, ease: "elastic.out(1.5,0.5)", stagger: 0.5});
   gsap.from(".arrivals", {duration: 1, scale:0, rotation:"-=720", autoAlpha:0, ease: "elastic.out(1.5,0.5)", delay: 1.4});



   const emblaNode = document.querySelector('.embla');
  if (!emblaNode) return;

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

  const embla = EmblaCarousel(emblaNode, { loop: true }, [autoplay, classNames]);
});


