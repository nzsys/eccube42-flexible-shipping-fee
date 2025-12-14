<?php

namespace Plugin\FlexibleShippingFee;

use Eccube\Common\EccubeNav;

class Nav implements EccubeNav
{
    public static function getNav()
    {
        return [
            'store' => [
                'children' => [
                    'flexible_shipping_fee' => [
                        'name' => 'フレキシブル送料',
                        'icon' => 'fa-truck',
                        'children' => [
                            'flexible_shipping_fee_area' => [
                                'name' => 'エリア管理',
                                'url' => 'flexible_shipping_fee_admin_area',
                            ],
                            'flexible_shipping_fee_rate' => [
                                'name' => '送料設定',
                                'url' => 'flexible_shipping_fee_admin_rate',
                            ],
                            'flexible_shipping_fee_size' => [
                                'name' => 'サイズ設定',
                                'url' => 'flexible_shipping_fee_admin_size',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
