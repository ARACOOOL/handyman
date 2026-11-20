<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/quote.php';

$json = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
function respond(int $status, array $data): never
{
    global $json;
    if ($json) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_THROW_ON_ERROR);
    } else {
        foreach ($data['old'] ?? [] as $field => $value) {
            if (is_string($value)) {
                $data['old'][$field] = mb_substr(mb_scrub($value), 0, $field === 'message' ? 3000 : 254);
            }
        }
        if (isset($data['errors']) && !empty($_FILES['attachments']['tmp_name'])) {
            foreach ((array) $_FILES['attachments']['tmp_name'] as $path) {
                if (is_string($path) && $path !== '') $data['reattach'] = true;
            }
        }
        $_SESSION['flash'] = $data;
        header('Location: /#quote', true, 303);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed');
}
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > QUOTE_POST_LIMIT) {
    respond(413, ['errors' => ['_form' => 'This request is too large. Keep all images together under 10 MB.']]);
}
$csrf = is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : '';
if (!hash_equals($_SESSION['csrf'], $csrf)) {
    respond(403, ['errors' => ['_form' => 'Your session has changed. Please try again.'], 'csrf' => $_SESSION['csrf']]);
}

$old = [];
foreach (['name', 'email', 'phone', 'zip', 'work_type', 'service', 'bedrooms', 'bathrooms', 'message', 'consent', 'website'] as $field) {
    $old[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
}
if ($old['website'] !== '') {
    respond(422, ['errors' => ['_form' => 'Please leave the website field empty.'], 'old' => $old]);
}
$errors = [];
if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 100 || !mb_check_encoding($old['name'])) $errors['name'] = 'Enter your name (2-100 characters).';
if (strlen($old['email']) > 254 || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
if ($old['phone'] !== '' && (!preg_match('/^[+0-9() .-]{7,40}$/', $old['phone']) || strlen(preg_replace('/\D/', '', $old['phone'])) < 7)) $errors['phone'] = 'Enter a valid phone number, or leave it blank.';
if (!preg_match('/^\d{5}(-\d{4})?$/', $old['zip'])) $errors['zip'] = 'Enter a 5-digit ZIP code.';
$addons = $_POST['addons'] ?? [];
$old['addons'] = [];
// Older forms already open in a browser submit a handyman service without a work type.
if (!array_key_exists('work_type', $_POST) && $old['service'] !== '') $old['work_type'] = 'handyman';
if ($old['work_type'] === 'cleaning') {
    if (!isset(BEDROOM_COUNTS[$old['bedrooms']])) $errors['bedrooms'] = 'Choose the number of bedrooms.';
    if (!isset(BATHROOM_COUNTS[$old['bathrooms']])) $errors['bathrooms'] = 'Choose the number of bathrooms.';
    $old['service'] = '';
    if (is_array($addons)) {
        foreach (array_keys(CLEANING_ADDONS) as $key) {
            if (in_array($key, $addons, true)) $old['addons'][] = $key;
        }
    }
    if (!is_array($addons) || count($addons) !== count($old['addons'])) {
        $errors['addons'] = 'Choose extras from the list.';
    }
} elseif ($old['work_type'] === 'handyman') {
    if (!isset($services[$old['service']]) && $old['service'] !== 'other') $errors['service'] = 'Choose a service.';
    $old['bedrooms'] = $old['bathrooms'] = '';
    if (mb_strlen($old['message']) < 10) $errors['message'] = 'Describe your project in at least 10 characters.';
} else {
    $errors['work_type'] = 'Choose cleaning or handyman.';
}
if (mb_strlen($old['message']) > 3000 || !mb_check_encoding($old['message'])) $errors['message'] = 'Enter a message of up to 3,000 characters.';
if ($old['consent'] !== '1') $errors['consent'] = 'Please allow us to contact you about this request.';
try {
    $uploads = quoteUploads($_FILES['attachments'] ?? null);
} catch (InvalidArgumentException $error) {
    $errors['attachments'] = $error->getMessage();
}
if ($errors) respond(422, ['errors' => $errors, 'old' => $old]);

// A single append-only file keeps requests outside the public document root.
// The lock covers duplicate detection, per-client throttling, and the write.
$directory = quoteStorage();
$handle = false;
$uploadDirectory = null;
$savedFiles = [];
try {
    umask(0077);
    if (!is_dir($directory) && !mkdir($directory, 0700, true)) throw new RuntimeException('Storage unavailable');
    $handle = fopen($directory . '/requests.jsonl', 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) throw new RuntimeException('Cannot lock storage');
    chmod($directory . '/requests.jsonl', 0600);
    $client = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . session_id());
    $attachments = array_map(static function (array $upload): array {
        unset($upload['path']);
        return $upload;
    }, $uploads);
    $fingerprint = hash('sha256', $csrf . json_encode([$old, $attachments], JSON_THROW_ON_ERROR));
    $recent = 0;
    while (($line = fgets($handle)) !== false) {
        $record = json_decode($line, true);
        if (!is_array($record)) continue;
        if (($record['fingerprint'] ?? '') === $fingerprint) {
            flock($handle, LOCK_UN);
            fclose($handle);
            respond(200, ['success' => true, 'reference' => $record['reference'], 'csrf' => $_SESSION['csrf']]);
        }
        if (($record['client'] ?? '') === $client && (int)($record['timestamp'] ?? 0) > time() - 3600) $recent++;
    }
    if ($recent >= 5) {
        flock($handle, LOCK_UN);
        fclose($handle);
        respond(429, ['errors' => ['_form' => 'You’ve sent several requests. Please wait an hour before sending another.'], 'old' => $old]);
    }
    $reference = 'LHS-' . strtoupper(bin2hex(random_bytes(4)));
    if ($uploads) {
        $path = $directory . '/' . $reference;
        if (!mkdir($path, 0700)) throw new RuntimeException('Cannot create upload directory');
        $uploadDirectory = $path;
        foreach ($uploads as $upload) {
            $path = $uploadDirectory . '/' . $upload['filename'];
            if (!move_uploaded_file($upload['path'], $path)) throw new RuntimeException('Cannot save image');
            $savedFiles[] = $path;
            if (!chmod($path, 0600)) throw new RuntimeException('Cannot set image permissions');
        }
    }
    $project = $old;
    unset($project['website']);
    $record = [
        'reference' => $reference,
        'timestamp' => time(),
        'created_at' => gmdate(DATE_ATOM),
        'client' => $client,
        'fingerprint' => $fingerprint,
        'project' => $project,
        'attachments' => $attachments,
        'notify' => quoteRecipient() !== '',
    ];
    $line = json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) . "\n";
    fseek($handle, 0, SEEK_END);
    $position = ftell($handle);
    if (fwrite($handle, $line) !== strlen($line) || !fflush($handle)) {
        ftruncate($handle, $position);
        throw new RuntimeException('Cannot save request');
    }
    flock($handle, LOCK_UN);
    fclose($handle);
    $handle = false;
    respond(201, ['success' => true, 'reference' => $reference, 'csrf' => $_SESSION['csrf']]);
} catch (Throwable $error) {
    foreach ($savedFiles as $path) unlink($path);
    if ($uploadDirectory !== null) rmdir($uploadDirectory);
    if (is_resource($handle)) { flock($handle, LOCK_UN); fclose($handle); }
    error_log('Project request storage failed: ' . $error->getMessage());
    respond(503, ['errors' => ['_form' => 'We couldn’t save your request right now. Please try again shortly.'], 'old' => $old]);
}
