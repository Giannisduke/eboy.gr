document.addEventListener('DOMContentLoaded', function() {
   gsap.from(".letter", {duration: 1.5, scale: 0, ease: "elastic.out(1.5,0.5)", stagger: 0.5});
   gsap.from(".arrivals", {duration: 1, x:220, autoAlpha:0, ease: "elastic.out(1.5,0.5)", delay: 1.4});
   var tl = new TimelineMax({
        yoyo:true, 
        repeat:-1
        });

        TweenMax.set(".image", {
        scale:0, 
        rotation:0.01,
        z:0.01,
        transformOrigin:"50% 50%"
        });

        tl
        .to(".image", 1, {scale:1, rotation:"+=720"})
        .to({}, 2, {})  // Κενό tween για παύση 0.5 δευτερολέπτων πριν την επανάληψη
;


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


