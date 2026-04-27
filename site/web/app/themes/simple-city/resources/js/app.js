import.meta.glob([
  '../images/**',
  '../fonts/**',
]);

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createVuetify } from 'vuetify';
import { VRangeSlider, VSelect } from 'vuetify/components';
import 'vuetify/styles';
import ShopPage from './components/shop/ShopPage.vue';

// Vuetify configuration with only needed components
const vuetify = createVuetify({
  components: {
    VRangeSlider,
    VSelect,
  },
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: {
          primary: '#0E0C0A',
          secondary: '#fff200',
        }
      }
    }
  }
});

import 'bootstrap';

import { gsap } from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
import { ScrollToPlugin } from "gsap/ScrollToPlugin";
import EmblaCarousel from 'embla-carousel';
import Autoplay from 'embla-carousel-autoplay';
import ClassNames from 'embla-carousel-class-names'

const emblaNode = document.querySelector('.embla__viewport');

if (emblaNode) {
    const emblaApi = EmblaCarousel(emblaNode, {
        loop: true,
        align: 'end',
    });

    const prevBtn = document.querySelector('.embla__button--prev');
    const nextBtn = document.querySelector('.embla__button--next');
    if (prevBtn) prevBtn.addEventListener('click', () => emblaApi.scrollPrev());
    if (nextBtn) nextBtn.addEventListener('click', () => emblaApi.scrollNext());

    const slideNodes = emblaApi.slideNodes();

    const updateActive = () => {
        const selected = emblaApi.selectedScrollSnap();
        slideNodes.forEach((slide, i) => slide.classList.toggle('is-active', i === selected));
    };

    emblaApi.on('select', updateActive);
    emblaApi.on('reInit', updateActive);
    updateActive();

    const dotsContainer = document.querySelector('.embla__dots');
    if (dotsContainer) {
        const dots = emblaApi.scrollSnapList().map((_, i) => {
            const dot = document.createElement('button');
            dot.classList.add('embla__dot');
            dot.type = 'button';
            dotsContainer.appendChild(dot);
            dot.addEventListener('click', () => emblaApi.scrollTo(i));
            return dot;
        });

        const updateDots = () => {
            const selected = emblaApi.selectedScrollSnap();
            dots.forEach((dot, i) => dot.classList.toggle('embla__dot--selected', i === selected));
        };

        emblaApi.on('select', updateDots);
        updateDots();
    }
}


gsap.registerPlugin(ScrollTrigger, ScrollToPlugin);



const grid_2 = document.getElementById('grid_2');
const grid_4 = document.getElementById('grid_4');
const grid_6 = document.getElementById('grid_6');
const targets = document.getElementsByClassName('product');

// Helper to clear "selected" class from all grid buttons
function clearSelected() {
    [grid_2, grid_4, grid_6].forEach(btn => {
        if (btn) btn.classList.remove('selected');
    });
}

if (grid_2) {
    grid_2.onclick = function() {
        const newValue = grid_2.getAttribute('data-value');
        if (!newValue) return;

        clearSelected();
        grid_2.classList.add('selected');

        for (let i = 0; i < targets.length; i++) {
            targets[i].classList.remove('view_large', 'view_normal');
            targets[i].classList.add(newValue);
        }
    };
}

if (grid_4) {
    grid_4.onclick = function() {
        const newValue = grid_4.getAttribute('data-value');
        if (!newValue) return;

        clearSelected();
        grid_4.classList.add('selected');

        for (let i = 0; i < targets.length; i++) {
            targets[i].classList.remove('view_large', 'view_small');
            targets[i].classList.add(newValue);
        }
    };
}

if (grid_6) {
    grid_6.onclick = function() {
        const newValue = grid_6.getAttribute('data-value');
        if (!newValue) return;

        clearSelected();
        grid_6.classList.add('selected');

        for (let i = 0; i < targets.length; i++) {
            targets[i].classList.remove('view_normal', 'view_small');
            targets[i].classList.add(newValue);
        }
    };
}

// Mount Vue Shop App
const shopAppElement = document.getElementById('vue-shop-app');
if (shopAppElement) {
    const pinia = createPinia();
    const shopApp = createApp(ShopPage);
    shopApp.use(pinia);
    shopApp.use(vuetify);
    shopApp.mount('#vue-shop-app');
}
