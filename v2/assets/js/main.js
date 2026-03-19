console.log("Renoria main interface initialized.");
async function loadMainLogs() {
  const container = document.getElementById("main-log-container");

  if (!container) return;

  try {
    const response = await fetch("../assets/data/logs.json");
    const logs = await response.json();

    container.innerHTML = "";

    logs.slice(0, 4).forEach(log => {
      const entry = document.createElement("div");
      entry.className = "entry entry-main";

      entry.innerHTML = `
        <span class="timestamp">[${log.timestamp}]</span>
        <div class="entry-text">${log.text}</div>
      `;

      container.appendChild(entry);
    });

  } catch (err) {
    console.error(err);
  }
}

loadMainLogs();
