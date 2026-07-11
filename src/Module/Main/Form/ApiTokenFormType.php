<?php

declare(strict_types=1);

namespace App\Module\Main\Form;

use App\Module\Main\Dto\ApiTokenData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<ApiTokenData>
 */
final class ApiTokenFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label' => 'Название токена',
            'constraints' => [
                new NotBlank(message: 'Введите название токена.'),
                new Length(max: 100, maxMessage: 'Название токена не должно быть длиннее {{ limit }} символов.'),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ApiTokenData::class,
        ]);
    }
}
