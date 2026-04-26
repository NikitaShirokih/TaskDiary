<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

final class TaskData
{
    public function __construct(
        #[Assert\NotBlank(message: 'Название задачи обязательно')]
        #[Assert\Length(
            max: 255,
            maxMessage: 'Название задачи не должно быть длиннее 255 символов'
        )]
        public readonly string $title,

        #[Assert\Length(
            max: 1000,
            maxMessage: 'Описание не должно быть длиннее 1000 символов'
        )]
        public readonly ?string $description,

        #[Assert\NotBlank(message: 'Приоритет обязателен')]
        #[Assert\Choice(
            choices: ['low', 'medium', 'high'],
            message: 'Некорректный приоритет'
        )]
        public readonly string $priority,

        #[Assert\NotBlank(message: 'Статус обязателен')]
        #[Assert\Choice(
            choices: ['waiting', 'in_progress', 'completed'],
            message: 'Некорректный статус'
        )]
        public readonly string $status,

        public readonly ?\DateTimeImmutable $startTime,
        public readonly ?\DateTimeImmutable $endTime,

        public readonly ?int $categoryId,

        public readonly ?int $parentId = null,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $categoryId = $request->request->get('category');
        $parentId = $request->request->get('parent_id');

        return new self(
            title: trim((string) $request->request->get('title')),
            description: $request->request->get('description'),
            priority: $request->request->getString('priority', 'medium'),
            status: $request->request->getString('status', 'waiting'),
            startTime: self::parseDateTime($request->request->get('start_time'), 'Дата начала'),
            endTime: self::parseDateTime($request->request->get('end_time'), 'Дата окончания'),
            categoryId: null !== $categoryId && '' !== $categoryId ? (int) $categoryId : null,
            parentId: null !== $parentId && '' !== $parentId ? (int) $parentId : null,
        );
    }

    public static function forSubtask(Request $request, int $parentId): self
    {
        $base = self::fromRequest($request);

        return new self(
            title: $base->title,
            description: $base->description,
            priority: $base->priority,
            status: $base->status,
            startTime: $base->startTime,
            endTime: $base->endTime,
            categoryId: null, // подзадача не имеет своей категории
            parentId: $parentId,
        );
    }

    public function isSubtask(): bool
    {
        return null !== $this->parentId;
    }

    private static function parseDateTime(?string $value, string $fieldName): ?\DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value);

        if (false === $date) {
            throw new \InvalidArgumentException(sprintf('%s имеет некорректный формат.', $fieldName));
        }

        return $date;
    }
}
