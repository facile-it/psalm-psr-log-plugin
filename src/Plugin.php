<?php

declare(strict_types=1);

namespace Facile\Psalm\PsrLogPlugin;

use function class_exists;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

class Plugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        class_exists(Hook\LoggerHook::class);
        $registration->registerHooksFromClass(Hook\LoggerHook::class);
    }
}
