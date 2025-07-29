import { createWebHistory, createRouter } from "vue-router";
import Home from "../../views/Posts.vue";

const routes = [
  {
    path: "/",
    name: "HomeTest",
    component: Home,
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
