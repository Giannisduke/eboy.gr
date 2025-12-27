
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