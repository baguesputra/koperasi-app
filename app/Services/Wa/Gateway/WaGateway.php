<?php

namespace App\Services\Wa\Gateway;

interface WaGateway
{
    public function status(): ?array;

    public function qr(): ?string;

    public function sendText(string $nomor, string $pesan): void;

    public function sendDocument(string $nomor, string $filename, string $base64, string $caption, string $mimetype): void;

    public function logout(): void;

    public function canManageSession(): bool;
}
