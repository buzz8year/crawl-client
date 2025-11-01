<?php

namespace crawler\parser\enum;

class SessionType
{
    public const string PHANTOM = 'phantom';
    public const string CURL = 'curl';
    public const string FILE = 'file';
    public const string ZIP = 'zip';

    public const array METHODS = [
        self::PHANTOM => 'phantomSession',
        self::CURL => 'curlSession',
        self::FILE => 'fileSession',
        self::ZIP => 'zipSession',
    ];

    public static function getFailMessage(): string
    {
        return PHP_EOL . 'Client session response is bad.';
    }
}