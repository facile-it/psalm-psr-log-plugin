<?php

declare(strict_types=1);

namespace Facile\Psalm\PsrLogPlugin;

use function array_reduce;
use function class_exists;
use function is_array;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

class Plugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        class_exists(Hook\LoggerHook::class);

        if ($config) {
            Hook\LoggerHook::setRequiredKeys($this->getKeys($config, 'requiredKey'));
            Hook\LoggerHook::setIgnoredkeys($this->getKeys($config, 'ignoredKey'));
        }

        $registration->registerHooksFromClass(Hook\LoggerHook::class);
    }

    /**
     * @return list<string>
     */
    private function getKeys(SimpleXMLElement $config, string $path): array
    {
        /** @var null|SimpleXMLElement|list<SimpleXMLElement> $keys */
        $keys = $config->{$path};

        if (! $keys) {
            return [];
        }

        if (! is_array($keys)) {
            $keys = [$keys];
        }

        return array_reduce(
            $keys,
            /**
             * @param list<string> $curry
             */
            function (array $curry, SimpleXMLElement $e) {
                $curry[] = (string) $e;

                return $curry;
            },
            []
        );
    }
}
