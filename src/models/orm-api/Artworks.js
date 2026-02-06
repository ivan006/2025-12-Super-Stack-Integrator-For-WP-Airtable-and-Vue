import BasicModel from "./BasicModel";

export default class Artworks extends BasicModel {
  static entity = "Artworks";
  static entityUrl = "/Art";

  static get airtableBaseUrl() {
    return "https://api.airtable.com/v0/appWL8gDT9ZaqV8jY";
  }

  static defaultFlags = {
    view: "viwn7wDGK6yk5ZHOl",
  };
}
