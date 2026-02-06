import { boot } from "quasar/wrappers";
import QCalendar from "@quasar/quasar-ui-qcalendar";
import "@quasar/quasar-ui-qcalendar/dist/index.css"; // Import QCalendar styles

export default boot(({ app }) => {
  app.use(QCalendar);
});
