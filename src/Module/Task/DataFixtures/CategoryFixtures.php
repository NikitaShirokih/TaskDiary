<?php

declare(strict_types=1);

namespace App\Module\Task\DataFixtures;

use App\Module\Main\DataFixtures\UserFixtures;
use App\Module\Main\Entity\User;
use App\Module\Task\Entity\Category;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class CategoryFixtures extends Fixture implements DependentFixtureInterface
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

        foreach ($this->getFixtureUsers() as $user) {
            foreach ($categories as $data) {
                $category = new Category($user);
                $category->setName($data['name']);
                $category->setColor($data['color']);
                $category->setIcon($data['icon']);
                $category->setDescription($data['description']);

                $manager->persist($category);
            }
        }

        $manager->flush();
    }

    /** @return list<User> */
    private function getFixtureUsers(): array
    {
        return [
            $this->getReference(UserFixtures::USER_REFERENCE, User::class),
            $this->getReference(UserFixtures::ADMIN_REFERENCE, User::class),
        ];
    }

    /** @return list<class-string<Fixture>> */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
