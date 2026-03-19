async function loadLogs() {
  const logContainer = document.getElementById("log-container");

  if (!logContainer) return;

  try {
    const response = await fetch("assets/data/logs.json");

    if (!response.ok) {
      throw new Error(`HTTP error: ${response.status}`);
    }

    const logs = await response.json();

    logs.forEach((log) => {
      const entry = document.createElement("div");
      entry.className = "entry";

      entry.innerHTML = `
        <span class="timestamp">${log.timestamp}</span>
        <div class="entry-text">${log.text}</div>
      `;

      logContainer.appendChild(entry);
    });

  } catch (error) {
    console.error("Error loading logs:", error);

    logContainer.innerHTML = `
      <div class="entry">
        <span class="timestamp">[system]</span>
        <div class="entry-text">
          Unable to load current log data.
        </div>
      </div>
    `;
  }
}
