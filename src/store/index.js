import { createStore } from "vuex";
import VuexORM from "@vuex-orm/core";

// import { DBCrudCacheSet } from 'wizweb-fe';

import RouteLineage from "src/models/RouteLineage";

import Site from "src/models/orm-api/Site";
import Site_Menu_Items from "src/models/orm-api/Site_Menu_Items";

// Initialize the database
const database = new VuexORM.Database();

// Register models
// database.register(DBCrudCacheSet);

database.register(RouteLineage);
database.register(Site);
database.register(Site_Menu_Items);

// Create Vuex store
const store = createStore({
  plugins: [VuexORM.install(database)],
});

export default store;
