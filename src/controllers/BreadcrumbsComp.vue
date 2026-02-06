<template>
  <div>
    <q-breadcrumbs class="q-mt-md">
      <!-- Add Home breadcrumb -->
      <q-breadcrumbs-el
        class="text-subtitle2"
        :label="'Home'"
        :to="{ path: '/' }"
      />
      <q-breadcrumbs-el
        class="text-subtitle2"
        v-for="(crumb, index) in breadcrumbs"
        :key="index"
        :label="getBreadcrumbLabel(crumb)"
        @click="goToRoute(crumb.name)"
      />
    </q-breadcrumbs>
  </div>
</template>

<script setup>
import { useStore } from "vuex";
import { computed } from "vue";
import { useRoute, useRouter } from "vue-router";

const store = useStore();
const route = useRoute();
const router = useRouter();

function standardizePath(path) {
  return typeof path === "string" ? path.replace(/^\//, "") : "";
}

const lineages = computed(() => {
  return store.$db().model("routeLineages").query().get();
});

const goToRoute = (routeName) => {
  router.push({
    name: routeName, // The name of the route you want to navigate to
    params: route.params, // Pass along the current params
    query: route.query, // Optional: Pass along the current query params
  });
};

const routeMatch = computed(() => router.resolve(route.path));

const breadcrumbs = computed(() => {
  const exactMatch =
    routeMatch.value.matched[routeMatch.value.matched.length - 1];
  const standardizedPath = standardizePath(exactMatch.path);

  // Find the current route lineage using the route name instead of path
  const currentRouteLineage = store
    .$db()
    .model("routeLineages")
    .query()
    .where("name", exactMatch.name)
    .first();

  if (currentRouteLineage) {
    return currentRouteLineage.lineage.map((name) => {
      return store
        .$db()
        .model("routeLineages")
        .query()
        .where("name", name)
        .first();
    });
  }
  return [];
});

const getBreadcrumbLabel = (crumb) => {
  if (crumb) {
    const dynamicMatch = crumb.label.match(/:(\w+)/);
    if (dynamicMatch) {
      const paramName = dynamicMatch[1];
      return route.params[paramName] || crumb.label;
    }
    return crumb.label;
  }
  return "";
};
</script>
