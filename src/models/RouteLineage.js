// src/models/RouteLineage.js
import { Model } from "@vuex-orm/core";

export default class RouteLineage extends Model {
  static entity = "routeLineages";

  static fields() {
    return {
      id: this.attr(null).nullable(),
      name: this.attr(""),
      label: this.attr(""),
      parent: this.attr(null),
      lineage: this.attr([]),
    };
  }
}
