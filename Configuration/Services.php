<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder): void {
    $services = $container->services();
    $services->defaults()->public()->autowire()->autoconfigure();

    $services->load('Supseven\\Cleverreach\\Controller\\', __DIR__ . '/../Classes/Controller/*');
    $services->load('Supseven\\Cleverreach\\Form\\', __DIR__ . '/../Classes/Form/*');
    $services->load('Supseven\\Cleverreach\\Service\\', __DIR__ . '/../Classes/Service/*');
    $services->load('Supseven\\Cleverreach\\Updates\\', __DIR__ . '/../Classes/Updates/*');
    $services->load('Supseven\\Cleverreach\\Validation\\Validator\\', __DIR__ . '/../Classes/Validation/Validator/*');
};
