<?php
require_once __DIR__ . '/../db.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$boardSlug = $_GET['board'] ?? '';

if ($boardSlug === '') {
    die('Board non specificata.');
}

$boardStmt = $pdo->prepare("
    SELECT id, slug, title, group_name
    FROM boards
    WHERE slug = :slug
    LIMIT 1
");
$boardStmt->execute(['slug' => $boardSlug]);
$board = $boardStmt->fetch(PDO::FETCH_ASSOC);

if (!$board) {
    die('Board non trovata.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $kind = trim($_POST['kind'] ?? '');
    $status = trim($_POST['status'] ?? 'open');
    $isImportant = isset($_POST['is_important']) ? 1 : 0;
    $isPinned = isset($_POST['is_pinned']) ? 1 : 0;

    $allowedStatuses = ['open', 'closed', 'archived'];
    if (!in_array($status, $allowedStatuses, true)) {
        $status = 'open';
    }

    $authorName = 'Renoria';

    if ($title !== '' && $body !== '') {
        $targetBoardId = (int) $board['id'];
        $originalBoardId = null;

        if ($status === 'archived') {
            $archiveStmt = $pdo->prepare("
                SELECT id
                FROM boards
                WHERE slug = 'archive'
                LIMIT 1
            ");
            $archiveStmt->execute();
            $archiveBoard = $archiveStmt->fetch(PDO::FETCH_ASSOC);

            if (!$archiveBoard) {
                die('Board Archive non trovata.');
            }

            $originalBoardId = (int) $board['id'];
            $targetBoardId = (int) $archiveBoard['id'];
        }

        $insertThreadStmt = $pdo->prepare("
            INSERT INTO threads (
                board_id,
                original_board_id,
                title,
                body,
                author_name,
                status,
                kind,
                is_pinned,
                is_important,
                replies_count,
                last_reply_author,
                last_reply_at,
                created_at,
                updated_at
            )
            VALUES (
                :board_id,
                :original_board_id,
                :title,
                :body,
                :author_name,
                :status,
                :kind,
                :is_pinned,
                :is_important,
                0,
                NULL,
                NULL,
                NOW(),
                NOW()
            )
        ");

        $insertThreadStmt->execute([
            'board_id' => $targetBoardId,
            'original_board_id' => $originalBoardId,
            'title' => $title,
            'body' => $body,
            'author_name' => $authorName,
            'status' => $status,
            'kind' => ($kind !== '' ? $kind : null),
            'is_pinned' => $isPinned,
            'is_important' => $isImportant,
        ]);

        $threadId = (int) $pdo->lastInsertId();

        $_SESSION['terminal_events'][] = [
            'type' => 'thr',
            'message' => 'New thread deployed: ' . $title
        ];

        header('Location: main.php?view=thread&id=' . $threadId);
        exit;
    }
}
?>

<div class="new-thread-view">
    <div class="location-title">
        <p class="location-path">
            <span class="path-dim">Boards</span> / <?= e($board['group_name']) ?> / <?= e($board['title']) ?>
        </p>
        <h1 class="location-name">New Thread</h1>
    </div>

    <hr class="thread-separator">

    <div class="btn-row">
        <a class="sidebar-button" href="main.php?view=board&slug=<?= urlencode($board['slug']) ?>">↩ Back</a>
    </div>

    <form method="POST" class="new-thread-form">
        <input
            type="text"
            name="title"
            class="new-thread-input"
            placeholder="Thread title..."
            required
        >

        <div class="field-grid">
            <select name="kind" class="new-thread-select">
                <option value="">Kind: none</option>
                <option value="system">System</option>
                <option value="mission">Mission</option>
                <option value="bug">Bug</option>
            </select>

            <select name="status" class="new-thread-select">
                <option value="open">Status: open</option>
                <option value="closed">Status: closed</option>
                <option value="archived">Status: archived</option>
            </select>
        </div>

        <div class="check-row">
            <label class="check-item">
                <input type="checkbox" name="is_important" value="1">
                Important
            </label>

            <label class="check-item">
                <input type="checkbox" name="is_pinned" value="1">
                Pinned
            </label>
        </div>

        <textarea
            name="body"
            class="new-thread-textarea"
            placeholder="Write your opening post..."
            required
        ></textarea>

        <div class="new-thread-actions">
            <button type="submit" class="sidebar-button">Create</button>
        </div>
    </form>
</div>