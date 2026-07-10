<?php

declare(strict_types=1);

namespace App\Module\Main\Form;

use App\Module\Main\Dto\ResetPasswordData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<ResetPasswordData>
 */
final class ResetPasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'invalid_message' => 'Пароли должны совпадать.',
            'empty_data' => '',
            'first_options' => [
                'label' => 'Новый пароль',
                'empty_data' => '',
            ],
            'second_options' => [
                'label' => 'Повторите пароль',
                'empty_data' => '',
            ],
            'constraints' => [
                new NotBlank(message: 'Введите новый пароль.'),
                new Length(
                    min: 8,
                    minMessage: 'Пароль должен быть не короче {{ limit }} символов.',
                ),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ResetPasswordData::class,
        ]);
    }
}
