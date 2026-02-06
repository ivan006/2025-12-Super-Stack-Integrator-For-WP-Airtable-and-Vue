import { route } from "quasar/wrappers";
import {
  createRouter,
  createMemoryHistory,
  createWebHistory,
  createWebHashHistory,
} from "vue-router";
import routes from "./routes";
import VueCookies from "vue-cookies";

export default route(function (/* { store, ssrContext } */) {
  const createHistory = process.env.SERVER
    ? createMemoryHistory
    : process.env.VUE_ROUTER_MODE === "history"
    ? createWebHistory
    : createWebHashHistory;

  const Router = createRouter({
    scrollBehavior(to, from, savedPosition) {
      if (to.hash) {
        return new Promise((resolve, reject) => {
          setTimeout(() => {
            resolve({
              el: to.hash,
              behavior: "smooth",
            });
          }, 300);
        });
      } else {
        return { top: 0 };
      }
    },
    routes,
    history: createHistory(process.env.VUE_ROUTER_BASE),
  });

  Router.beforeEach((to, from, next) => {
    const sessionitem = VueCookies.get("VITE_AUTH");
    const requiresAuth = to.matched.some(
      (record) => record.meta.requiresAuth !== false,
    );

    if (requiresAuth && !sessionitem?.user) {
      next({ path: "/login" });
    } else {
      next();
    }
  });

  return Router;
});
