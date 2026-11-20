<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';

$json = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
function respond(int $status, array $data): never
{
    global $json;
    if ($json) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_THROW_ON_ERROR);
    } else {
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
$csrf = is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : '';
if (!hash_equals($_SESSION['csrf'], $csrf)) {
    respond(403, ['errors' => ['_form' => 'Your session has changed. Please try again.'], 'csrf' => $_SESSION['csrf']]);
}

$old = [];
foreach (['name', 'email', 'phone', 'zip', 'service', 'message', 'consent', 'website'] as $field) {
    $old[$field] = is_string($_POST[$field] ?? null) ? trim($_POST[$field]) : '';
}
if ($old['website'] !== '') {
    respond(422, ['errors' => ['_form' => 'Please leave the website field empty.'], 'old' => $old]);
}
$errors = [];
if (strlen($old['name']) < 2 || strlen($old['name']) > 100) $errors['name'] = 'Enter your name (2-100 characters).';
if (strlen($old['email']) > 254 || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Enter a valid email address.';
if ($old['phone'] !== '' && (!preg_match('/^[+0-9() .-]{7,40}$/', $old['phone']) || strlen(preg_replace('/\D/', '', $old['phone'])) < 7)) $errors['phone'] = 'Enter a valid phone number, or leave it blank.';
if (!preg_match('/^\d{5}(-\d{4})?$/', $old['zip'])) $errors['zip'] = 'Enter a 5-digit ZIP code.';
if (!array_key_exists($old['service'], $services) && $old['service'] !== 'other') $errors['service'] = 'Choose a service.';
if (strlen($old['message']) < 10 || strlen($old['message']) > 3000) $errors['message'] = 'Describe your project in 10-3,000 characters.';
if ($old['consent'] !== '1') $errors['consent'] = 'Please allow us to contact you about this request.';
if ($errors) respond(422, ['errors' => $errors, 'old' => $old]);

// A single append-only file keeps requests outside the public document root.
// The lock covers duplicate detection, per-client throttling, and the write.
$directory = getenv('REQUEST_STORAGE') ?: '/var/lib/lion/requests';
$handle = false;
try {
    if (!is_dir($directory) && !mkdir($directory, 0700, true)) throw new RuntimeException('Storage unavailable');
    $handle = fopen($directory . '/requests.jsonl', 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) throw new RuntimeException('Cannot lock storage');
    chmod($directory . '/requests.jsonl', 0600);
    $client = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . session_id());
    $fingerprint = hash('sha256', $csrf . json_encode($old, JSON_THROW_ON_ERROR));
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
    unset($old['website']);
    $record = ['reference' => $reference, 'timestamp' => time(), 'created_at' => gmdate(DATE_ATOM), 'client' => $client, 'fingerprint' => $fingerprint, 'project' => $old];
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
    if (is_resource($handle)) { flock($handle, LOCK_UN); fclose($handle); }
    error_log('Project request storage failed: ' . $error->getMessage());
    respond(503, ['errors' => ['_form' => 'We couldn’t save your request right now. Please try again shortly.'], 'old' => $old]);
}
