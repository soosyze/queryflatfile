<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    $parameters = $containerConfigurator->parameters();

    // paths to refactor; solid alternative to CLI arguments
    $parameters->set(Option::PATHS, [
        __DIR__ . '/src',
        __DIR__ . '/tests'
    ]);

    // is your PHP version different from the one your refactor to? [default: your PHP version], uses PHP_VERSION_ID format
    $parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_72);

    // Define what rule sets will be applied
    $containerConfigurator->import(SetList::CODE_QUALITY);

    // Path to phpstan with extensions, that PHPSTan in Rector uses to determine types
    $parameters->set(Option::PHPSTAN_FOR_RECTOR_PATH, getcwd() . '/phpstan.neon.dist');
};
