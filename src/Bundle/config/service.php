<?php

declare(strict_types=1);

/*
 * This file is part of the Rollerworks PdbValidator package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Rollerworks\Component\PdbValidator\Constraint\DomainNameRegistrableValidator;
use Rollerworks\Component\PdbValidator\Constraint\DomainNameSuffixValidator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()

        ->set(DomainNameRegistrableValidator::class)
            ->arg(0, service('rollerworks_pdb.pdb_manager'))
            ->tag('validator.constraint_validator')

        ->set(DomainNameSuffixValidator::class)
            ->arg(0, service('rollerworks_pdb.pdb_manager'))
            ->tag('validator.constraint_validator')
    ;
};
