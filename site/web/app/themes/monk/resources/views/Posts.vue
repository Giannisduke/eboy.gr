
<script setup>
// If you are using PurgeCSS, make sure to whitelist the carousel CSS classes
import 'vue3-carousel/carousel.css'
import { Carousel, Slide, Pagination, Navigation } from 'vue3-carousel'

// Import Gsap
import { gsap } from "gsap";

import { TextPlugin } from 'gsap/TextPlugin';

import { ScrollTrigger } from "gsap/ScrollTrigger"; 

import { ScrollToPlugin } from "gsap/ScrollToPlugin";

gsap.registerPlugin(ScrollTrigger,ScrollToPlugin);



const carouselConfig = {
  height: 500,
  gap: 5,
  dir: 'rtl',
  modelValue: 0,
  snapAlign: 'center-even',
  itemsToShow: 4.5,
 // autoplay: 4500,
  wrapAround: true,
  pauseAutoplayOnHover: true,
  transition: 400,
  slideEffect: 'slide',
  mouseDrag: true,
  breakpointMode: 'carousel',
    // Breakpoints are mobile-first
  // Any settings not specified will fall back to the carousel's default settings
  breakpoints: {
    // 300px and up
    300: {
      itemsToShow: 2,
      snapAlign: 'center',
    },
    // 400px and up
    400: {
      itemsToShow: 3,
      snapAlign: 'start',
    },
    // 500px and up
    500: {
      itemsToShow: 4.5,
      snapAlign: 'center-even',
    },
  },
}


//gsap.set(".name", {opacity:0})
const handleInit = () => {
  console.log('Carousel initialized')

  }
const handleSlideEnd = (data) => {
//  console.log('Slide started:', data)
gsap.to(".carousel__slide--active .move", {
  duration: 1.5,
  scale: 1, 
  opacity: 1, 
  delay: 0.15, 
  stagger: 0.2,
  ease: "elastic", 
  force3D: true
});
}

const handleSlideStart = (data) => {
gsap.to(".move", {
  opacity: 0,
  scale: 0
});
}

</script>

<template>
   
  <Carousel @init="handleInit"  @slide-start="handleSlideStart" @slide-end="handleSlideEnd" v-bind="carouselConfig">
    <Slide v-for="product of products" :key="product">
      <div class="carousel__item">
        <div class="details">
          <div class="name move" ref="myElement">
            <span>
              {{ product.name }}    
            </span>
          </div>
          <div class="price">
            <span class="sales_price move">
            {{ product.sale_price }}€    
            </span>
            <span class="regular_price move">
            {{ product.regular_price }}€    
            </span>
          </div>
        </div>

        <img :src="product.images[0].src" alt="">
        </div>
    </Slide>

    <template #addons>
      <Navigation />
      <Pagination />
    </template>
  </Carousel>


</template>





<script>
  
import axios from "axios";

    export default {
        data() {
            return {
                products: [],
                errors: [],
            };
        },

        // Pulls posts when the component is created.
        created() {
            axios
                .get('https://simple-city.eboy.gr/wp-json/wc/v3/products?featured=true&consumer_key=ck_411069644bb7de3656fc0aa91c7b1ae647491d73&consumer_secret=cs_084bc2a33a810a4beaaf88da2cd8d9b15e8c326d')
                .then((response) => {
                    // JSON responses are automatically parsed.
                    this.products = response.data;
                })
                .catch((e) => {
                    this.errors.push(e);
                });
        }
    };
</script>


<style>



.carousel__track {
  transform-style: preserve-3d;
}

.carousel__slide--sliding {
  transition:
    opacity var(--carousel-transition),
    transform var(--carousel-transition);
}

.carousel.is-dragging .carousel__slide {
  transition:
    opacity var(--carousel-transition),
    transform var(--carousel-transition);
}


.carousel__slide .carousel__item img {
  max-height: 400px;
  filter: drop-shadow(0px 27px 7px hsl(50deg 50% 5% / 0.12));
}

.carousel__pagination {
  display: none;
}

.carousel__slide--prev {
  opacity: var(--carousel-opacity-near);
  transform: rotateY(-10deg) scale(0.35);
}

.carousel__slide--next {
  opacity: var(--carousel-opacity-near);
  transform: rotateY(10deg) scale(0.35);
}

.carousel__slide--next ~ .carousel__slide {
  opacity: var(--carousel-opacity-inactive);
  transform: translateX(-10px) rotateY(12deg) scale(0.35);
}


</style>