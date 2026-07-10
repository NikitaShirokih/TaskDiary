<?php

declare(strict_types=1);

namespace App\Module\Task\Form;

use App\Module\Task\Dto\TaskCommentData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<TaskCommentData>
 */
final class TaskCommentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('content', TextareaType::class, [
            'label' => false,
            'empty_data' => '',
            'attr' => [
                'rows' => 4,
                'placeholder' => 'Введите комментарий к задаче',
                'class' => 'form-control w-100',
            ],
            'constraints' => [
                new NotBlank(),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TaskCommentData::class,
        ]);
    }
}
