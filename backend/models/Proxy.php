<?php

namespace backend\models;

use Yii;
use yii\helpers\ArrayHelper;


class Proxy extends \yii\db\ActiveRecord
{

    public static function tableName()
    {
        return 'proxy';
    }


    public function rules()
    {
        return [
            [['ip'], 'required'],
            [['port', 'version'], 'integer'],
            [['ip', 'login', 'password'], 'string', 'max' => 45],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ip' => 'Ip',
            'port' => 'Port',
            'version' => 'IP Version',
            'login' => 'Login',
            'password' => 'Password',
        ];
    }


    public function getProxySources()
    {
        return $this->hasMany(ProxySource::className(), ['proxy_id' => 'id']);
    }


    static function listProxies()
    {
        $proxies = [];
        foreach (self::find()->all() as $proxy) {
            $caution = !$proxy->login || !$proxy->password || !$proxy->port ? '(most probably will fail)' : '';
            $proxies[$proxy->id] = ($proxy->login ?? 'xxxx') . ':' . ($proxy->password ? '••••' : 'xxxx') . '@' . $proxy->ip . ':' . ($proxy->port ?? 'xxxx ') . $caution;
        }
        return $proxies;
    }
}
