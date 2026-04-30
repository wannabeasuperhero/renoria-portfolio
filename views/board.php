<?php
require_once __DIR__ . '/../db.php';

$slug = $_GET['slug'] ?? '';

if ($slug === '') {
    die('Board non specificata.');
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatDateParts(?string $datetime): array
{
    if (!$datetime) {
        return ['—', '—'];
    }

    $timestamp = strtotime($datetime);

    if ($timestamp === false) {
        return ['—', '—'];
    }

    return [
        date('d/m/Y', $timestamp),
        date('H:i', $timestamp),
    ];
}

function statusClass(string $status): string
{
    return match (strtolower($status)) {
        'open' => 'open',
        'closed', 'archived' => 'closed',
        default => '',
    };
}

$boardStmt = $pdo->prepare("
    SELECT id, slug, title, description, group_name
    FROM boards
    WHERE slug = :slug
    LIMIT 1
");
$boardStmt->execute(['slug' => $slug]);
$board = $boardStmt->fetch(PDO::FETCH_ASSOC);

if (!$board) {
    die('Board non trovata.');
}

$threadsStmt = $pdo->prepare("
    SELECT
        id,
        title,
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
    FROM threads
    WHERE board_id = :board_id
    ORDER BY
        is_pinned DESC,
        is_important DESC,
        COALESCE(last_reply_at, updated_at, created_at) DESC,
        id DESC
");
$threadsStmt->execute(['board_id' => $board['id']]);
$threads = $threadsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="board-view">
    <div class="location-title">
        <p class="location-path" id="board-path">
            <span class="path-dim">Boards</span> / <?= e($board['group_name']) ?>
        </p>
        <h1 class="location-name" id="board-title"><?= e($board['title']) ?></h1>
    </div>

    <hr class="board-separator">

    <div class="btn-row">
        <a class="sidebar-button" id="btn-back" href="javascript:history.back()">↩ Back</a>
        <a class="sidebar-button" id="btn-home-boards" href="main.php?view=boards">⌂ Boards</a>
        <a class="sidebar-button primary" id="btn-new-thread" href="main.php?view=new-thread&board=<?= urlencode($board['slug']) ?>">+ New</a>
    </div>

    <table class="board-table">
        <colgroup>
            <col style="width: 24%">
            <col style="width: 8%">
            <col style="width: 36%">
            <col style="width: 9%">
            <col style="width: 25%">
        </colgroup>
        <thead>
            <tr>
                <th>Author</th>
                <th class="col-status">Status</th>
                <th>Thread</th>
                <th class="col-replies">Replies</th>
                <th>Last Activity</th>
            </tr>
        </thead>

        <tbody id="threads-container">
            <?php if (!empty($threads)): ?>
                <?php foreach ($threads as $thread): ?>
                    <?php
                    $authorDate = formatDateParts($thread['created_at']);
                    $lastReplyAuthor = $thread['last_reply_author'] ?: null;
                    $lastReplyAt = $thread['last_reply_at'] ?: null;
                    $status = strtolower((string) $thread['status']);
                    $isImportant = (int) $thread['is_important'] === 1;
                    $replies = (int) $thread['replies_count'];
                    ?>
                    <tr>
                        <td data-label="Author">
                            <div class="mobile-value">
                                <span class="author-name"><?= e($thread['author_name']) ?></span>
                                <span class="author-date">
                                    <?= e($authorDate[0]) ?>
                                    <span class="date-separator">•</span>
                                    <?= e($authorDate[1]) ?>
                                </span>
                            </div>
                        </td>

                        <td class="col-status" data-label="Status">
                            <div class="mobile-value status-value">
                                <span class="status-dot <?= e(statusClass($status)) ?>"></span>
                                <span class="status-text"><?= e($status) ?></span>
                            </div>
                        </td>

                        <td data-label="Thread">
                            <div class="mobile-value thread-value">
                                <a
                                    href="main.php?view=thread&id=<?= urlencode((string) $thread['id']) ?>"
                                    class="thread-title"
                                    style="text-decoration: none; color: inherit;"
                                >
                                    <?php if ($isImportant): ?>
                                        <span class="thread-flag flag-important">!</span>
                                    <?php endif; ?>
                                    <?= e($thread['title']) ?>
                                </a>
                            </div>
                        </td>

                        <td class="col-replies" data-label="Replies">
                            <div class="mobile-value">
                                <?= e((string) $replies) ?>
                            </div>
                        </td>

                        <td data-label="Last Activity">
                            <div class="mobile-value">
                                <?php if ($lastReplyAuthor && $lastReplyAt): ?>
                                    <?php $lastDate = formatDateParts($lastReplyAt); ?>
                                    <span class="last-reply-name"><?= e($lastReplyAuthor) ?></span>
                                    <span class="last-reply-date">
                                        <?= e($lastDate[0]) ?>
                                        <span class="date-separator">•</span>
                                        <?= e($lastDate[1]) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="no-reply">No replies yet</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td class="board-empty-cell" colspan="5">
                        No threads available in this board yet.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>