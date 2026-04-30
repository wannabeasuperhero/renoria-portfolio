<?php
require_once __DIR__ . '/../db.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$threadId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($threadId <= 0) {
    die('Thread non specificato.');
}

$threadStmt = $pdo->prepare("
    SELECT
        t.id,
        t.board_id,
        t.original_board_id,
        t.title,
        t.body,
        t.author_name,
        t.status,
        t.kind,
        t.is_pinned,
        t.is_important,
        b.slug AS board_slug,
        b.title AS board_title,
        b.group_name AS group_name
    FROM threads t
    JOIN boards b ON b.id = t.board_id
    WHERE t.id = :id
    LIMIT 1
");
$threadStmt->execute(['id' => $threadId]);
$thread = $threadStmt->fetch(PDO::FETCH_ASSOC);

if (!$thread) {
    die('Thread non trovato.');
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

    if ($title !== '' && $body !== '') {
        $newBoardId = (int) $thread['board_id'];
        $newOriginalBoardId = $thread['original_board_id'] !== null ? (int) $thread['original_board_id'] : null;

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

            if ((int) $thread['board_id'] !== (int) $archiveBoard['id']) {
                $newOriginalBoardId = (int) $thread['board_id'];
            }

            $newBoardId = (int) $archiveBoard['id'];
        } elseif ($thread['status'] === 'archived' && $thread['original_board_id']) {
            $newBoardId = (int) $thread['original_board_id'];
            $newOriginalBoardId = null;
        }

        $updateStmt = $pdo->prepare("
            UPDATE threads
            SET
                board_id = :board_id,
                original_board_id = :original_board_id,
                title = :title,
                body = :body,
                kind = :kind,
                status = :status,
                is_pinned = :is_pinned,
                is_important = :is_important,
                updated_at = NOW()
            WHERE id = :id
        ");

        $updateStmt->execute([
            'board_id' => $newBoardId,
            'original_board_id' => $newOriginalBoardId,
            'title' => $title,
            'body' => $body,
            'kind' => ($kind !== '' ? $kind : null),
            'status' => $status,
            'is_pinned' => $isPinned,
            'is_important' => $isImportant,
            'id' => $threadId,
        ]);

        $_SESSION['terminal_events'][] = [
            'type' => 'thr',
            'message' => 'Thread updated: ' . $title
        ];

        header('Location: main.php?view=thread&id=' . $threadId);
        exit;
    }
}
?>

<div class="edit-thread-view">
    <div class="location-title">
        <p class="location-path">
            <span class="path-dim">Boards</span> / <?= e($thread['group_name']) ?> / <?= e($thread['board_title']) ?>
        </p>
        <h1 class="location-name">Edit Thread</h1>
    </div>

    <hr class="thread-separator">

    <div class="btn-row">
        <a class="sidebar-button" href="main.php?view=thread&id=<?= urlencode((string) $thread['id']) ?>">↩ Back</a>
    </div>

    <form method="POST" class="edit-thread-form">
        <input
            type="text"
            name="title"
            class="edit-thread-input"
            value="<?= e($thread['title']) ?>"
            required
        >

        <div class="field-grid">
            <select name="kind" class="edit-thread-select">
                <option value="" <?= $thread['kind'] === null || $thread['kind'] === '' ? 'selected' : '' ?>>Kind: none</option>
                <option value="system" <?= $thread['kind'] === 'system' ? 'selected' : '' ?>>System</option>
                <option value="mission" <?= $thread['kind'] === 'mission' ? 'selected' : '' ?>>Mission</option>
                <option value="bug" <?= $thread['kind'] === 'bug' ? 'selected' : '' ?>>Bug</option>
            </select>

            <select name="status" class="edit-thread-select">
                <option value="open" <?= $thread['status'] === 'open' ? 'selected' : '' ?>>Status: open</option>
                <option value="closed" <?= $thread['status'] === 'closed' ? 'selected' : '' ?>>Status: closed</option>
                <option value="archived" <?= $thread['status'] === 'archived' ? 'selected' : '' ?>>Status: archived</option>
            </select>
        </div>

        <div class="check-row">
            <label class="check-item">
                <input type="checkbox" name="is_important" value="1" <?= (int) $thread['is_important'] === 1 ? 'checked' : '' ?>>
                Important
            </label>

            <label class="check-item">
                <input type="checkbox" name="is_pinned" value="1" <?= (int) $thread['is_pinned'] === 1 ? 'checked' : '' ?>>
                Pinned
            </label>
        </div>

        <textarea
            name="body"
            class="edit-thread-textarea"
            required
        ><?= e($thread['body']) ?></textarea>

        <div class="edit-thread-actions">
            <button type="submit" class="sidebar-button">Save</button>
        </div>
    </form>
</div>