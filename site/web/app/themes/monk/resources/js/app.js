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

import { TextPlugin } from 'gsap/TextPlugin';

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


var boxes = gsap.utils.toArray(".slogan > svg"),
    area = document.querySelector('#hover'),
    action = ['one', 'two', 'three'];

gsap.set(boxes, {
  xPercent:-1, 
  x:function(i) {return (10 * i) - (5*4);}, 
  yPercent:-1, y:0
})

function go() {
  boxes.forEach((box, i) => {
    action[i] = gsap.timeline({overwrite:true}) // 
    .to(box, {
      x: "random(-3, 3, 3)",
      y: "random(-3, 3, 3)",
      duration: gsap.utils.random(2, 6, 0.5), 
      ease: "sine.inOut",
      repeat:-1, repeatRefresh: true, // 
    })
    .to(box, {
      rotation: "random(-1, 1, 1)",
      duration: gsap.utils.random(1, 3, 0.5), 
      ease: "sine.inOut",
      repeat:-1, repeatRefresh: true, 
    }, 2)
    })
}
 
var master = gsap.timeline({})
.set(boxes, {autoAlpha:1, delay:1})
.add(go())



