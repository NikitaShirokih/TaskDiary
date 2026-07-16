<?php

declare(strict_types=1);

namespace App\Module\Task\Dto;

use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class TaskData
{
    public function __construct(
        #[Assert\NotBlank(message: 'Название задачи обязательно.')]
        #[Assert\Length(max: 255, maxMessage: 'Название задачи не должно быть длиннее 255 символов.')]
        public string $title,

        public ?string $description,

        #[Assert\NotBlank(message: 'Приоритет задачи обязателен.')]
        public string $priority,

        #[Assert\NotBlank(message: 'Статус задачи обязателен.')]
        public string $status,

        public ?DateTimeImmutable $startTime,

        public ?DateTimeImmutable $endTime,

        public string $categoryName,
    ) {
    }

}
