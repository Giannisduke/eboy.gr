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

import { gsap } from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
import { ScrollToPlugin } from "gsap/ScrollToPlugin";
import EmblaCarousel from 'embla-carousel';
import Autoplay from 'embla-carousel-autoplay';
import ClassNames from 'embla-carousel-class-names'

const emblaNode = document.querySelector('.embla__viewport');

if (emblaNode) {
    const options = {
        loop: true,
        align: 'end',
        containScroll: 'keepSnaps' // Prevents empty space at the end
    };
    const plugins = [
        //Autoplay(),
        ClassNames()
    ];
    const emblaApi = EmblaCarousel(emblaNode, options, plugins);
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
