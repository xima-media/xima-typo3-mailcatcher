<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (
    ContainerConfigurator $containerConfigurator,
    ContainerBuilder $containerBuilder
): void {
    $services = $containerConfigurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $excludes = ['../Classes/Domain/Model/'];

    // Prevent autowiring of unsupported controller (v10 is legacy)
    if (!$containerBuilder->hasDefinition(\TYPO3\CMS\Backend\Template\ModuleTemplateFactory::class)) {
        $excludes[] = '../Classes/Controller/BackendController.php';
    } else {
        $excludes[] = '../Classes/Controller/LegacyBackendController.php';
    }

    $services->load('Xima\\XimaTypo3Mailcatcher\\', '../Classes/')
        ->exclude($excludes);
};
