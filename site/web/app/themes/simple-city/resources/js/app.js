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

// CUSTOM DIRECTIVE
// app.directive('html-append', (el, binding) => {
//   el.insertAdjacentHTML('beforeend', binding.value);
// });

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


