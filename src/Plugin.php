<?php

declare(strict_types=1);

namespace Facile\Psalm\PsrLogPlugin;

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

use function array_reduce;
use function class_exists;
use function is_array;

/**
 * @psalm-api
 */
class Plugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        class_exists(Hook\LoggerHook::class);

        if ($config !== null) {
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

        if ($keys === null) {
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
