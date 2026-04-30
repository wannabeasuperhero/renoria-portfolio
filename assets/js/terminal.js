const terminalOutput = document.getElementById("terminal-output");

const STORAGE_KEY = "renoria_terminal_history";
const BOOT_KEY = "renoria_terminal_boot_done";
const TIP_TIME_KEY = "renoria_terminal_last_tip_at";
const MAX_HISTORY = 80;

const terminalTips = [
  "Tip: use the navigation panel to explore site modules.",
  "Tip: archived threads remain linked through board routing.",
  "Tip: system logs reflect structural updates across Renoria.",
  "Tip: thread activity may alter module visibility and status.",
  "Tip: interface stability improves after each deployed patch."
];

const terminalState = {
  queue: [],
  isTyping: false,
  bootCompleted: false
};

function scrollTerminalToBottom() {
  if (!terminalOutput) return;
  terminalOutput.scrollTop = terminalOutput.scrollHeight;
}

function readHistory() {
  try {
    return JSON.parse(sessionStorage.getItem(STORAGE_KEY) || "[]");
  } catch {
    return [];
  }
}

function writeHistory(history) {
  sessionStorage.setItem(STORAGE_KEY, JSON.stringify(history.slice(-MAX_HISTORY)));
}

function saveLine(type, message) {
  const history = readHistory();
  history.push({ type, message });
  writeHistory(history);
}

function createTerminalLine(type, message = "") {
  const line = document.createElement("div");
  line.className = `console-line ${type}`;

  const tag = document.createElement("span");
  tag.className = "console-tag";
  tag.textContent = `[${type.toUpperCase()}]`;

  const text = document.createElement("span");
  text.className = "console-text";
  text.textContent = message;

  line.append(tag, text);
  terminalOutput.appendChild(line);

  scrollTerminalToBottom();

  return { line, text };
}

function createTypingLine(type) {
  const line = document.createElement("div");
  line.className = `console-line ${type}`;

  const tag = document.createElement("span");
  tag.className = "console-tag";
  tag.textContent = `[${type.toUpperCase()}]`;

  const text = document.createElement("span");
  text.className = "console-text";

  const cursor = document.createElement("span");
  cursor.className = "terminal-cursor";
  cursor.textContent = "▌";

  line.append(tag, text, cursor);
  terminalOutput.appendChild(line);

  scrollTerminalToBottom();

  return { line, text, cursor };
}

function typeLine(type, message, speed = 22, persist = true) {
  return new Promise((resolve) => {
    if (!terminalOutput) {
      resolve();
      return;
    }

    const { text, cursor } = createTypingLine(type);
    let index = 0;

    const interval = setInterval(() => {
      text.textContent += message.charAt(index);
      index += 1;
      scrollTerminalToBottom();

      if (index >= message.length) {
        clearInterval(interval);
        cursor.remove();

        if (persist) {
          saveLine(type, message);
        }

        resolve();
      }
    }, speed);
  });
}

async function processTerminalQueue() {
  if (terminalState.isTyping) return;
  terminalState.isTyping = true;

  while (terminalState.queue.length > 0) {
    const item = terminalState.queue.shift();
    await typeLine(item.type, item.message, item.speed, item.persist);

    if (item.delayAfter) {
      await new Promise((resolve) => setTimeout(resolve, item.delayAfter));
    }
  }

  terminalState.isTyping = false;
}

function addTerminalLog(type, message, options = {}) {
  terminalState.queue.push({
    type,
    message,
    speed: options.speed ?? 20,
    delayAfter: options.delayAfter ?? 250,
    persist: options.persist ?? true
  });

  processTerminalQueue();
}

function restoreTerminalHistory() {
  if (!terminalOutput) return;

  const history = readHistory();
  terminalOutput.innerHTML = "";

  history.forEach((item) => {
    createTerminalLine(item.type, item.message);
  });

  scrollTerminalToBottom();
}

function runTerminalBootSequence() {
  const bootDone = sessionStorage.getItem(BOOT_KEY) === "1";
  if (bootDone) return;

  sessionStorage.setItem(BOOT_KEY, "1");

  addTerminalLog("sys", "Boot sequence initiated...", { speed: 34, delayAfter: 350 });
  addTerminalLog("sys", "Loading core modules...", { speed: 32, delayAfter: 300 });
  addTerminalLog("sys", "Mounting interface panels...", { speed: 30, delayAfter: 280 });
  addTerminalLog("sys", "Navigation map linked.", { speed: 28, delayAfter: 240 });
  addTerminalLog("sys", "Primary interface initialized.", { speed: 28, delayAfter: 240 });
  addTerminalLog("sys", "System status: ONLINE", { speed: 34, delayAfter: 450 });
}

function maybeEmitRandomTip() {
  const now = Date.now();
  const lastTipAt = Number(sessionStorage.getItem(TIP_TIME_KEY) || 0);
  const twoHours = 2 * 60 * 60 * 1000;

  if (now - lastTipAt < twoHours) return;

  const randomTip = terminalTips[Math.floor(Math.random() * terminalTips.length)];
  sessionStorage.setItem(TIP_TIME_KEY, String(now));
  addTerminalLog("tip", randomTip, { speed: 18, delayAfter: 180 });
}

function flushBackendEvents() {
  const events = window.renoriaTerminalEvents || [];
  if (!Array.isArray(events) || events.length === 0) return;

  events.forEach((event) => {
    if (!event?.type || !event?.message) return;
    addTerminalLog(event.type, event.message, { speed: 18, delayAfter: 180 });
  });

  window.renoriaTerminalEvents = [];
}

document.addEventListener("DOMContentLoaded", () => {
  restoreTerminalHistory();
  runTerminalBootSequence();
  flushBackendEvents();
  maybeEmitRandomTip();
});

window.addTerminalLog = addTerminalLog;
window.runTerminalBootSequence = runTerminalBootSequence;