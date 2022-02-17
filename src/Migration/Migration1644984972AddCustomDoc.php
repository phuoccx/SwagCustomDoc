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

        $this->addTranslations($connection, $documentTypeId);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
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
