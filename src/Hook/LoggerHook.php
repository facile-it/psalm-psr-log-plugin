<?php

declare(strict_types=1);

namespace Facile\Psalm\PsrLogPlugin\Hook;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Issue\InvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterMethodCallAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterMethodCallAnalysisEvent;
use Psalm\StatementsSource;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Union;
use Psr\Log\LoggerInterface;

use function array_diff;
use function array_filter;
use function array_keys;
use function array_merge;
use function array_reduce;
use function array_values;
use function count;
use function explode;
use function implode;
use function in_array;
use function is_a;
use function preg_match_all;

final class LoggerHook implements AfterMethodCallAnalysisInterface
{
    /** @var list<string> */
    private static $requiredKeys = [];

    /** @var list<string> */
    private static $ignoredkeys = [];

    /**
     * @param list<string> $requiredKeys
     */
    public static function setRequiredKeys(array $requiredKeys): void
    {
        self::$requiredKeys = $requiredKeys;
    }

    /**
     * @param list<string> $ignoredkeys
     */
    public static function setIgnoredkeys(array $ignoredkeys): void
    {
        self::$ignoredkeys = $ignoredkeys;
    }

    /**
     * @template T of Atomic
     *
     * @param class-string<T> $atomicFilterClass
     *
     * @return list<T>
     */
    private static function filterTypes(Union $union, string $atomicFilterClass): array
    {
        $atomics = array_filter(
            $union->getAtomicTypes(),
            function (Atomic $atomic) use ($atomicFilterClass) {
                return is_a($atomic, $atomicFilterClass);
            }
        );

        return array_values($atomics);
    }

    public static function afterMethodCallAnalysis(AfterMethodCallAnalysisEvent $event): void
    {
        $expr = $event->getExpr();

        if (! $expr instanceof MethodCall) {
            return;
        }

        [$className, $methodName] = explode('::', $event->getDeclaringMethodId());

        $allowedMethods = [
            'emergency',
            'alert',
            'critical',
            'error',
            'warning',
            'notice',
            'info',
            'debug',
        ];

        if (! in_array($methodName, $allowedMethods)) {
            return;
        }

        $codebase = $event->getCodebase();

        if (LoggerInterface::class !== $className && ! $codebase->classImplements($className, LoggerInterface::class)) {
            return;
        }

        if (count($expr->args) < 2) {
            return;
        }

        $message = $expr->args[0];
        $context = $expr->args[1];

        if (! $message instanceof Arg || ! $context instanceof Arg) {
            return;
        }

        $statementsSource = $event->getStatementsSource();

        $messageType = $statementsSource->getNodeTypeProvider()->getType($message->value);

        if ($messageType === null) {
            return;
        }

        foreach ($messageType->getAtomicTypes() as $type) {
            if ($type instanceof Atomic\TNamedObject) {
                IssueBuffer::remove(
                    $statementsSource->getFilePath(),
                    'ImplicitToStringCast',
                    $message->getStartFilePos()
                );

                IssueBuffer::remove(
                    $statementsSource->getFilePath(),
                    'InvalidCast',
                    $message->getStartFilePos()
                );

                IssueBuffer::remove(
                    $statementsSource->getFilePath(),
                    'InvalidArgument',
                    $message->getStartFilePos()
                );

                if (! $codebase->methodExists($type->value . '::__toString')) {
                    IssueBuffer::accepts(
                        new InvalidArgument(
                            "Argument 1 of {$event->getDeclaringMethodId()} expects string|Stringable, {$type->value} provided",
                            new CodeLocation($statementsSource, $context)
                        ),
                        $statementsSource->getSuppressedIssues()
                    );

                    return;
                }
            }
        }

        $messageString = self::getMessage($message->value, $statementsSource, $codebase);

        if (null === $messageString) {
            return;
        }

        $placeholders = array_merge(
            self::getPlaceholders($messageString),
            static::$requiredKeys
        );

        if (0 === count($placeholders)) {
            return;
        }

        $placeholders = array_diff(
            $placeholders,
            static::$ignoredkeys
        );

        /** @var non-empty-array<int|string, Union> $placeholdersTypes */
        $placeholdersTypes = array_reduce(
            $placeholders,
            /**
             * @param string|int $key
             */
            function (array $types, $key) {
                $types[$key] = new Union([new TMixed()]);

                return $types;
            },
            []
        );

        $nodeTypeProvider = $statementsSource->getNodeTypeProvider();
        $contextType = $nodeTypeProvider->getType($context->value);

        if (null === $contextType) {
            return;
        }

        $newContextType = new Union([
            new TKeyedArray($placeholdersTypes),
        ]);

        if ($codebase->isTypeContainedByType(
            $contextType,
            $newContextType
        )) {
            return;
        }

        /** @var Atomic\TKeyedArray[] $contextTypes */
        $contextTypes = self::filterTypes($contextType, Atomic\TKeyedArray::class);
        $contextKeys = [];

        foreach ($contextTypes as $type) {
            $contextKeys = array_merge($contextKeys, array_keys($type->properties));
        }

        IssueBuffer::accepts(
            new InvalidArgument(
                'Missing placeholders in context: ' . implode(', ', array_diff($placeholders, $contextKeys)),
                new CodeLocation($statementsSource, $context)
            ),
            $statementsSource->getSuppressedIssues()
        );
    }

    private static function getMessage(Expr $expr, StatementsSource $statementsSource, Codebase $codebase): ?string
    {
        $nodeTypeProvider = $statementsSource->getNodeTypeProvider();
        $messageType = $nodeTypeProvider->getType($expr);

        if (null === $messageType) {
            return null;
        }

        $literalStringTypes = self::filterTypes($messageType, Atomic\TLiteralString::class);

        if (count($literalStringTypes) === 0) {
            $messageTypes = self::filterTypes($messageType, Atomic\TNamedObject::class);

            if (count($messageTypes) !== 1) {
                return null;
            }

            $t = $messageTypes[0];
            $returnType = $codebase->getMethodReturnType($t->value . '::__toString', $selfClass);

            if (null === $returnType) {
                return null;
            }

            $literalStringTypes = self::filterTypes($returnType, Atomic\TLiteralString::class);
        }

        if (count($literalStringTypes) !== 1) {
            return null;
        }

        return $literalStringTypes[0]->value;
    }

    /**
     * @return list<string>
     */
    private static function getPlaceholders(string $message): array
    {
        preg_match_all('/{([a-zA-Z0-9_]+)}/', $message, $matches);

        return $matches[1] ?? [];
    }
}
