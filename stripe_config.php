<?php
declare(strict_types=1);

require_once __DIR__ . '/config/env.php';

function stripe_secret_key(): string
{
    return getenv('STRIPE_SECRET_KEY') ?: '';
}

function stripe_publishable_key(): string
{
    return getenv('STRIPE_PUBLISHABLE_KEY') ?: '';
}

function stripe_is_ready(): bool
{
    $key = stripe_secret_key();
    return $key !== '' && str_starts_with($key, 'sk_test_');
}

function stripe_base_url(): string
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $isHttps ? 'https' : 'http';
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if ($basePath === '/' || $basePath === '\\') {
        $basePath = '';
    }
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $basePath;
}

function stripe_api_request(string $method, string $endpoint, array $data = []): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'message' => 'La extension cURL de PHP no esta disponible en el servidor.'];
    }

    $secretKey = stripe_secret_key();
    if ($secretKey === '') {
        return ['ok' => false, 'message' => 'STRIPE_SECRET_KEY no esta configurada.'];
    }

    $ch = curl_init('https://api.stripe.com/v1/' . ltrim($endpoint, '/'));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_USERPWD => $secretKey . ':',
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 25,
    ]);

    if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        return ['ok' => false, 'message' => $curlError !== '' ? $curlError : 'No se pudo conectar con Stripe.'];
    }

    $decoded = json_decode($body, true);
    if ($status >= 400) {
        $message = $decoded['error']['message'] ?? 'Stripe devolvio un error.';
        return ['ok' => false, 'message' => $message, 'status' => $status];
    }

    return ['ok' => true, 'data' => $decoded];
}
