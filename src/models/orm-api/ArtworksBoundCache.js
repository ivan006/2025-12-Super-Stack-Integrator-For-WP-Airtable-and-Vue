import BasicModel from "./BasicModel";

export default class ArtworksBoundCache extends BasicModel {
  static entity = "ArtworksBoundCache";
  static entityUrl = "/Art";

  static get airtableBaseUrl() {
    return "https://api.airtable.com/v0/appWL8gDT9ZaqV8jY";
  }

  static get proxyBaseUrl() {
    return "https://capetownlists.co.za/bound-cache.php?action=get&url=";
  }

  static defaultFlags = {
    // view: "viwn7wDGK6yk5ZHOl",
  };

  // static entityUrl = '/Artwork?view=viwRVEM6ePmPsrZE4';
}
