<?php

namespace Plugin\FlexibleShippingFee;

use Doctrine\ORM\Tools\SchemaTool;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\Pref;
use Plugin\FlexibleShippingFee\Entity\ShippingArea;
use Plugin\FlexibleShippingFee\Entity\ShippingAreaPref;
use Plugin\FlexibleShippingFee\Entity\ShippingBreakdown;
use Plugin\FlexibleShippingFee\Entity\ShippingRate;
use Plugin\FlexibleShippingFee\Entity\SizeConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginManager extends AbstractPluginManager
{
    public function install(array $meta, ContainerInterface $container)
    {
        $this->createTables($container);
    }

    public function enable(array $meta, ContainerInterface $container)
    {
        $this->insertInitialData($container);
    }

    public function disable(array $meta, ContainerInterface $container) {}

    public function uninstall(array $meta, ContainerInterface $container)
    {
        $this->dropTables($container);
    }

    private function createTables(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $metadatas = [
            $entityManager->getClassMetadata(ShippingArea::class),
            $entityManager->getClassMetadata(ShippingAreaPref::class),
            $entityManager->getClassMetadata(ShippingRate::class),
            $entityManager->getClassMetadata(ShippingBreakdown::class),
            $entityManager->getClassMetadata(SizeConfig::class),
        ];

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->updateSchema($metadatas, true);
    }

    private function dropTables(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $metadatas = [
            $entityManager->getClassMetadata(ShippingBreakdown::class),
            $entityManager->getClassMetadata(SizeConfig::class),
            $entityManager->getClassMetadata(ShippingRate::class),
            $entityManager->getClassMetadata(ShippingAreaPref::class),
            $entityManager->getClassMetadata(ShippingArea::class),
        ];

        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropSchema($metadatas);
    }

    private function insertInitialData(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $sizeConfigRepo = $entityManager->getRepository(SizeConfig::class);
        $sizeCount = count($sizeConfigRepo->findAll());

        if ($sizeCount == 0) {
            $sizeConfig60 = new SizeConfig();
            $sizeConfig60->setSize(60);
            $sizeConfig60->setMinQuantity(1);
            $sizeConfig60->setMaxQuantity(2);
            $sizeConfig60->setSortNo(1);
            $entityManager->persist($sizeConfig60);

            $sizeConfig80 = new SizeConfig();
            $sizeConfig80->setSize(80);
            $sizeConfig80->setMinQuantity(3);
            $sizeConfig80->setMaxQuantity(4);
            $sizeConfig80->setSortNo(2);
            $entityManager->persist($sizeConfig80);

            $entityManager->flush();
        }

        $areaRepo = $entityManager->getRepository(ShippingArea::class);
        $areaCount = count($areaRepo->findAll());

        if ($areaCount > 0) {
            return;
        }

        $areas = [
            ['name' => '北海道', 'prefs' => [1]],
            ['name' => '北東北', 'prefs' => [2, 3, 5]],
            ['name' => '南東北', 'prefs' => [4, 6, 7]],
            ['name' => '関東', 'prefs' => [8, 9, 10, 11, 12, 13, 14, 19]],
            ['name' => '信越', 'prefs' => [15, 20]],
            ['name' => '北陸', 'prefs' => [16, 17, 18]],
            ['name' => '中部', 'prefs' => [21, 22, 23, 24]],
            ['name' => '関西', 'prefs' => [25, 26, 27, 28, 29, 30]],
            ['name' => '中国', 'prefs' => [31, 32, 33, 34, 35]],
            ['name' => '四国', 'prefs' => [36, 37, 38, 39]],
            ['name' => '九州', 'prefs' => [40, 41, 42, 43, 44, 45, 46]],
            ['name' => '沖縄', 'prefs' => [47]],
        ];

        $rates60 = [517, 660, 737, 814, 814, 902, 902, 1067, 1155, 1155, 1309, 2332];
        $rates80 = [561, 748, 797, 874, 957, 1056, 1056, 1232, 1320, 1320, 1463, 2948];

        foreach ($areas as $index => $areaData) {
            $area = new ShippingArea();
            $area->setName($areaData['name']);
            $area->setSortNo($index + 1);
            $entityManager->persist($area);
            $entityManager->flush();

            foreach ($areaData['prefs'] as $prefId) {
                $pref = $entityManager->getRepository(Pref::class)->find($prefId);
                if ($pref) {
                    $areaPref = new ShippingAreaPref();
                    $areaPref->setShippingArea($area);
                    $areaPref->setPref($pref);
                    $entityManager->persist($areaPref);
                }
            }

            $rate60 = new ShippingRate();
            $rate60->setShippingArea($area);
            $rate60->setSize(60);
            $rate60->setRate((string)$rates60[$index]);
            $rate60->setCoolFee('275');
            $rate60->setBoxFee('107');
            $entityManager->persist($rate60);

            $rate80 = new ShippingRate();
            $rate80->setShippingArea($area);
            $rate80->setSize(80);
            $rate80->setRate((string)$rates80[$index]);
            $rate80->setCoolFee('330');
            $rate80->setBoxFee('151');
            $entityManager->persist($rate80);

            $entityManager->flush();
        }
    }
}
