<?php

namespace Plugin\FlexibleShippingFee\Form\Type\Admin;

use Plugin\FlexibleShippingFee\Entity\ShippingRate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ShippingRateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('size', ChoiceType::class, [
                'label' => 'サイズ',
                'choices' => [
                    '60サイズ' => 60,
                    '80サイズ' => 80,
                    '100サイズ' => 100,
                ],
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('rate', NumberType::class, [
                'label' => '基本送料',
                'required' => true,
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'min' => 0,
                    'step' => 0.01,
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\GreaterThanOrEqual(0),
                ],
            ])
            ->add('cool_fee', NumberType::class, [
                'label' => 'クール便料金',
                'required' => false,
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'min' => 0,
                    'step' => 0.01,
                ],
                'constraints' => [
                    new Assert\GreaterThanOrEqual(0),
                ],
            ])
            ->add('box_fee', NumberType::class, [
                'label' => '箱代',
                'required' => false,
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'min' => 0,
                    'step' => 0.01,
                ],
                'constraints' => [
                    new Assert\GreaterThanOrEqual(0),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ShippingRate::class,
        ]);
    }
}
