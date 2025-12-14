<?php

namespace Plugin\FlexibleShippingFee\Form\Type\Admin;

use Eccube\Form\Type\Master\PrefType;
use Plugin\FlexibleShippingFee\Entity\ShippingArea;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ShippingAreaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'エリア名',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('prefectures', ChoiceType::class, [
                'label' => '都道府県',
                'choices' => $this->getPrefectureChoices(),
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'mapped' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ShippingArea::class,
        ]);
    }

    private function getPrefectureChoices()
    {
        return [
            '北海道' => 1,
            '青森県' => 2,
            '岩手県' => 3,
            '宮城県' => 4,
            '秋田県' => 5,
            '山形県' => 6,
            '福島県' => 7,
            '茨城県' => 8,
            '栃木県' => 9,
            '群馬県' => 10,
            '埼玉県' => 11,
            '千葉県' => 12,
            '東京都' => 13,
            '神奈川県' => 14,
            '新潟県' => 15,
            '富山県' => 16,
            '石川県' => 17,
            '福井県' => 18,
            '山梨県' => 19,
            '長野県' => 20,
            '岐阜県' => 21,
            '静岡県' => 22,
            '愛知県' => 23,
            '三重県' => 24,
            '滋賀県' => 25,
            '京都府' => 26,
            '大阪府' => 27,
            '兵庫県' => 28,
            '奈良県' => 29,
            '和歌山県' => 30,
            '鳥取県' => 31,
            '島根県' => 32,
            '岡山県' => 33,
            '広島県' => 34,
            '山口県' => 35,
            '徳島県' => 36,
            '香川県' => 37,
            '愛媛県' => 38,
            '高知県' => 39,
            '福岡県' => 40,
            '佐賀県' => 41,
            '長崎県' => 42,
            '熊本県' => 43,
            '大分県' => 44,
            '宮崎県' => 45,
            '鹿児島県' => 46,
            '沖縄県' => 47,
        ];
    }
}
