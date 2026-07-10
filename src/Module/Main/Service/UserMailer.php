<?php

declare(strict_types=1);

namespace App\Module\Main\Service;

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
}
