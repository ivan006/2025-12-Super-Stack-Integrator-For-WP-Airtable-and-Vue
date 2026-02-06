<template>
  <router-view />
</template>

<script>
import { onMounted } from "vue";
import { useStore } from "vuex";
import { useRouter } from "vue-router";
import VuexORM from "@vuex-orm/core";
import VuexORMAxios from "@vuex-orm/plugin-axios";
import axios from "axios";
import { generateRouteLineages } from "src/store/modules/routeLineages";

import { createMetaMixin } from "quasar";

export default {
  name: "App",
  mixins: [
    createMetaMixin(function () {
      const origin = window.location.origin;
      const path = this.$route.fullPath;
      const canonicalUrl = `${origin}${path}`;
      const baseUrl = import.meta.env.VITE_API_BACKEND_URL;
      return {
        meta: {
          // viewport: {
          //   name: 'viewport',
          //   content: 'width=device-width, initial-scale=0.75, maximum-scale=1.0, user-scalable=yes'
          // },
          ogLocale: {
            property: "og:locale",
            content: "en_ZA", // Or en_US if you're targeting US audience
          },
          ogUrl: {
            property: "og:url",
            content: canonicalUrl, // Dynamically resolve if needed
          },
          robots: {
            name: "robots",
            content: "index, follow",
          },
          appleMobileWebAppCapable: {
            name: "apple-mobile-web-app-capable",
            content: "yes",
          },
          appleMobileWebAppStatusBarStyle: {
            name: "apple-mobile-web-app-status-bar-style",
            content: "black-translucent",
          },
          themeColor: {
            name: "theme-color",
            content: "#ffffff", // Or match your brand color
          },
        },
        link: {
          canonical: {
            rel: "canonical",
            href: canonicalUrl,
          },
          preconnect: {
            rel: "preconnect",
            href: baseUrl,
          },
          dnsPrefetch: {
            rel: "dns-prefetch",
            href: baseUrl,
          },
        },
      };
    }),
  ],
  mounted() {
    const store = useStore();
    const router = useRouter();

    // Initialize Vuex ORM with Axios plugin
    VuexORM.use(VuexORMAxios, {
      axios,
      baseURL: "http://aiv-team-2.0.test/api",
    });

    // Ensure router and routes are available
    if (router && router.options && router.options.routes) {
      generateRouteLineages(router.options.routes);
    } else {
      console.error(
        "Router is not properly initialized or routes are not accessible.",
      );
    }
  },
};
</script>
