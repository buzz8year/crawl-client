<?php

namespace crawler\parser\enum;

class DetailsType
{
    public const string DESCRIPTION = 'description';
    public const string ATTRIBUTE = 'attribute';
    public const string IMAGE = 'image';

    public const array SETTLE_METHODS = [
        self::DESCRIPTION => 'saveDescriptions',
        self::ATTRIBUTE => 'saveAttributes',
        self::IMAGE => 'saveImages',
    ];
}