<?php
declare(strict_types=1);

$queryString = $_SERVER['QUERY_STRING'] ?? '';
$target = 'marca.php' . ($queryString !== '' ? '?' . $queryString : '');

header('Location: ' . $target);
exit;
