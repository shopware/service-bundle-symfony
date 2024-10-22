<?php

namespace Shopware\ServiceBundle\Exception;

class LicenseException extends \Exception
{
    public const ERROR_LICENSE_INVALID = 1001;
    public const ERROR_LICENSE_EXPIRED = 1002;

    public function __construct(string $message, int $code)
    {
        parent::__construct($message, $code);
    }

    public static function licenseExpired(string $licenseKey): self
    {
        return new self(sprintf('License key not valid: %s', $licenseKey), self::ERROR_LICENSE_EXPIRED);
    }

    public static function licenseInvalid(string $licenseKey): self
    {
        return new self(sprintf('License key not valid: %s', $licenseKey), self::ERROR_LICENSE_INVALID);
    }

    public static function licenseNotProvided(): self
    {
        return new self('License key not provided', self::ERROR_LICENSE_INVALID);
    }

    public static function domainInvalid(string $domain): self
    {
        return new self(sprintf('License domain not valid: %s', $domain), self::ERROR_LICENSE_INVALID);
    }

    public static function domainNotProvided(): self
    {
        return new self('License domain not provided', self::ERROR_LICENSE_INVALID);
    }
}
