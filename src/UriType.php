<?php

declare(strict_types=1);

namespace Chebur\DoctrineTypePsr7;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\TextType;
use InvalidArgumentException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\UriInterface;

class UriType extends TextType
{
    public const NAME = 'uri';

    public function getName()
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getClobTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof UriInterface) {
            return $value->__toString();
        }

        throw ConversionException::conversionFailedInvalidType($value, $this->getName(), ['null', UriInterface::class]);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof UriInterface) {
            return $value;
        }

        $factory = new Psr17Factory();

        try {
            $uri = $factory->createUri($value);
        } catch (InvalidArgumentException $e) {
            throw ConversionException::conversionFailedFormat($value, static::NAME, 'RFC3986', $e);
        }

        return $uri;
    }
}
