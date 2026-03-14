<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityLogFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('action', ChoiceType::class, [
                'label' => 'Filter by Action',
                'choices' => [
                    'All Actions' => '',
                    'Task Created' => 'TASK_CREATED',
                    'Task Updated' => 'TASK_UPDATED',
                    'Task Deleted' => 'TASK_DELETED',
                    'Comment Added' => 'COMMENT_ADDED',
                    'Comment Deleted' => 'COMMENT_DELETED',
                ],
                'required' => false,
                'placeholder' => 'All Actions',
                'attr' => ['class' => 'form-select']
            ])
            ->add('dateFrom', DateType::class, [
                'label' => 'From Date',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('dateTo', DateType::class, [
                'label' => 'To Date',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => false, // For GET forms
            'method' => 'GET'
        ]);
    }
}