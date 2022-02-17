<?php declare(strict_types=1);

namespace Swag\Core\Checkout\Document\DocumentGenerator;

use Shopware\Core\Checkout\Document\DocumentConfiguration;
use Shopware\Core\Checkout\Document\DocumentConfigurationFactory;
use Shopware\Core\Checkout\Document\DocumentGenerator\DocumentGeneratorInterface;
use Shopware\Core\Checkout\Document\Twig\DocumentTemplateRenderer;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

class CustomDocumentGenerator implements DocumentGeneratorInterface
{
    public const DEFAULT_TEMPLATE = '@SwagCustomDoc/documents/base.html.twig';
    public const EXAMPLE_DOC = 'custom_doc';

    private string $rootDir;

    private DocumentTemplateRenderer $documentTemplateRenderer;

    public function __construct(DocumentTemplateRenderer $documentTemplateRenderer, string $rootDir)
    {
        $this->rootDir = $rootDir;
        $this->documentTemplateRenderer = $documentTemplateRenderer;
    }

    public function supports(): string
    {
        return self::EXAMPLE_DOC;
    }

    public function generate(OrderEntity $order, DocumentConfiguration $config, Context $context, ?string $templatePath = null): string
    {
        $templatePath = $templatePath ?? self::DEFAULT_TEMPLATE;

        return $this->documentTemplateRenderer->render($templatePath, [
            'order' => $order,
            'config' => DocumentConfigurationFactory::mergeConfiguration($config, new DocumentConfiguration())->jsonSerialize(),
            'rootDir' => $this->rootDir,
            'context' => $context,
        ], $context, $order->getSalesChannelId(), $order->getLanguageId(), $order->getLanguage()->getLocale()->getCode());
    }

    public function getFileName(DocumentConfiguration $config): string
    {
        return $config->getFilenamePrefix() . $config->getDocumentNumber() . $config->getFilenameSuffix();
    }
}
