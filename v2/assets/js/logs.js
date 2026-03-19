async function loadLogs(targetId = "log-container") {
  const logContainer = document.getElementById(targetId);

  if (!logContainer) return;

  try {
    const response = await fetch("../assets/data/logs.json");

    if (!response.ok) {
      throw new Error(`HTTP error: ${response.status}`);
    }

    const logs = await response.json();

    logContainer.innerHTML = "";

    logs.forEach((log) => {
      const entry = document.createElement("div");
      entry.className = "entry";

      const typeLabel = log.type ? log.type.toUpperCase() : "LOG";

      entry.innerHTML = `
        <div class="entry-header">
          <span class="timestamp">[${log.timestamp}]</span>
          <span class="log-type">${typeLabel}</span>
        </div>
        <div class="entry-text">${log.text}</div>
      `;

      logContainer.appendChild(entry);
    });

  } catch (error) {
    console.error("Error loading logs:", error);

    logContainer.innerHTML = `
      <div class="entry">
        <div class="entry-header">
          <span class="timestamp">[system]</span>
          <span class="log-type">ERROR</span>
        </div>
        <div class="entry-text">
          Unable to load current log data.
        </div>
      </div>
    `;
  }
}
