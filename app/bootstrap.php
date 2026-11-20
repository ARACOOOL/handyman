<?php
declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'use_strict_mode' => true,
]);
header('Cache-Control: no-store');
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function icon(string $name, string $extra = ''): string
{
    return '<i class="ph ph-' . escape($name) . ' ' . escape($extra) . '" aria-hidden="true"></i>';
}

$services = [
    'repairs' => ['General repairs', 'The small fixes that make a big difference.', 'door-open', ['Doors, handles & hinges', 'Caulking & sealing', 'Everyday household fixes']],
    'assembly' => ['Furniture assembly', 'Skip the instructions. Enjoy the finished piece.', 'armchair', ['Flat-pack furniture', 'Shelving & storage', 'Desks, beds & wardrobes']],
    'mounting' => ['Mounting & installation', 'Everything level, secure, and in the right place.', 'frame-corners', ['TV & picture mounting', 'Curtain rods & blinds', 'Mirrors & floating shelves']],
    'painting' => ['Painting & drywall', 'A fresh start for your walls.', 'paint-roller', ['Interior paint touch-ups', 'Drywall patches', 'Trim & finish painting']],
    'carpentry' => ['Carpentry & finishing', 'Thoughtful details that pull a room together.', 'ruler', ['Trim & molding', 'Shelving & storage solutions', 'Cabinet adjustments']],
    'maintenance' => ['Home maintenance', 'Stay ahead of the little things.', 'toolbox', ['Seasonal maintenance', 'Weatherstripping', 'Home to-do lists']],
];
