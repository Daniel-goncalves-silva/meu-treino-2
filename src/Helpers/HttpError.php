<?php
namespace App\Helpers;
class HttpError extends \RuntimeException {
    public int $status;
    public array $errors;
    public function __construct(int $status, string $message, array $errors = []) {
        parent::__construct($message); $this->status=$status; $this->errors=$errors;
    }
}
