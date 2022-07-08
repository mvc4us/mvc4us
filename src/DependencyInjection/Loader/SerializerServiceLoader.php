<?php

declare(strict_types=1);

namespace Mvc4us\DependencyInjection\Loader;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class SerializerServiceLoader
{
    private function __construct()
    {
    }

    public static function load(ContainerBuilder $container): void
    {
        if (!class_exists('Symfony\\Component\\Serializer\\Serializer')) {
            return;
        }

        $defaultContext = [];
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $normalizers = [
            new DateTimeNormalizer(),
            new ArrayDenormalizer(),
            new ObjectNormalizer(
                classMetadataFactory: $classMetadataFactory,
                propertyTypeExtractor: new ReflectionExtractor(),
                defaultContext: $defaultContext
            ),
        ];
        $encoders = [new XmlEncoder(), new JsonEncoder()];

        $container->register(Serializer::class)
            ->setArgument('$normalizers', $normalizers)
            ->setArgument('$encoders', $encoders)
            ->setAutowired(true);
        $container->setAlias(SerializerInterface::class, Serializer::class);
        $container->setAlias('serializer', Serializer::class)->setPublic(true);
    }
}
