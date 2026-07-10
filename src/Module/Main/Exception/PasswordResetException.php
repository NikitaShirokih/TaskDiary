<?php

declare(strict_types=1);

namespace App\Module\Main\Exception;

use RuntimeException;

final class PasswordResetException extends RuntimeException
{
    public static function invalidToken(): self
    {
        return new self('Ссылка восстановления пароля недействительна.');
    }

    public static function expiredToken(): self
    {
        return new self('Срок действия ссылки восстановления пароля истёк.');
    }
}
