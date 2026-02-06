import dayjs from "dayjs";
import localizedFormat from "dayjs/plugin/localizedFormat";
import duration from "dayjs/plugin/duration";
import isBetween from "dayjs/plugin/isBetween";
// import isSame from "dayjs/plugin/isSame";
import advancedFormat from "dayjs/plugin/advancedFormat";

dayjs.extend(localizedFormat);
dayjs.extend(duration);
dayjs.extend(isBetween);
// dayjs.extend(isSame);
dayjs.extend(advancedFormat);

class Helpers {
  static formatCasualTime(start, end) {
    const now = dayjs();
    const startDate = dayjs(start);
    const endDate = dayjs(end);

    const startTime = startDate.format("HH:mm");
    const endTime = endDate.format("HH:mm");

    const dayNameStart = startDate.format("ddd");
    const dayNameEnd = endDate.format("ddd");

    const startDayWithMonth = startDate.format("MMM Do");
    const endDayWithMonth = endDate.format("MMM Do");

    const yearStart = startDate.year();
    const yearEnd = endDate.year();

    // Calculate duration in minutes, hours, days, and weeks, and round to the nearest half unit
    const durationMinutes = endDate.diff(startDate, "minute");
    const durationHours = durationMinutes / 60;
    const durationDays = durationHours / 24;
    const durationWeeks = durationDays / 7;

    let durationText;
    if (durationMinutes < 60) {
      const roundedMinutes = Math.round(durationMinutes / 30) * 0.5;
      durationText = `~${roundedMinutes} minute${
        roundedMinutes !== 1 ? "s" : ""
      }`;
    } else if (durationHours < 24) {
      const roundedHours = Math.round(durationHours * 2) / 2;
      durationText = `~${roundedHours} hour${roundedHours !== 1 ? "s" : ""}`;
    } else if (durationDays < 7) {
      const roundedDays = Math.round(durationDays * 2) / 2;
      durationText = `~${roundedDays} day${roundedDays !== 1 ? "s" : ""}`;
    } else {
      const roundedWeeks = Math.round(durationWeeks * 2) / 2;
      durationText = `~${roundedWeeks} week${roundedWeeks !== 1 ? "s" : ""}`;
    }

    // Determine if the event is within the next 6 days
    const withinNextSixDays = startDate.isBefore(now.add(6, "day"));

    // Format the time range
    let formattedRange = "";
    if (startDate.isSame(endDate, "day")) {
      formattedRange = startDate.isSame(now, "day")
        ? `Today, ${startTime} - ${endTime}`
        : `${dayNameStart}, ${startTime} - ${endTime}`;
    } else if (withinNextSixDays) {
      formattedRange = `${dayNameStart}, ${startTime} - ${dayNameEnd}, ${endTime}`;
    } else if (startDate.isSame(endDate, "month")) {
      formattedRange = `${startDayWithMonth}, ${dayNameStart}, ${startTime} - ${endDayWithMonth}, ${dayNameEnd}, ${endTime}`;
    } else if (startDate.isSame(endDate, "year")) {
      formattedRange = `${startDayWithMonth}, ${dayNameStart}, ${startTime} - ${endDayWithMonth}, ${dayNameEnd}, ${yearEnd}, ${endTime}`;
    } else {
      formattedRange = `${startDayWithMonth}, ${dayNameStart}, ${yearStart}, ${startTime} - ${endDayWithMonth}, ${dayNameEnd}, ${yearEnd}, ${endTime}`;
    }

    // Calculate "coming up" hint or event status
    const timeUntilStart = startDate.diff(now, "minute");
    const timeUntilEnd = endDate.diff(now, "minute");
    let comingUpHint = "";
    if (timeUntilEnd < 0) {
      comingUpHint = "Event has finished";
    } else if (timeUntilStart <= 0 && timeUntilEnd >= 0) {
      comingUpHint = "Event is happening now";
    } else if (timeUntilStart < 60) {
      const roundedMinutes = Math.round(timeUntilStart / 30) * 0.5;
      comingUpHint = `~${roundedMinutes} minute${
        roundedMinutes !== 1 ? "s" : ""
      } from now`;
    } else if (timeUntilStart < 1440) {
      const roundedHours = Math.round((timeUntilStart / 60) * 2) / 2;
      comingUpHint = `~${roundedHours} hour${
        roundedHours !== 1 ? "s" : ""
      } from now`;
    } else if (timeUntilStart < 10080) {
      const roundedDays = Math.round((timeUntilStart / 1440) * 2) / 2;
      comingUpHint = `~${roundedDays} day${
        roundedDays !== 1 ? "s" : ""
      } from now`;
    } else if (timeUntilStart < 43800) {
      const roundedWeeks = Math.round((timeUntilStart / 10080) * 2) / 2;
      comingUpHint = `~${roundedWeeks} week${
        roundedWeeks !== 1 ? "s" : ""
      } from now`;
    } else {
      const roundedMonths = Math.round((timeUntilStart / 43800) * 2) / 2;
      comingUpHint = `~${roundedMonths} month${
        roundedMonths !== 1 ? "s" : ""
      } from now`;
    }

    return {
      range: formattedRange,
      duration: durationText,
      comingUpHint: comingUpHint,
    };
  }

  static snakeToTitle(string) {
    return string
      .split("_")
      .map((part) => part.charAt(0).toUpperCase() + part.slice(1).toLowerCase())
      .join(" ");
  }

  static getIfMatchesAllChecks(item, filters) {
    for (const [key, filter] of Object.entries(filters)) {
      // todo: note the below logic was to support time range filters
      if (
        typeof filter === "object" &&
        !Array.isArray(filter) &&
        filter !== null
      ) {
        if (filter?.value) {
          if (filter.usageType === "timeRangeStart") {
            if (filter.value.range.start) {
              const startDate = new Date(filter.value.range.start);
              const endDate = new Date(filter.value.range.end);
              const itemDate = new Date(item[key]);
              return startDate < itemDate && itemDate < endDate;
            }
          }
        }
      } else if (filter !== null) {
        return item[key] == filter;
      }
    }

    return true;
  }

  static prepareFiltersForSupabase(obj) {
    let result = [];
    for (const [key, filter] of Object.entries(obj)) {
      // todo: note the below logic was to support time range filters
      if (
        typeof filter === "object" &&
        !Array.isArray(filter) &&
        filter !== null
      ) {
        if (filter?.value) {
          if (filter.usageType === "timeRangeStart") {
            if (filter.value.range.start) {
              result.push(`${key}=gte.${filter.value.range.start}`);
              result.push(`${key}=lte.${filter.value.range.end}`);
            }
          }
        }
      } else if (filter !== null) {
        // result[key] = `eq.${item.value}`;
        result.push(`${key}=eq.${filter}`);
      }
    }
    // return result;
    return result.join("&");
  }

  static prepareFiltersForLaravel(obj) {
    let result = {
      filter: {},
    };
    for (const [key, filter] of Object.entries(obj)) {
      result.filter[key] = filter;
    }
    // return result.join('&');
    return result;
  }

  static prepareRelationsForSupabase(arr) {
    let select = ["*"];
    for (const value of arr) {
      select.push(`${value}(*)`);
    }

    // *,event_type_id(*),provider_that_owns_this_id(*),event_type:venue_country_id(*),venue_state_id(*),venue_substate_id(*),venue_town_id(*),venue_suburb_id(*)
    const result = select.join(",");

    return {
      select: result,
    };
  }
  static prepareRelationsForLaravel(arr) {
    let includes = [];
    for (const value of arr) {
      includes.push(value);
    }

    // Join the relations with commas
    const result = includes.join(",");

    return {
      include: result,
    };
  }

  static capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
  }
}

export default Helpers;
