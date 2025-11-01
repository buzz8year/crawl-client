<?php

namespace crawler\models\proxy;

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
            'password' => 'Password',
            'login' => 'Login',
        ];
    }


    public function getProxySources()
    {
        return $this->hasMany(ProxySource::class, ['proxy_id' => 'id']);
    }

    public function getIpPort(): string
    {
        return $this->port 
            ? sprintf('%s:%s' ,$this->ip, $this->port) 
            : $this->ip;
    }

    public function getLoginPassword(): string
    {
        return $this->login && $this->password 
            ? sprintf('%s:%s', $this->login, $this->password) 
            : '';
    }

    public static function listProxies(): array
    {
        $proxies = [];
        foreach (self::find()->all() as $proxy) 
        {
            $port = $proxy->port ?? 'xxxx';
            $login = $proxy->login ?? 'xxxx';
            $password = $proxy->password ? '••••' : 'xxxx';
            $warning = !$proxy->login || !$proxy->password || !$proxy->port 
                ? '(most probably will fail)' 
                : '';

            $proxies[$proxy->id] = sprintf('%s:%s@%s:%s %s', $login, $password, $proxy->ip, $port, $warning);
        }
        return $proxies;
    }

    public static function getIdByAddress(string $address)
    {
        $exp = explode(':', $address);
        return Proxy::find()
            ->where(['ip' => $exp[0], 'port' => $exp[1] ?? null])
            ->one()->id 
            ?? null;
    }
}
