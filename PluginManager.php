<?php

namespace Plugin\FlexibleShippingFee;

use Eccube\Plugin\AbstractPluginManager;
use Eccube\Common\EccubeConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginManager extends AbstractPluginManager
{
    public function enable(array $meta, ContainerInterface $container)
    {
        $this->createTables($container);
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
        $connection = $entityManager->getConnection();
        $schema = $connection->getSchemaManager();

        if (!$schema->tablesExist(['plg_flexible_shipping_area'])) {
            $connection->executeStatement("
                CREATE TABLE plg_flexible_shipping_area (
                    id INT AUTO_INCREMENT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    sort_no INT NOT NULL DEFAULT 0,
                    create_date DATETIME NOT NULL,
                    update_date DATETIME NOT NULL,
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE = InnoDB
            ");
        }

        if (!$schema->tablesExist(['plg_flexible_shipping_area_pref'])) {
            $connection->executeStatement("
                CREATE TABLE plg_flexible_shipping_area_pref (
                    id INT AUTO_INCREMENT NOT NULL,
                    area_id INT NOT NULL,
                    pref_id INT NOT NULL,
                    create_date DATETIME NOT NULL,
                    update_date DATETIME NOT NULL,
                    PRIMARY KEY(id),
                    INDEX IDX_AREA (area_id),
                    INDEX IDX_PREF (pref_id),
                    UNIQUE INDEX unique_area_pref (area_id, pref_id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE = InnoDB
            ");
        }

        if (!$schema->tablesExist(['plg_flexible_shipping_rate'])) {
            $connection->executeStatement("
                CREATE TABLE plg_flexible_shipping_rate (
                    id INT AUTO_INCREMENT NOT NULL,
                    area_id INT NOT NULL,
                    size INT NOT NULL,
                    rate DECIMAL(12, 2) NOT NULL DEFAULT 0,
                    cool_fee DECIMAL(12, 2) NOT NULL DEFAULT 0,
                    box_fee DECIMAL(12, 2) NOT NULL DEFAULT 0,
                    create_date DATETIME NOT NULL,
                    update_date DATETIME NOT NULL,
                    PRIMARY KEY(id),
                    INDEX IDX_AREA_SIZE (area_id, size),
                    UNIQUE INDEX unique_area_size (area_id, size)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE = InnoDB
            ");
        }

        if (!$schema->tablesExist(['plg_flexible_shipping_breakdown'])) {
            $connection->executeStatement("
                CREATE TABLE plg_flexible_shipping_breakdown (
                    id INT AUTO_INCREMENT NOT NULL,
                    order_id INT NOT NULL,
                    shipping_id INT NOT NULL,
                    area_name VARCHAR(255) NOT NULL,
                    size INT NOT NULL,
                    quantity INT NOT NULL,
                    base_fee DECIMAL(12, 2) NOT NULL DEFAULT 0,
                    cool_fee DECIMAL(12, 2) NOT NULL DEFAULT 0,
                    box_fee DECIMAL(12, 2) NOT NULL DEFAULT 0,
                    total_fee DECIMAL(12, 2) NOT NULL DEFAULT 0,
                    create_date DATETIME NOT NULL,
                    update_date DATETIME NOT NULL,
                    PRIMARY KEY(id),
                    INDEX IDX_ORDER (order_id),
                    INDEX IDX_SHIPPING (shipping_id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE = InnoDB
            ");
        }

        if (!$schema->tablesExist(['plg_flexible_shipping_size_config'])) {
            $connection->executeStatement("
                CREATE TABLE plg_flexible_shipping_size_config (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    size INTEGER NOT NULL,
                    min_quantity INTEGER NOT NULL,
                    max_quantity INTEGER DEFAULT NULL,
                    sort_no INTEGER NOT NULL DEFAULT 0,
                    create_date DATETIME NOT NULL,
                    update_date DATETIME NOT NULL
                )
            ");

            $connection->executeStatement("
                CREATE INDEX IDX_SORT ON plg_flexible_shipping_size_config (sort_no)
            ");
        }
    }

    private function dropTables(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $connection = $entityManager->getConnection();
        $schema = $connection->getSchemaManager();

        if ($schema->tablesExist(['plg_flexible_shipping_breakdown'])) {
            $connection->executeStatement('DROP TABLE plg_flexible_shipping_breakdown');
        }

        if ($schema->tablesExist(['plg_flexible_shipping_size_config'])) {
            $connection->executeStatement('DROP TABLE plg_flexible_shipping_size_config');
        }

        if ($schema->tablesExist(['plg_flexible_shipping_rate'])) {
            $connection->executeStatement('DROP TABLE plg_flexible_shipping_rate');
        }

        if ($schema->tablesExist(['plg_flexible_shipping_area_pref'])) {
            $connection->executeStatement('DROP TABLE plg_flexible_shipping_area_pref');
        }

        if ($schema->tablesExist(['plg_flexible_shipping_area'])) {
            $connection->executeStatement('DROP TABLE plg_flexible_shipping_area');
        }
    }

    private function insertInitialData(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $connection = $entityManager->getConnection();

        $sizeCount = $connection->executeQuery('SELECT COUNT(*) FROM plg_flexible_shipping_size_config')->fetchOne();
        if ($sizeCount == 0) {
            $connection->executeStatement("
                INSERT INTO plg_flexible_shipping_size_config (size, min_quantity, max_quantity, sort_no, create_date, update_date)
                VALUES
                    (60, 1, 2, 1, datetime('now'), datetime('now')),
                    (80, 3, 4, 2, datetime('now'), datetime('now'))
            ");
        }

        $areaCount = $connection->executeQuery('SELECT COUNT(*) FROM plg_flexible_shipping_area')->fetchOne();
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
            $connection->executeStatement(
                "INSERT INTO plg_flexible_shipping_area (name, sort_no, create_date, update_date) VALUES (?, ?, datetime('now'), datetime('now'))",
                [$areaData['name'], $index + 1]
            );
            $areaId = $connection->lastInsertId();

            foreach ($areaData['prefs'] as $prefId) {
                $connection->executeStatement(
                    "INSERT INTO plg_flexible_shipping_area_pref (area_id, pref_id, create_date, update_date) VALUES (?, ?, datetime('now'), datetime('now'))",
                    [$areaId, $prefId]
                );
            }

            $connection->executeStatement(
                "INSERT INTO plg_flexible_shipping_rate (area_id, size, rate, cool_fee, box_fee, create_date, update_date) VALUES (?, 60, ?, 275, 107, datetime('now'), datetime('now'))",
                [$areaId, $rates60[$index]]
            );

            $connection->executeStatement(
                "INSERT INTO plg_flexible_shipping_rate (area_id, size, rate, cool_fee, box_fee, create_date, update_date) VALUES (?, 80, ?, 330, 151, datetime('now'), datetime('now'))",
                [$areaId, $rates80[$index]]
            );
        }
    }
}
