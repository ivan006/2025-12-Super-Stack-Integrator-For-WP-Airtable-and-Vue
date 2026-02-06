// import { DBBaseModel } from 'wizweb-fe'
import VueCookies from "vue-cookies";
import { Model } from "@vuex-orm/core";

export default class MyBaseModel extends Model {
  static baseUrl = import.meta.env.VITE_API_BACKEND_URL;

  static adapter = "airtable";

  static mergeHeaders(headers) {
    const result = {
      ...headers,
    };

    // const VITE_AUTH = VueCookies.get('VITE_AUTH');
    //
    // if (VITE_AUTH?.token) {
    //   const expireDate = new Date(VITE_AUTH.expireDate);
    //   const currentDate = new Date();
    //
    //   if (currentDate <= expireDate) {
    //     result['Authorization'] = `Bearer ${VITE_AUTH.token}`;
    //   }
    // }

    result["Authorization"] = `Bearer ${import.meta.env.VITE_API_AIRTABLE_KEY}`;

    return result;
  }
}
