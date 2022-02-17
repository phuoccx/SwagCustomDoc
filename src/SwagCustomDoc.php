<?php declare(strict_types=1);

namespace Swag;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class SwagCustomDoc extends Plugin
{
    public function uninstall(UninstallContext $uninstallContext): void
    {
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        $exitDocType = $connection->fetchOne('SELECT * FROM document_type WHERE technical_name =:techName',
            [
                'techName' => 'custom_doc',
            ]);

        $exitDoc = $connection->fetchOne('SELECT * FROM document WHERE document_type_id =:docTypeId',
            [
                'docTypeId' => $exitDocType,
            ]);

        if ($exitDoc) {
            return;
        }

        $connection->executeStatement('DELETE FROM document_type WHERE technical_name =:techName ',
        [
            'techName' => 'custom_doc'
        ]);

        $connection->executeStatement('DELETE FROM number_range_type WHERE technical_name =:techName ',
            [
                'techName' => 'document_custom_doc'
            ]);
    }
}
