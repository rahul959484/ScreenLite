<?php
declare(strict_types=1);

session_start();

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$flashMessage = $_SESSION['flash_message'] ?? '';
$flashMessageType = $_SESSION['flash_message_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_message_type']);

$message = is_string($flashMessage) ? $flashMessage : '';
$messageType = $flashMessageType === 'error' ? 'error' : 'success';
$databaseError = false;

try {
    $pdo = require __DIR__ . '/config/database.php';
} catch (Throwable $exception) {
    error_log('ScreenLite database connection failed: ' . $exception->getMessage());
    $databaseError = true;
    $message = 'The database is unavailable. Check its local connection settings and try again.';
    $messageType = 'error';
}

if (!$databaseError && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
            $name = $_POST['name'] ?? null;
            $name = is_string($name) ? trim($name) : '';

            if ($name === '') {
                throw new InvalidArgumentException('Enter a screen name.');
            }
            if (mb_strlen($name) > 150) {
                throw new InvalidArgumentException('Screen names must be 150 characters or fewer.');
            }

            $insert = $pdo->prepare(
                'INSERT INTO screens (name, device_code) VALUES (:name, :device_code)'
            );
            $created = false;

            for ($attempt = 0; $attempt < 5; $attempt++) {
                $deviceCode = 'SCR-' . strtoupper(bin2hex(random_bytes(6)));

                try {
                    $insert->execute([
                        'name' => $name,
                        'device_code' => $deviceCode,
                    ]);
                    $created = true;
                    break;
                } catch (PDOException $exception) {
                    if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) {
                        throw $exception;
                    }
                }
            }

            if (!$created) {
                throw new RuntimeException('Could not generate a unique device code.');
            }

            $message = 'Screen added successfully.';
        } elseif ($action === 'delete') {
            $screenId = filter_var(
                $_POST['screen_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($screenId === false) {
                throw new InvalidArgumentException('A valid screen ID is required.');
            }

            $delete = $pdo->prepare('DELETE FROM screens WHERE id = :id');
            $delete->execute(['id' => $screenId]);

            $message = $delete->rowCount() === 1
                ? 'Screen deleted successfully.'
                : 'That screen no longer exists.';
            $messageType = $delete->rowCount() === 1 ? 'success' : 'error';
        } else {
            throw new InvalidArgumentException('Choose a valid screen action.');
        }
    } catch (InvalidArgumentException $exception) {
        $message = $exception->getMessage();
        $messageType = 'error';
    } catch (Throwable $exception) {
        error_log('ScreenLite screen action failed: ' . $exception->getMessage());
        $message = 'The screen could not be saved. Check the database and try again.';
        $messageType = 'error';
    }

    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_message_type'] = $messageType;
    header('Location: index.php', true, 303);
    exit;
}

$screens = [];
if (!$databaseError) {
    try {
        $screens = $pdo->query(
            'SELECT id, name, device_code, last_seen_at FROM screens ORDER BY id'
        )->fetchAll();
    } catch (Throwable $exception) {
        error_log('ScreenLite screen list failed: ' . $exception->getMessage());
        $databaseError = true;
        $message = 'Screens could not be loaded. Check the database and try again.';
        $messageType = 'error';
    }
}

$indiaTimezone = new DateTimeZone('Asia/Kolkata');
$now = new DateTimeImmutable('now', $indiaTimezone);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ScreenLite - Screen Management</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem auto; max-width: 1000px; padding: 0 1rem; }
        table { border-collapse: collapse; margin-top: 1.5rem; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 0.6rem; text-align: left; }
        th { background: #f2f2f2; }
        .message { padding: 0.75rem; }
        .success { background: #e8f5e9; }
        .error { background: #ffebee; }
        .online { color: #176b2c; }
        .offline { color: #a32121; }
        form { margin: 0; }
    </style>
</head>
<body>
    <h1>Screen Management</h1>

    <?php if ($message !== ''): ?>
        <p class="message <?= escapeHtml($messageType) ?>" role="status">
            <?= escapeHtml($message) ?>
        </p>
    <?php endif; ?>

    <h2>Add a screen</h2>
    <form method="post">
        <input type="hidden" name="action" value="add">
        <label for="name">Screen name</label>
        <input id="name" name="name" type="text" maxlength="150" required>
        <button type="submit" <?= $databaseError ? 'disabled' : '' ?>>Add Screen</button>
    </form>

    <h2>All screens</h2>
    <?php if ($databaseError): ?>
        <p>The screen list is temporarily unavailable.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Screen Name</th>
                    <th>Device Code</th>
                    <th>Last Seen (IST)</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($screens === []): ?>
                    <tr><td colspan="6">No screens have been added.</td></tr>
                <?php endif; ?>
                <?php foreach ($screens as $screen): ?>
                    <?php
                    $lastSeen = null;
                    if ($screen['last_seen_at'] !== null) {
                        $lastSeen = DateTimeImmutable::createFromFormat(
                            'Y-m-d H:i:s',
                            $screen['last_seen_at'],
                            $indiaTimezone
                        );
                    }
                    $isOnline = $lastSeen !== false
                        && $lastSeen !== null
                        && $lastSeen->getTimestamp() >= $now->getTimestamp() - 120;
                    ?>
                    <tr>
                        <td><?= (int) $screen['id'] ?></td>
                        <td><?= escapeHtml($screen['name']) ?></td>
                        <td><?= escapeHtml($screen['device_code']) ?></td>
                        <td>
                            <?= $lastSeen === null || $lastSeen === false
                                ? 'Never'
                                : escapeHtml($lastSeen->format('Y-m-d H:i:s')) ?>
                        </td>
                        <td class="<?= $isOnline ? 'online' : 'offline' ?>">
                            <?= $isOnline ? 'Online' : 'Offline' ?>
                        </td>
                        <td>
                            <form method="post" onsubmit="return confirm('Delete this screen?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="screen_id" value="<?= (int) $screen['id'] ?>">
                                <button type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
