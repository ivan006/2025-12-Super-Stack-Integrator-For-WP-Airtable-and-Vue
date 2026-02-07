const routes = [
  {
    path: "/",
    component: () => import("src/controllers/GlobalController.vue"),
    meta: { requiresAuth: false },
    children: [
      {
        path: "",
        redirect: "/data-cache-info",
      },
      {
        path: "data-cache-info",
        component: () => import("src/controllers/DataCacheInfo.vue"),
        meta: { requiresAuth: false },
      },
      {
        path: "data-cache-binder",
        component: () => import("src/controllers/DataCacheBinder.vue"),
        meta: { requiresAuth: false },
      },
      {
        path: "html-cache-pages",
        component: () => import("src/controllers/HtmlCachePages.vue"),
        meta: { requiresAuth: false },
      },
      {
        path: "data-acceptance-page",
        component: () => import("src/controllers/DataAcceptance.vue"),
        meta: { requiresAuth: false },
      },
    ],
  },

  {
    path: "/:catchAll(.*)*",
    component: () => import("src/controllers/ErrorNotFound.vue"),
    meta: { requiresAuth: false },
  },
];

export default routes;
