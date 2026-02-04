document.addEventListener('DOMContentLoaded', function() {

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


