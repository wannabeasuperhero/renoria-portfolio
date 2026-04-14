<?php
require_once __DIR__ . '/../db.php';

$stmt = $pdo->query("
    SELECT id, slug, group_name, title, description
    FROM boards
    ORDER BY 
        CASE group_name
            WHEN 'Portfolio' THEN 1
            WHEN 'External' THEN 2
            ELSE 99
        END,
        CASE slug
            WHEN 'development' THEN 1
            WHEN 'modules' THEN 2
            WHEN 'ideas' THEN 3
            WHEN 'archive' THEN 4
            WHEN 'personal' THEN 5
            WHEN 'freelance' THEN 6
            ELSE 99
        END
");
$boards = $stmt->fetchAll(PDO::FETCH_ASSOC);

$groupedBoards = [];

foreach ($boards as $board) {
    $statsStmt = $pdo->prepare("
        SELECT
            COUNT(*) AS thread_count,
            MAX(COALESCE(last_reply_at, updated_at, created_at)) AS latest_activity
        FROM threads
        WHERE board_id = :board_id
    ");
    $statsStmt->execute(['board_id' => $board['id']]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    $board['thread_count'] = (int) ($stats['thread_count'] ?? 0);
    $board['latest_activity'] = $stats['latest_activity'] ?? null;

    $groupedBoards[$board['group_name']][] = $board;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatShortDate(?string $datetime): string
{
    if (!$datetime) {
        return '—';
    }

    $timestamp = strtotime($datetime);

    if ($timestamp === false) {
        return '—';
    }

    return date('d/m', $timestamp);
}
?>

<div class="boards-view">
    <div class="location-title">
        <h1 class="location-name">Boards</h1>
    </div>

    <div id="boards-container">
        <?php foreach ($groupedBoards as $groupName => $groupBoards): ?>
            <section class="boards-group">
                <h2 class="boards-group-title"><?= e($groupName) ?></h2>

                <div class="boards-group-list">
                    <?php foreach ($groupBoards as $board): ?>
                        <a
                            class="board-row"
                            href="main.php?view=board&slug=<?= urlencode($board['slug']) ?>"
                            data-board-id="<?= e($board['slug']) ?>"
                        >
                            <div class="board-main">
                                <span class="board-bullet">•</span>
                                <span class="board-name"><?= e($board['title']) ?></span>
                                <span class="board-slash">/</span>
                                <span class="board-description"><?= e($board['description']) ?></span>
                            </div>

                            <div class="board-meta">
                                <span class="board-count">
                                    <?= e((string) $board['thread_count']) ?>
                                    <?= $board['thread_count'] === 1 ? 'thread' : 'threads' ?>
                                </span>
                                <span class="board-meta-separator">•</span>
                                <span class="board-last"><?= e(formatShortDate($board['latest_activity'])) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</div>
