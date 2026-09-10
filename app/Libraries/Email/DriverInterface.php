<?php

namespace App\Libraries\Email;

interface DriverInterface
{
    /**
     * Send a fully-rendered email.
     *
     * @param array $message {
     *   from_email, from_name, to_email, to_name,
     *   subject, html, text,
     *   reply_to (string|null), cc (array<string>), bcc (array<string>),
     *   headers (array<string,string>), attachments (array<{path,name?}>)
     * }
     * @return array{ok:bool, message_id?:string, error?:string}
     */
    public function send(array $message): array;

    /** Driver short-key: "brevo" | "ses" | "smtp". */
    public function key(): string;

    /** Returns true if all required credentials are present. */
    public function isConfigured(): bool;
}
