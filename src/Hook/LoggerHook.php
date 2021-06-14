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
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
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

        $nodeTypeProvider = $statements_source->getNodeTypeProvider();

        $messageString = self::getMessage($message->value);

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

        $contextType = $nodeTypeProvider->getType($context->value);

        if (null === $contextType) {
            return;
        }

        $newContextType = new Union([
            new TKeyedArray($placeholdersTypes),
        ]);

        if (! $codebase->isTypeContainedByType(
            $contextType,
            $newContextType
        )) {
            /** @var TKeyedArray[] $contextTypes */
            $contextTypes = array_filter(
                array_values($contextType->getAtomicTypes()),
                function (Atomic $atomic) {
                    return $atomic instanceof TKeyedArray;
                }
            );
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
    }

    private static function getMessage(Expr $expr): ?string
    {
        if ($expr instanceof String_) {
            return $expr->value;
        }

        return null;
    }

    private static function getPlaceholders(string $message): array
    {
        preg_match_all('/{([a-zA-Z0-9_]+)}/', $message, $matches);

        return $matches[1] ?? [];
    }
}
