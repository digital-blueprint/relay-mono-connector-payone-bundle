<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Config;

class PaymentMethod
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string[]
     */
    private array $products;

    private ?string $templateVariant;

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string[]
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    /**
     * @param string[] $products
     */
    public function setProducts(array $products): void
    {
        $this->products = $products;
    }

    public function getTemplateVariant(): ?string
    {
        return $this->templateVariant;
    }

    public function setTemplateVariant(?string $templateVariant): void
    {
        $this->templateVariant = $templateVariant;
    }
}
