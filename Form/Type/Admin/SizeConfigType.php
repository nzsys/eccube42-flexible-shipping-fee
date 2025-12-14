<?php

namespace Plugin\FlexibleShippingFee\Form\Type\Admin;

use Plugin\FlexibleShippingFee\Entity\SizeConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SizeConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('size', IntegerType::class, [
                'label' => 'サイズ',
                'required' => true,
                'attr' => [
                    'placeholder' => '例: 60, 80, 100',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\GreaterThan(0),
                ],
            ])
            ->add('min_quantity', IntegerType::class, [
                'label' => '最小数量',
                'required' => true,
                'attr' => [
                    'placeholder' => '例: 1',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\GreaterThanOrEqual(1),
                ],
            ])
            ->add('max_quantity', IntegerType::class, [
                'label' => '最大数量',
                'required' => false,
                'attr' => [
                    'placeholder' => '例: 2 (空欄の場合は上限なし)',
                ],
                'constraints' => [
                    new Assert\GreaterThanOrEqual(1),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SizeConfig::class,
        ]);
    }
}
