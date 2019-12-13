<?php

declare(strict_types=1);

namespace Chebur\DoctrineTypePsr7;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\JsonType;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestInterface;
use Throwable;

class RequestType extends JsonType
{
    public const NAME = 'request';

    protected const METHOD = 'method';
    protected const URI = 'uri';
    protected const PROTOCOL_VERSION = 'protocol_version';
    protected const HEADERS = 'headers';
    protected const BODY = 'body';

    public function getName()
    {
        return static::NAME;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof RequestInterface) {
            $array = [
                static::METHOD => $value->getMethod(),
                static::URI => $value->getUri()->__toString(),
                static::PROTOCOL_VERSION => $value->getProtocolVersion(),
                static::HEADERS => $value->getHeaders(),
                static::BODY => $value->getBody()->__toString(),
            ];
            return parent::convertToDatabaseValue($array, $platform);
        }

        throw ConversionException::conversionFailedInvalidType($value, $this->getName(), ['null', RequestInterface::class]);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $valueArray = parent::convertToPHPValue($value, $platform);
        if ($valueArray === null) {
            return null;
        }

        $factory = new Psr17Factory();

        try {
            $request = $factory->createRequest($valueArray[static::METHOD], $factory->createUri($valueArray[static::URI]));
            foreach ($valueArray[static::HEADERS] as $name => $valueArray) {
                $request = $request->withHeader($name, $valueArray);
            }
            $request = $request->withProtocolVersion($valueArray[static::PROTOCOL_VERSION]);
            $request->withBody($factory->createStream($valueArray[static::BODY]));
        } catch (Throwable $e) {
            throw ConversionException::conversionFailedFormat($value, static::NAME, json_encode([
                static::METHOD => '',
                static::URI => '',
                static::PROTOCOL_VERSION => '',
                static::HEADERS => '',
                static::BODY => '',
            ]), $e);
        }

        return $request;
    }
}
