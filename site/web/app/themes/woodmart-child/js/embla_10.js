  const emblaNode = document.querySelector('.embla')  
  const options = { loop: false}  
  const plugins = [EmblaCarouselAutoplay()]  
  const emblaApi = EmblaCarousel(emblaNode, options, plugins)

  console.log(emblaApi.slideNodes()) // Access API


    const emblaNode = document.querySelector('.embla')
  const options = { loop: false }
  const emblaApi = EmblaCarousel(emblaNode, options)

  console.log(emblaApi.slideNodes()) // Access API