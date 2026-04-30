// ========================
// LOAD MAIN LOGS
// ========================

async function loadMainLogs() {
  const container = document.getElementById("main-log-container");

  if (!container) return;

  try {
    const response = await fetch("/renoria/assets/data/logs.json");

    if (!response.ok) {
      throw new Error(`HTTP error: ${response.status}`);
    }

    const logs = await response.json();

    container.innerHTML = "";

    logs.slice(0, 4).forEach((log) => {
      const entry = document.createElement("div");
      entry.className = "entry entry-main";

      entry.innerHTML = `
        <span class="timestamp">[${log.timestamp}]</span>
        <div class="entry-text">${log.text}</div>
      `;

      container.appendChild(entry);
    });
  } catch (error) {
    console.error("Error loading main logs:", error);

    terminalLog("sys", "Main log stream unreachable.", {
      speed: 18,
      delayAfter: 180
    });

    container.innerHTML = `
      <div class="entry entry-main">
        <span class="timestamp">[system]</span>
        <div class="entry-text">Unable to load current log data.</div>
      </div>
    `;
  }
}

// ========================
// HELPERS
// ========================

function formatBoardDate(dateString) {
  if (!dateString) return "—";

  const [datePart, timePart] = dateString.split(" ");
  if (!datePart || !timePart) return dateString;

  const [year, month, day] = datePart.split("-");
  if (!year || !month || !day) return dateString;

  return `${day}/${month}/${year}<span class="date-separator">•</span>${timePart}`;
}

function getLastReplyDate(lastReply) {
  if (!lastReply) return null;
  return lastReply.createdAt || lastReply.date || null;
}

function terminalLog(type, message, options = {}) {
  if (typeof window.addTerminalLog === "function") {
    window.addTerminalLog(type, message, options);
  }
}

function logTip(message) {
  terminalLog("tip", `Tip: ${message}`);
}

// ========================
// BACKEND EVENTS
// ========================

function flushBackendEvents() {
  const events = window.renoriaTerminalEvents || [];

  if (!Array.isArray(events) || events.length === 0) return;

  events.forEach((event) => {
    if (!event?.type || !event?.message) return;

    terminalLog(event.type, event.message, {
      speed: 18,
      delayAfter: 180
    });
  });

  window.renoriaTerminalEvents = [];
}

// ========================
// INIT
// ========================

console.log("Renoria main interface initialized.");

document.addEventListener("DOMContentLoaded", () => {
  loadMainLogs();
  flushBackendEvents();
});