import VuexORM from "@vuex-orm/core";
import VuexORMAxios from "@vuex-orm/plugin-axios";
import axios from "axios";

export default async ({ store }) => {
  VuexORM.use(VuexORMAxios, { axios, baseURL: "http://aiv-team-2.0.test/api" });
};
