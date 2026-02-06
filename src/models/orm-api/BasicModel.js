import { Model } from "@vuex-orm/core";

export default class BasicModel extends Model {
  static entityUrl = "";

  /** ✅ Defaults (can be overridden in child models) **/
  static get proxyBaseUrl() {
    return import.meta.env.VITE_API_PROXY_URL;
  }

  static get airtableBaseUrl() {
    return import.meta.env.VITE_API_BACKEND_URL;
  }

  static baseUrl = import.meta.env.VITE_API_BACKEND_URL;
  static defaultFlags = {};

  static mergeHeaders(headers) {
    return { ...headers };
  }

  static customApiBase(moreHeaders) {
    this.apiConfig = {
      baseURL: "",
      headers: { ...moreHeaders },
    };
    return this.api();
  }

  static flattenParams(obj, prefix = "") {
    const result = {};
    for (const [key, value] of Object.entries(obj)) {
      const newKey = prefix ? `${prefix}.${key}` : key;
      if (
        typeof value === "object" &&
        value !== null &&
        !Array.isArray(value)
      ) {
        Object.assign(result, this.flattenParams(value, newKey));
      } else {
        result[newKey] = Array.isArray(value) ? value.join(",") : value;
      }
    }
    return result;
  }

  static FetchAll(
    relationships = [],
    flags = {},
    moreHeaders = {},
    options = {
      page: 1,
      limit: 15,
      filters: {},
      offset: null,
      clearPrimaryModelOnly: false,
    },
  ) {
    const proxyBase = this.proxyBaseUrl;
    const airtableBase = this.airtableBaseUrl;
    const headers = this.mergeHeaders(moreHeaders);
    const airtableOffset = options.offset ? options.offset : undefined;
    const airtableUrl = `${airtableBase}${this.entityUrl}`;

    const flatParams = this.flattenParams({
      // limit: options.limit,
      ...(airtableOffset ? { offset: airtableOffset } : {}),
      ...flags,
      ...this.defaultFlags,
      ...options.filters,
    });

    const queryStringEncoded = new URLSearchParams(flatParams).toString();
    const queryString = decodeURIComponent(queryStringEncoded);
    const encodedInner = encodeURIComponent(`${airtableUrl}?${queryString}`);
    const finalUrl = `${proxyBase}${encodedInner}`;

    return this.customApiBase(headers).get(finalUrl, { save: false });
  }

  static FetchById(id, relationships = [], flags = {}, moreHeaders = {}) {
    const proxyBase = this.proxyBaseUrl;
    const airtableBase = this.airtableBaseUrl;
    const headers = this.mergeHeaders(moreHeaders);

    const airtableUrl = `${airtableBase}${this.entityUrl}/${id}`;

    const flatParams = this.flattenParams({
      ...flags,
      ...(relationships.length ? { with: relationships.join(",") } : {}),
    });

    const queryStringEncoded = new URLSearchParams(flatParams).toString();
    const queryString = decodeURIComponent(queryStringEncoded);
    const encodedInner = encodeURIComponent(`${airtableUrl}?${queryString}`);
    const finalUrl = `${proxyBase}${encodedInner}`;

    return this.customApiBase(headers).get(finalUrl, { save: false });
  }
}
