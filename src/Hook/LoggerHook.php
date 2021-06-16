<?php

declare(strict_types=1);

namespace Facile\Psalm\PsrLogPlugin\Hook;

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
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use function preg_match_all;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\InvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Plugin\Hook\AfterMethodCallAnalysisInterface;
use Psalm\StatementsSource;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Union;
use Psr\Log\LoggerInterface;

class LoggerHook implements AfterMethodCallAnalysisInterface
{
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

    public static function afterMethodCallAnalysis(
        Expr $expr,
        string $method_id,
        string $appearing_method_id,
        string $declaring_method_id,
        Context $context,
        StatementsSource $statements_source,
        Codebase $codebase,
        array &$file_replacements = [],
        Union &$return_type_candidate = null
    ): void {
        if (! $expr instanceof MethodCall) {
            return;
        }

        [$className, $methodName] = explode('::', $declaring_method_id);

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

        if (LoggerInterface::class !== $className && ! $codebase->classImplements($className, LoggerInterface::class)) {
            return;
        }

        if (count($expr->args) < 2) {
            return;
        }

        $message = $expr->args[0];
        $context = $expr->args[1];

        IssueBuffer::remove(
            $statements_source->getFilePath(),
            'ImplicitToStringCast',
            $message->getStartFilePos()
        );

        $messageString = self::getMessage($message->value, $statements_source, $codebase);

        if (null === $messageString) {
            return;
        }

        $placeholders = self::getPlaceholders($messageString);

        if (0 === count($placeholders)) {
            return;
        }

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

        $nodeTypeProvider = $statements_source->getNodeTypeProvider();
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

        $contextTypes = self::filterTypes($contextType, Atomic\TKeyedArray::class);
        $contextKeys = [];

        foreach ($contextTypes as $type) {
            $contextKeys = array_merge($contextKeys, array_keys($type->getChildNodes()));
        }

        IssueBuffer::accepts(
            new InvalidArgument(
                'Missing placeholders in context: ' . implode(', ', array_diff($placeholders, $contextKeys)),
                new CodeLocation($statements_source, $context)
            ),
            $statements_source->getSuppressedIssues()
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

    private static function getPlaceholders(string $message): array
    {
        preg_match_all('/{([a-zA-Z0-9_]+)}/', $message, $matches);

        return $matches[1] ?? [];
    }
}
