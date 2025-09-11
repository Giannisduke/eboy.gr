import.meta.glob([
  '../images/**',
  '../fonts/**',
]);

// Import all of Bootstrap's JS
import * as bootstrap from 'bootstrap'

import { createApp } from 'vue';
import { createPinia } from 'pinia';

import App from './App.vue';
import router from './router';
// Import Gsap
import { gsap } from "gsap";

import { ScrollTrigger } from "gsap/ScrollTrigger"; 

import { ScrollToPlugin } from "gsap/ScrollToPlugin";

gsap.registerPlugin(ScrollTrigger,ScrollToPlugin);


const pinia = createPinia();
const app = createApp(App);
app.use(router);
app.use(pinia);

app.mount('#slider');

document.addEventListener('facetwp-loaded', function() {
  const scrollButton = document.querySelector('.searchbar');
  const main_cats = document.querySelector('.main_cats');
  const buttons = document.querySelectorAll('.facetwp-radio');
  const control = document.querySelector('.product-tags');

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
const grid_6 = document.getElementById('grid_6');
const targets = document.getElementsByClassName('product');

if (grid_2) {
    grid_2.onclick = function() {
        const newValue = grid_2.getAttribute('data-value');
        if (!newValue) return;

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

        for (let i = 0; i < targets.length; i++) {
            targets[i].classList.remove('view_normal', 'view_small');
            targets[i].classList.add(newValue);
        }
    };
}
