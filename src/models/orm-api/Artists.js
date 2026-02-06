import BasicModel from "./BasicModel";

export default class Artist extends BasicModel {
  static entity = "Artist";
  static entityUrl = "/Artist";

  static get airtableBaseUrl() {
    return "https://api.airtable.com/v0/appWL8gDT9ZaqV8jY";
  }

  static defaultFlags = {
    view: "viwqEmrRk14mVdu4z",
  };
}
