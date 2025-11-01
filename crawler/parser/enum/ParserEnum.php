<?php

namespace crawler\parser\enum;

use Yii;

class ParserEnum
{
    public static function getHint(): string
    {
        return Yii::t('app', 'A helpful message here.');
    }
}