import.meta.glob([
  '../images/**',
  '../fonts/**',
]);

import * as bootstrap from 'bootstrap';

//import { createApp } from 'vue';
//import { createPinia } from 'pinia';

//import App from './App.vue';
//import router from './router';

import { gsap } from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
import { ScrollToPlugin } from "gsap/ScrollToPlugin";
import EmblaCarousel from 'embla-carousel';
import Autoplay from 'embla-carousel-autoplay';

EmblaCarousel.globalOptions = { loop: true }

const emblaNode = document.querySelector('.embla')
const plugins = [Autoplay()]
//const emblaApi = EmblaCarousel(emblaNode, options, plugins)
const emblaApi = EmblaCarousel(emblaNode, { align: 'end' })


gsap.registerPlugin(ScrollTrigger, ScrollToPlugin);

//const pinia = createPinia();
//const app = createApp(App);
//app.use(router);
//app.use(pinia);

//app.mount('#slider');


document.addEventListener('facetwp-loaded', function() {
  const scrollButton = document.querySelector('.searchbar');
  const main_cats = document.querySelector('.main_cats');
  const buttons = document.querySelectorAll('.facetwp-radio');
  const control = document.querySelector('.product-tags');
 
   // TODO: Define radios and currentSort variables
  //const radios = document.querySelectorAll('.some-radio-selector');
  //const currentSort = // η τρέχουσα τιμή sort

  // radios.forEach(radio => {
  //   radio.checked = radio.value === currentSort;
  // });




scrollButton.addEventListener('click', () => {
        gsap.to(window, .5, {scrollTo:{y:main_cats, offsetY:160}});
        });
buttons.forEach((button) => {
    button.addEventListener('click', () => {
      bootstrap.Collapse.getOrCreateInstance(control, { toggle: true });
      });
  });


});



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

