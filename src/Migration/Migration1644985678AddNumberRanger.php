<?php declare(strict_types=1);

namespace Swag\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Migration\Traits\ImportTranslationsTrait;
use Shopware\Core\Migration\Traits\Translations;

class Migration1644985678AddNumberRanger extends MigrationStep
{
    use ImportTranslationsTrait;

    public function getCreationTimestamp(): int
    {
        return 1644985678;
    }

    public function update(Connection $connection): void
    {
        $exitDocRangeType = $connection->fetchOne('SELECT 1 FROM number_range_type WHERE technical_name =:techName',
            [
                'techName' => 'document_custom_doc',
            ]);

        if ($exitDocRangeType) {
            return;
        }

        $numberRangeId = Uuid::randomBytes();
        $numberRangeTypeId = Uuid::randomBytes();

        $this->insertNumberRange($connection, $numberRangeId, $numberRangeTypeId);
        $this->insertTranslations($connection, $numberRangeId, $numberRangeTypeId);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function insertNumberRange(Connection $connection, string $numberRangeId, string $numberRangeTypeId): void
    {
        $connection->insert('number_range_type', [
            'id' => $numberRangeTypeId,
            'global' => 0,
            'technical_name' => 'document_custom_doc',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
        ]);

        $connection->insert('number_range', [
            'id' => $numberRangeId,
            'type_id' => $numberRangeTypeId,
            'global' => 0,
            'pattern' => '{n}',
            'start' => 10000,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
        ]);

        $storefrontSalesChannelId = $this->getStorefrontSalesChannelId($connection);
        if (!$storefrontSalesChannelId) {
            return;
        }

        $connection->insert('number_range_sales_channel', [
            'id' => Uuid::randomBytes(),
            'number_range_id' => $numberRangeId,
            'sales_channel_id' => $storefrontSalesChannelId,
            'number_range_type_id' => $numberRangeTypeId,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
        ]);
    }

    private function getStorefrontSalesChannelId(Connection $connection): ?string
    {
        $sql = <<<SQL
            SELECT id
            FROM sales_channel
            WHERE type_id = :typeId
SQL;
        $salesChannelId = $connection->fetchOne($sql, [
            ':typeId' => Uuid::fromHexToBytes(Defaults::SALES_CHANNEL_TYPE_STOREFRONT)
        ]);

        if (!$salesChannelId) {
            return null;
        }

        return $salesChannelId;
    }

    private function insertTranslations(Connection $connection, string $numberRangeId, string $numberRangeTypeId): void
    {
        $numberRangeTranslations = new Translations(
            [
                'number_range_id' => $numberRangeId,
                'name' => 'Beispiel',
            ],
            [
                'number_range_id' => $numberRangeId,
                'name' => 'Example',
            ]
        );

        $numberRangeTypeTranslations = new Translations(
            [
                'number_range_type_id' => $numberRangeTypeId,
                'type_name' => 'Beispiel',
            ],
            [
                'number_range_type_id' => $numberRangeTypeId,
                'type_name' => 'Example',
            ]
        );

        $this->importTranslation(
            'number_range_translation',
            $numberRangeTranslations,
            $connection
        );

        $this->importTranslation(
            'number_range_type_translation',
            $numberRangeTypeTranslations,
            $connection
        );
    }
}
