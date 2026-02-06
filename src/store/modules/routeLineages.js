// src/store/modules/routeLineages.js
import RouteLineage from "src/models/RouteLineage";

function standardizePath(path) {
  return typeof path === "string" ? path.replace(/^\//, "") : "";
}

function flattenRoutes(routes) {
  const flatRoutes = {};

  function processRoute(route, parentPath = "") {
    const standardizedPath = standardizePath(
      parentPath + "/" + standardizePath(route.path),
    );
    flatRoutes[standardizedPath] = route;
    if (route.children && route.children.length > 0) {
      route.children.forEach((subroute) =>
        processRoute(subroute, standardizedPath),
      );
    }
  }

  routes.forEach((route) => processRoute(route));
  return flatRoutes;
}

function processRouteForLineage(route, flatRoutes) {
  const standardizedPath = standardizePath(route.path);
  const lineage = [];
  let currentRoute = route;

  while (currentRoute) {
    const currentRouteName = currentRoute.name || "";
    if (lineage.includes(currentRouteName)) break; // Break if lineage already includes the name to prevent infinite loop

    lineage.unshift(currentRouteName);
    const parentRouteName = currentRoute.meta?.breadcrumbParentName || null;

    // If no breadcrumbParentName, treat as root route and include itself in the lineage
    if (!parentRouteName) {
      break;
    }

    const parentRoute = parentRouteName
      ? flatRoutes[standardizePath(parentRouteName)]
      : null;
    currentRoute = parentRoute || null;
  }

  const breadcrumbLabel = route.meta?.breadcrumbName || "";
  const routeLineageData = {
    label: breadcrumbLabel,
    name: route.name || "",
    parent: route.meta?.breadcrumbParentName
      ? standardizePath(route.meta.breadcrumbParentName)
      : null,
    lineage: lineage,
  };

  RouteLineage.insert({
    data: routeLineageData,
  });
}

export function generateRouteLineages(routes) {
  console.log("Generating route lineages...");
  const flatRoutes = flattenRoutes(routes);
  Object.values(flatRoutes).forEach((route) => {
    processRouteForLineage(route, flatRoutes);
  });
  console.log("Route lineages generated.");
}
