// ========================
// LOAD MAIN LOGS
// ========================

async function loadMainLogs() {
  const container = document.getElementById("main-log-container");

  if (!container) return;

  try {
    const response = await fetch("assets/data/logs.json");

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

    terminalLog("sys", "Main log feed unavailable.", {
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

async function openBoardFromHash(boardId) {
  try {
    const response = await fetch("assets/data/boards.json");
    if (!response.ok) throw new Error(`HTTP error: ${response.status}`);

    const groups = await response.json();

    for (const group of groups) {
      const board = group.boards.find((b) => b.id === boardId);

      if (board) {
        await loadBoardView(board.id, board.name, group.group);
        return;
      }
    }

    console.warn(`Board not found for hash: ${boardId}`);
    terminalLog("sys", `Requested board not found: ${boardId}`, {
      speed: 18,
      delayAfter: 180
    });
  } catch (error) {
    console.error("Error opening board from hash:", error);
    terminalLog("sys", "Unable to restore board from URL hash.", {
      speed: 18,
      delayAfter: 180
    });
  }
}

// ========================
// LOAD BOARDS
// ========================

async function loadBoards() {
  const container = document.getElementById("boards-container");
  if (!container) return;

  try {
    const boardsResponse = await fetch("assets/data/boards.json");
    if (!boardsResponse.ok) throw new Error(`HTTP error: ${boardsResponse.status}`);
    const groups = await boardsResponse.json();

    container.innerHTML = "";

    for (const group of groups) {
      const groupEl = document.createElement("section");
      groupEl.className = "boards-group";

      groupEl.innerHTML = `
        <h2 class="boards-group-title">${group.group}</h2>
        <div class="boards-group-list"></div>
      `;

      const listEl = groupEl.querySelector(".boards-group-list");

      for (const board of group.boards) {
        let threadCount = "—";
        let lastActivity = "—";

        try {
          const boardResponse = await fetch(`assets/data/boards/${board.id}.json`);
          if (boardResponse.ok) {
            const boardData = await boardResponse.json();
            const threads = Array.isArray(boardData.threads) ? boardData.threads : [];

            threadCount = `${threads.length} ${threads.length === 1 ? "thread" : "threads"}`;

            const latestDates = threads
              .map((thread) => {
                if (thread.lastReply && (thread.lastReply.createdAt || thread.lastReply.date)) {
                  return thread.lastReply.createdAt || thread.lastReply.date;
                }
                return thread.createdAt || null;
              })
              .filter(Boolean)
              .sort()
              .reverse();

            if (latestDates.length > 0) {
              const latest = latestDates[0];
              const [datePart] = latest.split(" ");
              if (datePart) {
                const [year, month, day] = datePart.split("-");
                if (year && month && day) {
                  lastActivity = `${day}/${month}`;
                }
              }
            }
          }
        } catch (error) {
          console.warn(`Unable to load board stats for ${board.id}:`, error);
        }

        const rowEl = document.createElement("a");
        rowEl.href = "#";
        rowEl.className = "board-row";
        rowEl.dataset.boardId = board.id;

        rowEl.innerHTML = `
          <div class="board-main">
            <span class="board-bullet">•</span>
            <span class="board-name">${board.name}</span>
            <span class="board-slash">/</span>
            <span class="board-description">${board.description}</span>
          </div>
          <div class="board-meta">
            <span class="board-count">${threadCount}</span>
            <span class="board-meta-separator">•</span>
            <span class="board-last">${lastActivity}</span>
          </div>
        `;

        rowEl.addEventListener("click", (e) => {
          e.preventDefault();
          loadBoardView(board.id, board.name, group.group);
        });

        listEl.appendChild(rowEl);
      }

      container.appendChild(groupEl);
    }
  } catch (error) {
    console.error("Error loading boards:", error);

    terminalLog("sys", "Board index unavailable.", {
      speed: 18,
      delayAfter: 180
    });

    container.innerHTML = `
      <div class="module-error">
        <h2>Error</h2>
        <p>Unable to load boards.</p>
      </div>
    `;
  }
}

// ========================
// LOAD BOARD VIEW
// ========================

async function loadBoardView(boardId, boardName, groupName) {
  try {
    const response = await fetch("views/board.html");
    if (!response.ok) throw new Error(`HTTP error: ${response.status}`);

    const html = await response.text();
    moduleContainer.innerHTML = html;

    updateTopbar("boards");

    if (window.location.hash !== `#boards-${boardId}`) {
      updateHash(`boards-${boardId}`);
    }

    await loadBoardThreads(boardId, boardName, groupName);
  } catch (error) {
    console.error("Error loading board view:", error);

    terminalLog("sys", "Unable to load board view.", {
      speed: 18,
      delayAfter: 180
    });

    moduleContainer.innerHTML = `
      <div class="module-error">
        <h2>Module error</h2>
        <p>Unable to load board view.</p>
      </div>
    `;
  }
}

async function loadBoardThreads(boardId, boardName, groupName) {
  const container = document.getElementById("threads-container");
  const titleEl = document.getElementById("board-title");
  const pathEl = document.getElementById("board-path");
  const backBtn = document.getElementById("btn-back");
  const homeBoardsBtn = document.getElementById("btn-home-boards");
  const newThreadBtn = document.getElementById("btn-new-thread");

  if (titleEl) titleEl.textContent = boardName.toUpperCase();

  if (pathEl) {
    pathEl.innerHTML = `<span class="path-dim">Boards</span> / ${groupName}`;
  }

  if (backBtn) backBtn.textContent = "↩ Back";

  if (backBtn) {
    backBtn.addEventListener("click", () => {
      loadView("boards");
    });
  }

  if (homeBoardsBtn) {
    homeBoardsBtn.addEventListener("click", () => {
      loadView("boards");
    });
  }

  if (newThreadBtn) {
    newThreadBtn.addEventListener("click", () => {
      terminalLog("sys", `Thread creation requested in board: ${boardName}`, {
        speed: 18,
        delayAfter: 180
      });
      logTip("thread creation module not available in current build");
      console.log(`New thread requested for board: ${boardId}`);
    });
  }

  if (!container) return;

  try {
    const response = await fetch(`assets/data/boards/${boardId}.json`);
    if (!response.ok) throw new Error(`HTTP error: ${response.status}`);

    const data = await response.json();
    const threads = Array.isArray(data.threads) ? data.threads : [];

    container.innerHTML = "";

    if (threads.length === 0) {
      terminalLog("sys", `No threads found in board: ${boardName}`, {
        speed: 18,
        delayAfter: 180
      });

      container.innerHTML = `
        <tr>
          <td colspan="5" class="board-empty-cell">No threads yet.</td>
        </tr>
      `;
      return;
    }

    threads.forEach((thread) => {
      const row = document.createElement("tr");
      const lastReplyDate = getLastReplyDate(thread.lastReply);

      row.innerHTML = `
        <td data-label="Author">
          <div class="mobile-value">
            <div class="author-name">${thread.author}</div>
            <div class="author-date">${formatBoardDate(thread.createdAt)}</div>
          </div>
        </td>

        <td data-label="Status" class="col-status">
          <div class="mobile-value status-value">
            <span class="status-dot ${thread.status}"></span>
            <span class="status-text">${thread.status}</span>
          </div>
        </td>

        <td data-label="Thread">
          <div class="mobile-value thread-value">
            ${thread.important ? '<span class="thread-flag flag-important">!</span>' : ""}
            <span class="thread-title">${thread.title}</span>
          </div>
        </td>

        <td data-label="Replies" class="col-replies">
          <div class="mobile-value">
            ${thread.replies}
          </div>
        </td>

        <td data-label="Last Activity">
          <div class="mobile-value">
            ${
              thread.lastReply && lastReplyDate
                ? `<div class="last-reply-name">${thread.lastReply.author}</div>
                   <div class="last-reply-date">${formatBoardDate(lastReplyDate)}</div>`
                : `<span class="no-reply">—</span>`
            }
          </div>
        </td>
      `;

      row.style.cursor = "pointer";
      row.addEventListener("click", () => {
        loadThreadView(thread.id, thread.title, boardId, boardName, groupName);
      });

      container.appendChild(row);
    });
  } catch (error) {
    console.error("Error loading threads:", error);

    terminalLog("sys", `Unable to load threads for board: ${boardName}`, {
      speed: 18,
      delayAfter: 180
    });

    container.innerHTML = `
      <tr>
        <td colspan="5">Unable to load threads.</td>
      </tr>
    `;
  }
}

// ========================
// THREAD VIEW LOADER
// ========================

async function loadThreadView(threadId, threadTitle, boardId, boardName, groupName) {
  try {
    const response = await fetch("views/thread.html");
    if (!response.ok) throw new Error(`HTTP error: ${response.status}`);

    const html = await response.text();
    moduleContainer.innerHTML = html;

    updateTopbar("boards");

    const threadResponse = await fetch(`assets/data/threads/${threadId}.json`);
    if (!threadResponse.ok) throw new Error(`HTTP error: ${threadResponse.status}`);

    const threadData = await threadResponse.json();

    const pathEl = document.getElementById("thread-path");
    const titleEl = document.getElementById("thread-title");
    const postsContainer = document.getElementById("posts-container");
    const replyBox = document.getElementById("reply-box");

    const backBtn = document.getElementById("btn-back-board");
    const homeBoardsBtn = document.getElementById("btn-home-boards");
    const newReplyBtn = document.getElementById("btn-new-reply");
    const sendBtn = document.getElementById("btn-send-reply");
    const textarea = document.getElementById("reply-input");

    if (pathEl) {
      pathEl.innerHTML = `<span class="path-dim">Boards</span> / ${groupName} / ${boardName}`;
    }

    if (titleEl) titleEl.textContent = threadData.title.toUpperCase();

    if (backBtn) {
      backBtn.addEventListener("click", () => {
        loadBoardView(boardId, boardName, groupName);
      });
    }

    if (homeBoardsBtn) {
      homeBoardsBtn.addEventListener("click", () => {
        loadView("boards");
      });
    }

    if (newReplyBtn) {
      newReplyBtn.addEventListener("click", () => {
        terminalLog("sys", `Reply composer opened for thread: ${threadData.title}`, {
          speed: 18,
          delayAfter: 180
        });

        if (!replyBox) return;

        replyBox.classList.add("active");

        replyBox.scrollIntoView({
          behavior: "smooth",
          block: "center"
        });

        setTimeout(() => {
          if (textarea) textarea.focus();
        }, 250);
      });
    }

    if (postsContainer) {
      postsContainer.innerHTML = "";

      threadData.posts.forEach((post) => {
        const article = document.createElement("article");
        article.className = "forum-post";

        const paragraphs = Array.isArray(post.content)
          ? post.content.map((p) => `<p>${p}</p>`).join("")
          : `<p>${post.content}</p>`;

        article.innerHTML = `
          <aside class="post-side">
            <div class="post-author">${post.author}</div>
            <div class="post-avatar" aria-hidden="true"></div>
            <div class="post-role">${post.role || "User"}</div>
          </aside>

          <div class="post-main">
            <div class="post-topbar">
              <span class="post-date">${formatBoardDate(post.createdAt)}</span>
            </div>

            <div class="post-content">
              ${paragraphs}
            </div>
          </div>
        `;

        postsContainer.appendChild(article);
      });
    }

    if (sendBtn && textarea) {
      sendBtn.addEventListener("click", () => {
        const text = textarea.value.trim();

        if (!text) {
          terminalLog("sys", "Reply aborted: empty input.", {
            speed: 18,
            delayAfter: 160
          });
          return;
        }

        console.log("Mock reply:", text);

        terminalLog("sys", `Reply submitted to thread: ${threadData.title}`, {
          speed: 18,
          delayAfter: 180
        });
        logTip("live post submission will be handled by backend in a future build");

        textarea.value = "";

        sendBtn.textContent = "Sent";
        setTimeout(() => {
          sendBtn.textContent = "Send";
        }, 1200);
      });
    }
  } catch (error) {
    console.error("Error loading thread view:", error);

    terminalLog("sys", `Unable to load thread view: ${threadTitle}`, {
      speed: 18,
      delayAfter: 180
    });

    moduleContainer.innerHTML = `
      <div class="module-error">
        <h2>Module error</h2>
        <p>Unable to load thread view.</p>
      </div>
    `;
  }
}

// ========================
// MODULE VIEW LOADER
// ========================

const moduleContainer = document.getElementById("module-container");
const viewLinks = document.querySelectorAll("[data-view]");
const topbarTitle = document.querySelector(".status-wrap span");

const validViews = ["home", "boards", "admin", "office"];

function formatViewName(viewName) {
  if (!viewName || viewName === "home") return "Main";
  return viewName.charAt(0).toUpperCase() + viewName.slice(1);
}

function updateTopbar(viewName) {
  if (!topbarTitle) return;
  topbarTitle.textContent = `Renoria / ${formatViewName(viewName)}`;
}

function updateHash(viewName) {
  const newHash = `#${viewName}`;
  if (window.location.hash !== newHash) {
    history.replaceState(null, "", newHash);
  }
}

async function loadView(viewName, updateUrl = true) {
  let safeView = viewName;

  if (viewName.startsWith("boards-")) {
    safeView = "boards";
  } else if (!validViews.includes(viewName)) {
    safeView = "home";
  }

  try {
    const response = await fetch(`views/${safeView}.html`);

    if (!response.ok) {
      throw new Error(`Unable to load view: ${safeView}`);
    }

    const html = await response.text();
    moduleContainer.innerHTML = html;

    if (safeView === "boards") {
      await loadBoards();

      if (viewName.startsWith("boards-")) {
        const boardId = viewName.replace("boards-", "");
        await openBoardFromHash(boardId);
      }
    }

    updateTopbar(safeView);

    if (updateUrl) {
      if (viewName.startsWith("boards-")) {
        updateHash(viewName);
      } else {
        updateHash(safeView);
      }
    }
  } catch (error) {
    console.error(error);

    terminalLog("sys", `Unable to load requested module: ${safeView}`, {
      speed: 18,
      delayAfter: 180
    });

    moduleContainer.innerHTML = `
      <div class="module-error">
        <h2>Module error</h2>
        <p>Unable to load requested module.</p>
      </div>
    `;

    updateTopbar(safeView);

    if (updateUrl) {
      if (viewName.startsWith("boards-")) {
        updateHash(viewName);
      } else {
        updateHash(safeView);
      }
    }
  }
}

// click sui pulsanti
viewLinks.forEach((link) => {
  link.addEventListener("click", (event) => {
    event.preventDefault();

    const viewName = link.dataset.view;
    if (!viewName) return;

    loadView(viewName);
  });
});

// cambio hash manuale / avanti-indietro browser
window.addEventListener("hashchange", () => {
  const hashView = window.location.hash.replace("#", "");
  loadView(hashView || "home", false);
});

// ========================
// INIT
// ========================

console.log("Renoria main interface initialized.");

terminalLog("sys", "Main interface initialized.", {
  speed: 18,
  delayAfter: 180
});

loadMainLogs();

const initialView = window.location.hash.replace("#", "") || "home";
loadView(initialView, false);

if (validViews.includes(initialView) || initialView.startsWith("boards-")) {
  updateHash(initialView);
} else {
  updateHash("home");
}
