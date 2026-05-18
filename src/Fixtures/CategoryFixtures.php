<?php

declare(strict_types=1);

namespace App\Fixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = [
            ['name' => 'Работа',        'color' => '#3498db', 'icon' => 'briefcase',    'description' => 'Рабочие задачи и проекты'],
            ['name' => 'Личное',        'color' => '#2ecc71', 'icon' => 'person',        'description' => 'Личные дела и цели'],
            ['name' => 'Учёба',         'color' => '#9b59b6', 'icon' => 'book',          'description' => 'Обучение и саморазвитие'],
            ['name' => 'Здоровье',      'color' => '#e74c3c', 'icon' => 'heart-pulse',   'description' => 'Спорт, здоровье и питание'],
            ['name' => 'Финансы',       'color' => '#f39c12', 'icon' => 'currency-dollar', 'description' => 'Финансовые задачи и планирование'],
            ['name' => 'Покупки',       'color' => '#1abc9c', 'icon' => 'cart3',         'description' => 'Список покупок'],
            ['name' => 'Путешествия',   'color' => '#e67e22', 'icon' => 'airplane',      'description' => 'Планирование поездок'],
            ['name' => 'Разное',        'color' => '#95a5a6', 'icon' => 'three-dots',    'description' => 'Всё остальное'],
        ];

        foreach ($categories as $data) {
            $category = new Category();
            $category->setName($data['name']);
            $category->setColor($data['color']);
            $category->setIcon($data['icon']);
            $category->setDescription($data['description']);

            $manager->persist($category);
        }

        $manager->flush();
    }
}
