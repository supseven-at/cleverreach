<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder): void {
    $services = $container->services();
    $services->defaults()->private()->autowire()->autoconfigure();

    $services->set(\Supseven\Cleverreach\Controller\NewsletterController::class);
    $services->set(\Supseven\Cleverreach\Form\Finishers\CleverreachFinisher::class);
    $services->set(\Supseven\Cleverreach\Form\Validator\OptinValidator::class);
    $services->set(\Supseven\Cleverreach\Form\Validator\OptoutValidator::class);
    $services->set(\Supseven\Cleverreach\Service\ApiService::class);
    $services->set(\Supseven\Cleverreach\Service\ConfigurationService::class);
    $services->set(\Supseven\Cleverreach\Service\RestService::class);
    $services->set(\Supseven\Cleverreach\Service\SubscriptionService::class);
    $services->set(\Supseven\Cleverreach\Validation\Validator\OptinValidator::class);
    $services->set(\Supseven\Cleverreach\Validation\Validator\OptoutValidator::class);
};
