<?php
declare(strict_types=1);

const QUOTE_UPLOAD_LIMIT = 10_000_000;
const QUOTE_UPLOAD_COUNT = 5;
const QUOTE_POST_LIMIT = 12 * 1024 * 1024;
const QUOTE_IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const CLEANING_ADDONS = [
    'laundry' => 'Laundry',
    'fridge' => 'Fridge cleaning',
    'oven' => 'Oven cleaning',
    'windows' => 'Interior window cleaning',
    'pets' => 'Cleaning a home with pets',
    'baseboards' => 'Baseboard cleaning',
    'cabinets' => 'Interior cabinet cleaning',
    'basement' => 'Basement cleaning',
];
const BEDROOM_COUNTS = ['0' => 'Studio / no bedrooms', '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6+' => '6 or more'];
const BATHROOM_COUNTS = ['0' => '0', '0.5' => '½', '1' => '1', '1.5' => '1½', '2' => '2', '2.5' => '2½', '3' => '3', '3.5' => '3½', '4' => '4', '4.5' => '4½', '5+' => '5 or more'];

$services = [
    'repairs' => ['General repairs', 'The small fixes that make a big difference.', 'door-open', ['Doors, handles & hinges', 'Caulking & sealing', 'Everyday household fixes']],
    'assembly' => ['Furniture assembly', 'Skip the instructions. Enjoy the finished piece.', 'armchair', ['Flat-pack furniture', 'Shelving & storage', 'Desks, beds & wardrobes']],
    'mounting' => ['Mounting & installation', 'Everything level, secure, and in the right place.', 'frame-corners', ['TV & picture mounting', 'Curtain rods & blinds', 'Mirrors & floating shelves']],
    'painting' => ['Painting & drywall', 'A fresh start for your walls.', 'paint-roller', ['Interior paint touch-ups', 'Drywall patches', 'Trim & finish painting']],
    'carpentry' => ['Carpentry & finishing', 'Thoughtful details that pull a room together.', 'ruler', ['Trim & molding', 'Shelving & storage solutions', 'Cabinet adjustments']],
    'maintenance' => ['Home maintenance', 'Stay ahead of the little things.', 'toolbox', ['Seasonal maintenance', 'Weatherstripping', 'Home to-do lists']],
];

function quoteStorage(): string
{
    return getenv('REQUEST_STORAGE') ?: '/var/lib/lion/requests';
}

function quoteRecipient(): string
{
    return filter_var(getenv('QUOTE_EMAIL_TO'), FILTER_VALIDATE_EMAIL) ?: '';
}

function quoteUploads(?array $files): array
{
    if ($files === null) return [];
    if (!is_array($files['error'] ?? null) || count($files['error']) > QUOTE_UPLOAD_COUNT) {
        throw new InvalidArgumentException('Choose up to ' . QUOTE_UPLOAD_COUNT . ' images.');
    }
    $total = 0;
    $uploads = [];
    foreach ($files['error'] as $index => $error) {
        if ($error === UPLOAD_ERR_NO_FILE) continue;
        if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new InvalidArgumentException('Keep all images together under 10 MB.');
        }
        $path = $files['tmp_name'][$index] ?? null;
        if ($error !== UPLOAD_ERR_OK || !is_string($path) || !is_uploaded_file($path)) {
            throw new InvalidArgumentException('An image could not be uploaded. Please choose it again.');
        }
        $size = filesize($path);
        $total += $size;
        if ($total > QUOTE_UPLOAD_LIMIT) {
            throw new InvalidArgumentException('Keep all images together under 10 MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        $image = @getimagesize($path);
        if (!$image || !isset(QUOTE_IMAGE_TYPES[$mime]) || $image['mime'] !== $mime) {
            throw new InvalidArgumentException('Use JPG, PNG, or WebP images.');
        }
        $filename = 'image-' . (count($uploads) + 1) . '.' . QUOTE_IMAGE_TYPES[$mime];
        $uploads[] = ['path' => $path, 'filename' => $filename, 'size' => $size, 'sha256' => hash_file('sha256', $path)];
    }
    return $uploads;
}
