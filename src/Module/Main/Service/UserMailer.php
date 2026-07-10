<?php

declare(strict_types=1);

namespace App\Module\Main\Service;

use App\Module\Main\Entity\User;
use RuntimeException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class UserMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private string $mailFromAddress,
        private string $mailFromName,
    ) {
    }

    public function sendTestEmail(string $to): void
    {
        $email = (new Email())
            ->from(new Address($this->mailFromAddress, $this->mailFromName))
            ->to($to)
            ->subject('Тестовое письмо TaskDiary')
            ->html('<p>Symfony Mailer успешно настроен для TaskDiary.</p>');

        $this->mailer->send($email);
    }

    public function sendEmailVerification(User $user, string $verificationUrl): void
    {
        $recipient = $user->getEmail();

        if (!is_string($recipient) || trim($recipient) === '') {
            throw new RuntimeException('Не указан email пользователя для подтверждения.');
        }

        $safeUrl = htmlspecialchars($verificationUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $email = (new Email())
            ->from(new Address($this->mailFromAddress, $this->mailFromName))
            ->to($recipient)
            ->subject('Подтверждение email в TaskDiary')
            ->html(sprintf(
                '<h1>Подтверждение email</h1>
                <p>Спасибо за регистрацию в TaskDiary.</p>
                <p>Чтобы подтвердить email, перейдите по ссылке:</p>
                <p><a href="%s">Подтвердить email</a></p>
                <p>Ссылка действует 24 часа.</p>',
                $safeUrl,
            ));

        $this->mailer->send($email);
    }

    public function sendPasswordReset(User $user, string $resetUrl): void
    {
        $recipient = $user->getEmail();

        if (!is_string($recipient) || trim($recipient) === '') {
            throw new RuntimeException('Не указан email пользователя для восстановления пароля.');
        }

        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $email = (new Email())
            ->from(new Address($this->mailFromAddress, $this->mailFromName))
            ->to($recipient)
            ->subject('Восстановление пароля в TaskDiary')
            ->html(sprintf(
                '<h1>Восстановление пароля</h1>
                <p>Вы запросили восстановление пароля в TaskDiary.</p>
                <p>Чтобы задать новый пароль, перейдите по ссылке:</p>
                <p><a href="%s">Сбросить пароль</a></p>
                <p>Ссылка действует 1 час.</p>
                <p>Если вы не запрашивали восстановление пароля, просто проигнорируйте это письмо.</p>',
                $safeUrl,
            ));

        $this->mailer->send($email);
    }
}
