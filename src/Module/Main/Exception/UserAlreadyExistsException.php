<?php

declare(strict_types=1);

namespace App\Module\Main\Exception;

final class UserAlreadyExistsException extends \RuntimeException
{
    public static function byEmail(string $email): self
    {
        return new self(sprintf('Аккаунт с email %s уже существует.', $email));
    }
}
