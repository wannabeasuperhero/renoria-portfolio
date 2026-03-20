async function loadMainLogs() {
  const container = document.getElementById("main-log-container");

  if (!container) return;

  try {
    const response = await fetch("../assets/data/logs.json");

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

    container.innerHTML = `
      <div class="entry entry-main">
        <span class="timestamp">[system]</span>
        <div class="entry-text">Unable to load current log data.</div>
      </div>
    `;
  }
}

console.log("Renoria main interface initialized.");
loadMainLogs();
