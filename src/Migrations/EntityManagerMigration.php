<?php

declare(strict_types=1);

namespace Dbp\Relay\MonoConnectorPayoneBundle\Migrations;

use Dbp\Relay\CoreBundle\Doctrine\AbstractEntityManagerMigration;

abstract class EntityManagerMigration extends AbstractEntityManagerMigration
{
    private const EM_NAME = 'dbp_relay_mono_connector_payone_bundle';

    protected function getEntityManagerId(): string
    {
        return self::EM_NAME;
    }
}
