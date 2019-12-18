<?php

declare(strict_types=1);

namespace Chebur\DoctrineTypePsr7;

use Chebur\DoctrinePostgreSQLTypeJson\JsonType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\UriInterface;
use Throwable;

class UriJsonType extends JsonType
{
    public const NAME = 'uri_json';

    protected const SCHEME = 'scheme';
    protected const USERINFO = 'userinfo';
    protected const HOST = 'host';
    protected const PORT = 'port';
    protected const PATH = 'path';
    protected const QUERY = 'query';
    protected const FRAGMENT = 'fragment';

    public function getName()
    {
        return static::NAME;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof UriInterface) {
            $array = [
                static::SCHEME => $value->getScheme(),
                static::USERINFO => $value->getUserInfo(),
                static::HOST => $value->getHost(),
                static::PORT => $value->getPort(),
                static::PATH => $value->getPath(),
                static::QUERY => $value->getQuery(),
                static::FRAGMENT => $value->getFragment(),
            ];
            return parent::convertToDatabaseValue($array, $platform);
        }

        throw ConversionException::conversionFailedInvalidType($value, $this->getName(), ['null', UriInterface::class]);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $valueArray = parent::convertToPHPValue($value, $platform);
        if ($valueArray === null) {
            return null;
        }

        $factory = new Psr17Factory();

        try {
            $uri = $factory->createUri('');
            $uri = $uri->withScheme($valueArray[static::SCHEME]);
            $uri = $uri->withUserInfo($valueArray[static::USERINFO]);
            $uri = $uri->withHost($valueArray[static::HOST]);
            $uri = $uri->withPort($valueArray[static::PORT]);
            $uri = $uri->withPath($valueArray[static::PATH]);
            $uri = $uri->withQuery($valueArray[static::QUERY]);
            $uri = $uri->withFragment($valueArray[static::FRAGMENT]);
        } catch (Throwable $e) {
            throw ConversionException::conversionFailedFormat($value, static::NAME, json_encode([
                static::SCHEME => '',
                static::USERINFO => '',
                static::HOST => '',
                static::PORT => '',
                static::PATH => '',
                static::QUERY => '',
                static::FRAGMENT => '',
            ]), $e);
        }

        return $uri;
    }
}
