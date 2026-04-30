<?php
require_once __DIR__ . '/../db.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatBoardDate(?string $datetime): string
{
    if (!$datetime) {
        return '—';
    }

    $timestamp = strtotime($datetime);

    if ($timestamp === false) {
        return '—';
    }

    return date('d/m/Y H:i', $timestamp);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedThreadId = (int) ($_POST['thread_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    if ($postedThreadId > 0 && $content !== '') {
        $authorName = 'Renoria';

        $threadInfoStmt = $pdo->prepare("
            SELECT id, title, status
            FROM threads
            WHERE id = :thread_id
            LIMIT 1
        ");
        $threadInfoStmt->execute(['thread_id' => $postedThreadId]);
        $threadInfo = $threadInfoStmt->fetch(PDO::FETCH_ASSOC);

        if ($threadInfo && $threadInfo['status'] === 'open') {
            $insertPostStmt = $pdo->prepare("
                INSERT INTO posts (
                    thread_id,
                    author_name,
                    content,
                    created_at,
                    updated_at
                )
                VALUES (
                    :thread_id,
                    :author_name,
                    :content,
                    NOW(),
                    NOW()
                )
            ");

            $insertPostStmt->execute([
                'thread_id'   => $postedThreadId,
                'author_name' => $authorName,
                'content'     => $content,
            ]);

            $updateThreadStmt = $pdo->prepare("
                UPDATE threads
                SET
                    replies_count = replies_count + 1,
                    last_reply_author = :author_name,
                    last_reply_at = NOW(),
                    updated_at = NOW()
                WHERE id = :thread_id
            ");

            $updateThreadStmt->execute([
                'author_name' => $authorName,
                'thread_id'   => $postedThreadId,
            ]);

            $_SESSION['terminal_events'][] = [
                'type' => 'thr',
                'message' => 'New reply registered in thread: ' . $threadInfo['title']
            ];
        }

        header('Location: main.php?view=thread&id=' . $postedThreadId);
        exit;
    }
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
        t.replies_count,
        t.last_reply_author,
        t.last_reply_at,
        t.created_at,
        t.updated_at,
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

$postsStmt = $pdo->prepare("
    SELECT
        id,
        thread_id,
        author_name,
        content,
        created_at,
        updated_at
    FROM posts
    WHERE thread_id = :thread_id
    ORDER BY created_at ASC, id ASC
");
$postsStmt->execute(['thread_id' => $threadId]);
$posts = $postsStmt->fetchAll(PDO::FETCH_ASSOC);

$isReplyAllowed = ($thread['status'] === 'open');
?>

<div class="thread-view">
    <div class="location-title">
        <p class="location-path" id="thread-path">
            <span class="path-dim">Boards</span> /
            <?= e($thread['group_name']) ?> /
            <?= e($thread['board_title']) ?>
        </p>

        <h1 class="location-name" id="thread-title">
            <?= strtoupper(e($thread['title'])) ?>
        </h1>
    </div>

    <hr class="thread-separator">

    <div class="btn-row">
        <a class="sidebar-button" id="btn-back-board" href="main.php?view=board&slug=<?= urlencode($thread['board_slug']) ?>">
            ↩ Back
        </a>

        <a class="sidebar-button" id="btn-home-boards" href="main.php?view=boards">
            ⌂ Boards
        </a>

        <a class="sidebar-button" href="main.php?view=edit-thread&id=<?= urlencode((string) $thread['id']) ?>">
            ✎ Edit
        </a>

        <?php if ($isReplyAllowed): ?>
            <button class="sidebar-button primary" id="btn-new-reply" type="button">
                + Reply
            </button>
        <?php endif; ?>
    </div>

    <div class="posts-container" id="posts-container">
        <article class="forum-post">
            <aside class="post-side">
                <div class="post-author"><?= e($thread['author_name']) ?></div>
                <div class="post-avatar" aria-hidden="true"></div>
                <div class="post-role">System Owner</div>
            </aside>

            <div class="post-main">
                <div class="post-topbar">
                    <span class="post-date"><?= e(formatBoardDate($thread['created_at'])) ?></span>
                </div>

                <div class="post-content">
                    <?php
                    $bodyParagraphs = preg_split("/\R{2,}/", trim((string) $thread['body']));
                    if (!$bodyParagraphs || (count($bodyParagraphs) === 1 && $bodyParagraphs[0] === '')) {
                        $bodyParagraphs = [(string) $thread['body']];
                    }
                    ?>
                    <?php foreach ($bodyParagraphs as $paragraph): ?>
                        <p><?= nl2br(e(trim($paragraph))) ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>

        <?php foreach ($posts as $post): ?>
            <?php
            $role = ($post['author_name'] === 'Renoria') ? 'System Owner' : 'User';
            $paragraphs = preg_split("/\R{2,}/", trim((string) $post['content']));
            if (!$paragraphs || (count($paragraphs) === 1 && $paragraphs[0] === '')) {
                $paragraphs = [(string) $post['content']];
            }
            ?>
            <article class="forum-post">
                <aside class="post-side">
                    <div class="post-author"><?= e($post['author_name']) ?></div>
                    <div class="post-avatar" aria-hidden="true"></div>
                    <div class="post-role"><?= e($role) ?></div>
                </aside>

                <div class="post-main">
                    <div class="post-topbar">
                        <span class="post-date"><?= e(formatBoardDate($post['created_at'])) ?></span>
                    </div>

                    <div class="post-content">
                        <?php foreach ($paragraphs as $paragraph): ?>
                            <p><?= nl2br(e(trim($paragraph))) ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($isReplyAllowed): ?>
        <form method="POST" class="reply-box" id="reply-box">
            <div class="reply-inner">
                <div class="reply-header">
                    Reply:
                </div>

                <textarea
                    id="reply-input"
                    name="content"
                    class="reply-textarea"
                    placeholder="Write your message..."
                    required
                ></textarea>

                <input type="hidden" name="thread_id" value="<?= e((string) $thread['id']) ?>">

                <div class="reply-actions">
                    <button id="btn-send-reply" class="sidebar-button" type="submit">
                        Send
                    </button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php if ($isReplyAllowed): ?>
<script>
const replyBox = document.getElementById('reply-box');
const newReplyBtn = document.getElementById('btn-new-reply');
const textarea = document.getElementById('reply-input');

if (newReplyBtn && replyBox) {
    newReplyBtn.addEventListener('click', () => {
        replyBox.classList.add('active');
        replyBox.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });

        setTimeout(() => {
            if (textarea) {
                textarea.focus();
            }
        }, 250);
    });
}
</script>
<?php endif; ?>