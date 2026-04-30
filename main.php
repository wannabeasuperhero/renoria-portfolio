<?php
session_start();

$view = $_GET['view'] ?? 'home';

$allowedViews = [
    'home',
    'boards',
    'board',
    'thread',
    'new-thread',
    'edit-thread',
    'admin',
    'office'
];

if (!in_array($view, $allowedViews, true)) {
    $view = 'home';
}

function formatViewName(string $viewName): string
{
    return match ($viewName) {
        'home' => 'Main',
        'boards' => 'Boards',
        'board' => 'Board',
        'thread' => 'Thread',
        'new-thread' => 'New Thread',
        'edit-thread' => 'Edit Thread',
        'admin' => 'Admin',
        'office' => 'Office',
        default => 'Main',
    };
}

$topbarTitle = 'Renoria / ' . formatViewName($view);

$phpViewPath = __DIR__ . "/views/{$view}.php";
$htmlViewPath = __DIR__ . "/views/{$view}.html";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Renoria - Main</title>

  <link rel="icon" type="image/svg+xml" href="renoria/assets/icons/Logo_R.svg">

  <link rel="stylesheet" href="assets/css/components.css">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/boards.css">
  <link rel="stylesheet" href="assets/css/board.css">
  <link rel="stylesheet" href="assets/css/thread.css">
  <link rel="stylesheet" href="assets/css/new-thread.css">
  <link rel="stylesheet" href="assets/css/edit-thread.css">

  <?php if (!empty($_SESSION['terminal_events'])): ?>
  <script>
    window.renoriaTerminalEvents = <?= json_encode($_SESSION['terminal_events'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  </script>
  <?php unset($_SESSION['terminal_events']); ?>
  <?php endif; ?>

  <script src="assets/js/terminal.js" defer></script>
  <script src="assets/js/logs.js" defer></script>
  <script src="assets/js/main.js" defer></script>
</head>
<body>

  <main class="main-frame">
    <header class="topbar">
      <div class="status-wrap">
        <div class="dot"></div>
        <span><?= htmlspecialchars($topbarTitle, ENT_QUOTES, 'UTF-8') ?></span>
      </div>

      <a href="index.html" class="logout-button">Logout</a>
    </header>

    <section class="main-layout">

      <aside class="sidebar">
        <div class="panel terminal-panel">
          <div class="panel-heading">
            <h2 class="section-title">Terminal</h2>
          </div>

          <div class="terminal-output" id="terminal-output" aria-live="polite"></div>
        </div>

        <div class="left-actions">
          <div class="panel action-panel">
            <nav class="sidebar-nav">
              <a href="main.php?view=home">Home</a>
            </nav>
          </div>

          <div class="panel action-panel">
            <nav class="sidebar-nav">
              <a href="main.php?view=admin">Admin</a>
            </nav>
          </div>
        </div>

        <div class="panel side-bottom presence-panel">
          <div class="panel-heading">
            <h2 class="section-title">Presence</h2>
          </div>

          <div class="presence-list">
            <a href="#" class="presence-item">Renoria</a>
            <a href="#" class="presence-item">Guest</a>
          </div>
        </div>
      </aside>

      <section class="main-column">
        <div id="module-container" class="module-container">
          <?php if (file_exists($phpViewPath)): ?>
            <?php include $phpViewPath; ?>
          <?php elseif (file_exists($htmlViewPath)): ?>
            <?php include $htmlViewPath; ?>
          <?php else: ?>
            <div class="module-error">
              <h2>Module error</h2>
              <p>Unable to load requested module.</p>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <aside class="right-column">
        <div class="panel status-panel">
          <div class="panel-heading">
            <h2 class="section-title">Status</h2>
          </div>

          <p class="panel-text">Modules online: 1</p>
          <p class="panel-text">Next target:<br>boards module</p>
        </div>

        <div class="right-actions">
          <div class="panel action-panel">
            <nav class="sidebar-nav">
              <a href="main.php?view=boards">Boards</a>
            </nav>
          </div>

          <div class="panel action-panel">
            <nav class="sidebar-nav">
              <a href="main.php?view=office">Office</a>
            </nav>
          </div>
        </div>

        <div class="panel log-panel">
          <div class="panel-heading">
            <h2 class="section-title">Current log</h2>
          </div>

          <div class="log" id="main-log-container"></div>
        </div>
      </aside>

    </section>
  </main>

</body>
</html>