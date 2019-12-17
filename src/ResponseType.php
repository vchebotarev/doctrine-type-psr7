<?php

declare(strict_types=1);

namespace Chebur\DoctrineTypePsr7;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\JsonType;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ResponseType extends JsonType
{
    public const NAME = 'response';

    protected const STATUS_CODE = 'status_code';
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

        if ($value instanceof ResponseInterface) {
            $array = [
                static::STATUS_CODE => $value->getStatusCode(),
                static::PROTOCOL_VERSION => $value->getProtocolVersion(),
                static::HEADERS => $value->getHeaders(),
                static::BODY => $value->getBody()->__toString(),
            ];
            return parent::convertToDatabaseValue($array, $platform);
        }

        throw ConversionException::conversionFailedInvalidType($value, $this->getName(), ['null', ResponseInterface::class]);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $valueArray = parent::convertToPHPValue($value, $platform);
        if ($valueArray === null) {
            return null;
        }

        $factory = new Psr17Factory();

        try {
            $response = $factory->createResponse($valueArray[static::STATUS_CODE]);
            $response = $response->withProtocolVersion($valueArray[static::PROTOCOL_VERSION]);
            foreach ($valueArray[static::HEADERS] as $name => $header) {
                $response = $response->withHeader($name, $header);
            }
            $response = $response->withBody($factory->createStream($valueArray[static::BODY]));
        } catch (Throwable $e) {
            throw ConversionException::conversionFailedFormat($value, static::NAME, json_encode([
                static::STATUS_CODE => '',
                static::PROTOCOL_VERSION => '',
                static::HEADERS => '',
                static::BODY => '',
            ]), $e);
        }

        return $response;
    }
}
