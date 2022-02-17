<?php declare(strict_types=1);

namespace Swag\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Migration\Traits\ImportTranslationsTrait;
use Shopware\Core\Migration\Traits\Translations;

class Migration1644984972AddCustomDoc extends MigrationStep
{
    use ImportTranslationsTrait;

    public function getCreationTimestamp(): int
    {
        return 1644984972;
    }

    public function update(Connection $connection): void
    {
        $exitDocType = $connection->fetchOne('SELECT 1 FROM document_type WHERE technical_name =:techName',
        [
            'techName' => 'custom_doc',
        ]);

        if ($exitDocType) {
            return;
        }

        $documentTypeId = Uuid::randomBytes();

        $connection->insert('document_type', [
            'id' => $documentTypeId,
            'technical_name' => 'custom_doc',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
        ]);

        $this->addCustomDocConfig($connection, $documentTypeId);
        $this->addTranslations($connection, $documentTypeId);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function addCustomDocConfig(Connection $connection, string $documentTypeId): void
    {
        $documentConfigId = Uuid::randomBytes();

        $connection->insert('document_base_config', [
            'id' => $documentConfigId,
            'name' => 'custom',
            'filename_prefix' => 'custom_',
            'global' => 1,
            'document_type_id' => $documentTypeId,
            'config' => $this->getConfig(),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
        ]);

        $storefrontSalesChannelId = $this->getStorefrontSalesChannelId($connection);
        if (!$storefrontSalesChannelId) {
            return;
        }

        $connection->insert('document_base_config_sales_channel', [
            'id' => Uuid::randomBytes(),
            'document_base_config_id' => $documentConfigId,
            'sales_channel_id' => $storefrontSalesChannelId,
            'document_type_id' => $documentTypeId,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)
        ]);
    }

    private function getConfig(): string
    {
        $config = [
            'displayPrices' => false,
            'displayFooter' => true,
            'displayHeader' => true,
            'displayLineItems' => true,
            'displayLineItemPosition' => true,
            'displayPageCount' => true,
            'displayCompanyAddress' => true,
            'pageOrientation' => 'portrait',
            'pageSize' => 'a4',
            'itemsPerPage' => 10,
            'companyName' => 'NFQ company',
            'companyAddress' => 'Example company address',
            'companyEmail' => 'custom@example.org'
        ];

        return json_encode($config);
    }

    private function getStorefrontSalesChannelId(Connection $connection): ?string
    {
        $salesChannelId = $connection->fetchOne('SELECT id FROM sales_channel WHERE type_id = :typeId', [
            ':typeId' => Uuid::fromHexToBytes(Defaults::SALES_CHANNEL_TYPE_STOREFRONT)
        ]);

        if (!$salesChannelId) {
            return null;
        }

        return $salesChannelId;
    }

    private function addTranslations(Connection $connection, string $documentTypeId): void
    {
        $englishName = 'Custom Document';
        $germanName = 'Beispiel Dokumententyp Name';

        $documentTypeTranslations = new Translations(
            [
                'document_type_id' => $documentTypeId,
                'name' => $germanName,
            ],
            [
                'document_type_id' => $documentTypeId,
                'name' => $englishName,
            ]
        );

        $this->importTranslation(
            'document_type_translation',
            $documentTypeTranslations,
            $connection
        );
    }
}
