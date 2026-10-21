/**
 * UTC to Local Time Conversion Functions
 * Convert UTC timestamps to user's local date and time
 */

/**
 * Convert UTC timestamp to user's local date and time
 * @param {string} utcTimestamp - UTC timestamp in format "YYYY-MM-DD HH:MM:SS" or ISO string
 * @param {object} options - Formatting options
 * @param {boolean} options.includeSeconds - Whether to include seconds in output (default: false)
 * @param {boolean} options.includeDate - Whether to include date in output (default: true)
 * @param {boolean} options.includeTime - Whether to include time in output (default: true)
 * @param {string} options.dateFormat - Date format: 'short', 'medium', 'long', 'full' (default: 'medium')
 * @param {string} options.timeFormat - Time format: 'short', 'medium', 'long', 'full' (default: 'short')
 * @returns {string} Formatted local date and time
 */
function utcToLocal(utcTimestamp, options = {}) {
  if (!utcTimestamp) return "";

  // Default options
  const defaults = {
    includeSeconds: false,
    includeDate: true,
    includeTime: true,
    dateFormat: "medium", // short, medium, long, full
    timeFormat: "short", // short, medium, long, full
  };

  const opts = { ...defaults, ...options };

  try {
    // Parse the UTC timestamp
    let utcDate;

    // Handle different input formats
    if (utcTimestamp.includes("T") || utcTimestamp.includes("Z")) {
      // ISO string format
      utcDate = new Date(utcTimestamp);
    } else {
      // MySQL format "YYYY-MM-DD HH:MM:SS"
      utcDate = new Date(utcTimestamp + "Z"); // Add Z to indicate UTC
    }

    // Check if date is valid
    if (isNaN(utcDate.getTime())) {
      console.warn("Invalid UTC timestamp:", utcTimestamp);
      return utcTimestamp; // Return original if invalid
    }

    // Format options for toLocaleDateString and toLocaleTimeString
    const dateOptions = {
      short: { month: "numeric", day: "numeric", year: "2-digit" },
      medium: { month: "short", day: "numeric", year: "numeric" },
      long: { month: "long", day: "numeric", year: "numeric" },
      full: { weekday: "long", month: "long", day: "numeric", year: "numeric" },
    };

    const timeOptions = {
      short: { hour: "numeric", minute: "2-digit" },
      medium: { hour: "numeric", minute: "2-digit", second: "2-digit" },
      long: {
        hour: "numeric",
        minute: "2-digit",
        second: "2-digit",
        timeZoneName: "short",
      },
      full: {
        hour: "numeric",
        minute: "2-digit",
        second: "2-digit",
        timeZoneName: "long",
      },
    };

    let result = "";

    // Add date if requested
    if (opts.includeDate) {
      result += utcDate.toLocaleDateString(
        undefined,
        dateOptions[opts.dateFormat],
      );
    }

    // Add time if requested
    if (opts.includeTime) {
      const timeStr = utcDate.toLocaleTimeString(
        undefined,
        timeOptions[opts.timeFormat],
      );

      if (opts.includeDate) {
        result += " at " + timeStr;
      } else {
        result += timeStr;
      }
    }
    console.log(`Converted UTC "${utcTimestamp}" to local "${result}"`);
    return result;
  } catch (error) {
    console.error("Error converting UTC timestamp:", error, utcTimestamp);
    return utcTimestamp; // Return original on error
  }
}

/**
 * Convert UTC timestamp to relative time (e.g., "2 hours ago", "in 3 days")
 * @param {string} utcTimestamp - UTC timestamp
 * @param {boolean} includeSuffix - Whether to include "ago" or "from now" (default: true)
 * @returns {string} Relative time string
 */
function utcToRelativeTime(utcTimestamp, includeSuffix = true) {
  if (!utcTimestamp) return "";

  try {
    let utcDate;

    if (utcTimestamp.includes("T") || utcTimestamp.includes("Z")) {
      utcDate = new Date(utcTimestamp);
    } else {
      utcDate = new Date(utcTimestamp + "Z");
    }

    if (isNaN(utcDate.getTime())) {
      return utcTimestamp;
    }

    const now = new Date();
    const diffMs = utcDate.getTime() - now.getTime();
    const diffSeconds = Math.abs(diffMs) / 1000;
    const isPast = diffMs < 0;

    let unit, value;

    if (diffSeconds < 60) {
      const result = isPast ? "just now" : "in a moment";
      console.log(
        `Converted UTC "${utcTimestamp}" to relative time "${result}"`,
      );
      return result;
    } else if (diffSeconds < 3600) {
      // Less than 1 hour
      value = Math.floor(diffSeconds / 60);
      unit = value === 1 ? "minute" : "minutes";
    } else if (diffSeconds < 86400) {
      // Less than 1 day
      value = Math.floor(diffSeconds / 3600);
      unit = value === 1 ? "hour" : "hours";
    } else if (diffSeconds < 604800) {
      // Less than 1 week
      value = Math.floor(diffSeconds / 86400);
      unit = value === 1 ? "day" : "days";
    } else if (diffSeconds < 2592000) {
      // Less than 1 month
      value = Math.floor(diffSeconds / 604800);
      unit = value === 1 ? "week" : "weeks";
    } else if (diffSeconds < 31536000) {
      // Less than 1 year
      value = Math.floor(diffSeconds / 2592000);
      unit = value === 1 ? "month" : "months";
    } else {
      value = Math.floor(diffSeconds / 31536000);
      unit = value === 1 ? "year" : "years";
    }

    const timeStr = `${value} ${unit}`;
    if (!includeSuffix) {
      console.log(
        `Converted UTC "${utcTimestamp}" to relative time "${timeStr}"`,
      );
      return timeStr;
    }

    const result = isPast ? `${timeStr} ago` : `in ${timeStr}`;
    console.log(`Converted UTC "${utcTimestamp}" to relative time "${result}"`);
    return result;
  } catch (error) {
    console.error("Error converting to relative time:", error, utcTimestamp);
    return utcTimestamp;
  }
}

/**
 * Get user's current timezone
 * @returns {string} Timezone identifier (e.g., "America/New_York")
 */
function getUserTimezone() {
  return Intl.DateTimeFormat().resolvedOptions().timeZone;
}

/**
 * Format UTC timestamp with custom format
 * @param {string} utcTimestamp - UTC timestamp
 * @param {string} format - Format string (uses similar syntax to PHP date format)
 * @returns {string} Formatted date/time
 */
function formatUtcTimestamp(utcTimestamp, format = "M j, Y g:i A") {
  if (!utcTimestamp) return "";

  try {
    let utcDate;

    if (utcTimestamp.includes("T") || utcTimestamp.includes("Z")) {
      utcDate = new Date(utcTimestamp);
    } else {
      utcDate = new Date(utcTimestamp + "Z");
    }

    if (isNaN(utcDate.getTime())) {
      return utcTimestamp;
    }

    // Simple format implementation (similar to PHP date format)
    const formats = {
      // Year
      Y: utcDate.getFullYear(),
      y: String(utcDate.getFullYear()).slice(-2),

      // Month
      m: String(utcDate.getMonth() + 1).padStart(2, "0"),
      n: utcDate.getMonth() + 1,
      M: utcDate.toLocaleDateString("en-US", { month: "short" }),
      F: utcDate.toLocaleDateString("en-US", { month: "long" }),

      // Day
      d: String(utcDate.getDate()).padStart(2, "0"),
      j: utcDate.getDate(),
      D: utcDate.toLocaleDateString("en-US", { weekday: "short" }),
      l: utcDate.toLocaleDateString("en-US", { weekday: "long" }),

      // Time
      g: utcDate.getHours() % 12 || 12, // 12-hour format without leading zero
      G: utcDate.getHours(), // 24-hour format
      h: String(utcDate.getHours() % 12 || 12).padStart(2, "0"), // 12-hour with leading zero
      H: String(utcDate.getHours()).padStart(2, "0"), // 24-hour with leading zero
      i: String(utcDate.getMinutes()).padStart(2, "0"),
      s: String(utcDate.getSeconds()).padStart(2, "0"),
      A: utcDate.getHours() >= 12 ? "PM" : "AM",
      a: utcDate.getHours() >= 12 ? "pm" : "am",
    };

    const result = format.replace(
      /[YymdMnFDljgGhHisAa]/g,
      (match) => formats[match] || match,
    );

    console.log(
      `Formatted UTC "${utcTimestamp}" with format "${format}" to "${result}"`,
    );
    return result;
  } catch (error) {
    console.error("Error formatting UTC timestamp:", error, utcTimestamp);
    return utcTimestamp;
  }
}

/**
 * Auto-update relative time displays
 * This function finds all elements with relative time and updates them periodically
 */

/**
 * Initialize auto-updating relative time for elements with data-utc-timestamp attribute
 * @param {string} selector - CSS selector for elements to update (default: '[data-utc-timestamp]')
 * @param {number} intervalMs - Update interval in milliseconds (default: 60000 = 1 minute)
 */
function initRelativeTimeUpdates(
  selector = "[data-utc-timestamp]",
  intervalMs = 60000,
) {
  // Update all relative time elements immediately
  updateRelativeTimeElements(selector);

  // Set up periodic updates
  setInterval(() => {
    updateRelativeTimeElements(selector);
  }, intervalMs);

  console.log(
    `Initialized relative time updates for "${selector}" every ${intervalMs}ms`,
  );
}

/**
 * Update all elements matching the selector with fresh relative time
 * @param {string} selector - CSS selector for elements to update
 */
function updateRelativeTimeElements(selector = "[data-utc-timestamp]") {
  const elements = document.querySelectorAll(selector);

  elements.forEach((element) => {
    const utcTimestamp = element.getAttribute("data-utc-timestamp");
    if (utcTimestamp) {
      const relativeTime = utcToRelativeTime(utcTimestamp);
      element.textContent = relativeTime;
    }
  });
}

/**
 * Manually update a specific element's relative time
 * @param {HTMLElement} element - The element to update
 */
function updateElementRelativeTime(element) {
  const utcTimestamp = element.getAttribute("data-utc-timestamp");
  if (utcTimestamp) {
    const relativeTime = utcToRelativeTime(utcTimestamp);
    element.textContent = relativeTime;
  }
}

/**
 * Add relative time to an element and start auto-updates
 * @param {HTMLElement} element - The element to add relative time to
 * @param {string} utcTimestamp - The UTC timestamp
 * @param {boolean} autoUpdate - Whether to auto-update (default: true)
 */
function setElementRelativeTime(element, utcTimestamp, autoUpdate = true) {
  // Set the data attribute
  element.setAttribute("data-utc-timestamp", utcTimestamp);

  // Set initial relative time
  const relativeTime = utcToRelativeTime(utcTimestamp);
  element.textContent = relativeTime;

  // Add to auto-update if requested
  if (autoUpdate && !element.hasAttribute("data-relative-time-active")) {
    element.setAttribute("data-relative-time-active", "true");
    // The global update function will handle this element
  }
}

initRelativeTimeUpdates();

/**
 * Stop auto-updates for an element
 * @param {HTMLElement} element - The element to stop updating
 */
function stopElementRelativeTime(element) {
  element.removeAttribute("data-relative-time-active");
  element.removeAttribute("data-utc-timestamp");
}
