<?php
declare(strict_types=1);

namespace Mvc4us\Serializer;

use Mvc4us\Utils\ArrayUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;

class ExtendedSymfonySerializer extends Serializer
{
    public function parseJson(string $json, object|string $object, array $context = []): mixed
    {
        return $this->deserialize(
            $json,
            is_object($object) ? get_class($object) : $object,
            'json',
            array_merge_recursive(
                [AbstractNormalizer::OBJECT_TO_POPULATE => is_object($object) ? $object : null],
                $context
            )
        );
    }

    public function json(mixed $data, array $context = []): string
    {
        return $this->serialize(
            $data,
            'json',
            array_merge([JsonEncode::OPTIONS => JsonResponse::DEFAULT_ENCODING_OPTIONS], $context)
        );
    }

    public static function extendContext(array $context = []): array
    {
        $customContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_LIMIT => 1,
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return null;
            },
            AbstractObjectNormalizer::ENABLE_MAX_DEPTH => true,
            DateTimeNormalizer::TIMEZONE_KEY => 'UTC'
        ];
        return ArrayUtils::merge($customContext, $context);
    }
}
