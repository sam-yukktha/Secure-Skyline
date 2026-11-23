<?php
declare(strict_types=1);

function encryption_key(): string
{
    return hash('sha256', (string) app_config('encryption_secret'), true);
}

function encrypt_value(string $plaintext, string $context = ''): string
{
    $nonce = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt(
        $plaintext,
        'aes-256-gcm',
        encryption_key(),
        OPENSSL_RAW_DATA,
        $nonce,
        $tag,
        $context,
        16
    );
    if ($ciphertext === false) {
        throw new RuntimeException('Encryption failed. Confirm that the PHP OpenSSL extension is enabled.');
    }
    return 'v1.' . base64_encode($nonce . $tag . $ciphertext);
}

function decrypt_value(string $payload, string $context = ''): string
{
    if (!str_starts_with($payload, 'v1.')) {
        throw new RuntimeException('Unsupported encrypted data format.');
    }
    $raw = base64_decode(substr($payload, 3), true);
    if ($raw === false || strlen($raw) < 29) {
        throw new RuntimeException('Encrypted data is malformed.');
    }
    $nonce = substr($raw, 0, 12);
    $tag = substr($raw, 12, 16);
    $ciphertext = substr($raw, 28);
    $plaintext = openssl_decrypt(
        $ciphertext,
        'aes-256-gcm',
        encryption_key(),
        OPENSSL_RAW_DATA,
        $nonce,
        $tag,
        $context
    );
    if ($plaintext === false) {
        throw new RuntimeException('Decryption failed because the data or authentication tag is invalid.');
    }
    return $plaintext;
}

function generate_access_token(): string
{
    return 'SKY-' . strtoupper(bin2hex(random_bytes(8)));
}

function validate_uploaded_report(array $file): array
{
    $settings = app_config('upload');
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $message = match ($file['error'] ?? UPLOAD_ERR_NO_FILE) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The report exceeds the upload size limit.',
            UPLOAD_ERR_NO_FILE => 'Choose a report file to upload.',
            default => 'The report upload failed. Please try again.',
        };
        throw new RuntimeException($message);
    }
    if (($file['size'] ?? 0) <= 0 || $file['size'] > $settings['max_bytes']) {
        throw new RuntimeException('The report must be between 1 byte and 5 MB.');
    }
    $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $settings['extensions'], true)) {
        throw new RuntimeException('Allowed report types: PDF, TXT, PNG, JPG, and JPEG.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file((string) $file['tmp_name']);
    if (!in_array($mime, $settings['mime_types'], true)) {
        throw new RuntimeException('The uploaded file content does not match an allowed report type.');
    }
    $contents = file_get_contents((string) $file['tmp_name']);
    if ($contents === false) {
        throw new RuntimeException('The uploaded report could not be read.');
    }
    return [
        'original_name' => basename((string) $file['name']),
        'stored_name' => bin2hex(random_bytes(16)) . '.' . $extension . '.enc',
        'mime_type' => $mime,
        'size_bytes' => (int) $file['size'],
        'contents' => $contents,
    ];
}
