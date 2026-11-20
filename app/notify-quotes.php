<?php
declare(strict_types=1);

require __DIR__ . '/quote.php';

function quoteEmail(array $record, array $services, string $from, string $to): array
{
    $project = $record['project'];
    $reference = $record['reference'];
    $work = ucfirst($project['work_type']);
    $lines = [
        'Reference: ' . $reference,
        'Received: ' . $record['created_at'],
        'Work type: ' . $work,
        'Name: ' . $project['name'],
        'Email: ' . $project['email'],
        'Phone: ' . $project['phone'],
        'ZIP code: ' . $project['zip'],
    ];
    if ($project['work_type'] === 'cleaning') {
        $lines[] = 'Bedrooms: ' . (BEDROOM_COUNTS[$project['bedrooms']] ?? $project['bedrooms']);
        $lines[] = 'Bathrooms: ' . (BATHROOM_COUNTS[$project['bathrooms']] ?? $project['bathrooms']);
        $addons = array_map(static fn (string $key): string => CLEANING_ADDONS[$key] ?? $key, $project['addons']);
        $lines[] = 'Cleaning extras: ' . ($addons ? implode(', ', $addons) : 'None selected');
    } else {
        $lines[] = 'Service: ' . ($services[$project['service']][0] ?? 'A few things / something else');
    }
    $lines[] = "\nMessage:\n" . ($project['message'] !== '' ? $project['message'] : 'No message provided.');

    $files = [];
    foreach ($record['attachments'] as $attachment) {
        $filename = $attachment['filename'];
        if (!preg_match('/^image-[1-5]\.(jpg|png|webp)$/D', $filename)) throw new RuntimeException('Invalid attachment name');
        $file = @file_get_contents(quoteStorage() . '/' . $reference . '/' . $filename, false, null, 0, QUOTE_UPLOAD_LIMIT + 1);
        if ($file === false || hash('sha256', $file) !== $attachment['sha256']) {
            throw new RuntimeException('Attachment is missing or changed');
        }
        $files[] = ['filename' => $filename, 'content' => base64_encode($file)];
    }

    $email = [
        'from' => 'Lion Home Services <' . $from . '>',
        'to' => [$to],
        'reply_to' => $project['email'],
        'subject' => $work . ' quote request ' . $reference,
        'text' => implode("\n", $lines),
    ];
    if ($files) $email['attachments'] = $files;
    return $email;
}

function sendQuoteEmail(array $email, string $reference, string $apiKey): string
{
    $request = curl_init('https://api.resend.com/emails');
    curl_setopt_array($request, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($email, JSON_THROW_ON_ERROR),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'Idempotency-Key: quote/' . $reference,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($request);
    if ($response === false) throw new RuntimeException('Mail connection error ' . curl_errno($request));
    $status = curl_getinfo($request, CURLINFO_RESPONSE_CODE);
    $result = json_decode($response, true);
    if ($status < 200 || $status >= 300 || !is_string($result['id'] ?? null)) {
        throw new RuntimeException('Mail provider returned HTTP ' . $status);
    }
    return $result['id'];
}

$to = quoteRecipient();
if ($to === '') exit;
$from = filter_var(getenv('QUOTE_EMAIL_FROM'), FILTER_VALIDATE_EMAIL);
$apiKey = getenv('RESEND_API_KEY');
if (!$from || !$apiKey) {
    fwrite(STDERR, "Quote email needs a valid sender and Resend API key. Requests remain saved.\n");
    exit(1);
}

umask(0077);
$directory = quoteStorage();
if (!is_file($directory . '/requests.jsonl')) exit;
$state = $directory . '/notifications';
if (!is_dir($state) && !@mkdir($state, 0700) && !is_dir($state)) exit(1);
$lock = fopen($state . '/worker.lock', 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) exit;
$requests = fopen($directory . '/requests.jsonl', 'r');
if ($requests === false || !flock($requests, LOCK_SH)) exit(1);
$committedBytes = fstat($requests)['size'];
flock($requests, LOCK_UN);

while (ftell($requests) < $committedBytes && ($line = fgets($requests)) !== false) {
    if (!str_ends_with($line, "\n") || ftell($requests) > $committedBytes) break;
    $record = json_decode($line, true);
    $reference = $record['reference'] ?? '';
    if (($record['notify'] ?? false) !== true || !preg_match('/^LHS-[A-F0-9]{8}$/D', $reference)) continue;
    $sent = $state . '/' . $reference . '.sent';
    if (is_file($sent)) continue;

    try {
        $id = sendQuoteEmail(quoteEmail($record, $services, $from, $to), $reference, $apiKey);
        if (file_put_contents($sent . '.tmp', $id) !== strlen($id) || !rename($sent . '.tmp', $sent)) {
            throw new RuntimeException('Cannot record mail acceptance');
        }
        fwrite(STDOUT, "Quote email accepted for $reference.\n");
    } catch (Throwable $error) {
        fwrite(STDERR, "Quote email pending for $reference. " . $error->getMessage() . ".\n");
    }
}
fclose($requests);
fclose($lock);
