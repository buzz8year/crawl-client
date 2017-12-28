<?php

namespace backend\models;

use Yii;
use yii\helpers\ArrayHelper;


class Source extends \yii\db\ActiveRecord
{

    const SYNONYMIZE = 1;
    const NOT_SYNONYMIZE = 0;
    const PROXY = 1;
    const NOT_PROXY = 0;
    const CAPTCHA = 1;
    const NOT_CAPTCHA = 0;

    const ON_SELECTOR = 'text-primary';
    const OFF_SELECTOR = 'text-danger';

    public $keywords = [];


    public static function tableName()
    {
        return 'source';
    }


    public function rules()
    {
        return [
            // [['title', 'source_url', 'description', 'class_namespace'], 'required'],
            [['title', 'source_url', 'description'], 'required'],
            [['description'], 'string'],
            [['need_synonymizer', 'need_proxy', 'need_captcha', 'search_applicable', 'limit_page', 'limit_detail', 'status'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['source_url'], 'string', 'max' => 64],
            [['class_namespace'], 'string', 'max' => 128],
            [['search_action', 'search_category', 'search_keyword'], 'string', 'max' => 32],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'source_url' => 'Source Url',
            'description' => 'Description',
            'class_namespace' => 'Class Namespace',
            'need_synonymizer' => 'Need Synonymizer',
            'need_proxy' => 'Need Proxy',
            'need_captcha' => 'Need Captcha',
            'search_applicable' => 'Apply below URL Queries to Parse ?',
            'search_action' => 'Search Action URL Query',
            'search_category' => 'Search Category URL Query',
            'search_keyword' => 'Search Keyword URL Query',
            'limit_detail' => 'Limit of catalog/search Pages to parse',
            'limit_page' => 'Limit of products to parse for Details',
        ];
    }


    public function synonymizeHighlight()
    {
        return self::highlightSelector()[$this->need_synonymizer];
    }

    public function proxyHighlight()
    {
        return self::highlightSelector()[$this->need_proxy];
    }

    public function captchaHighlight()
    {
        return self::highlightSelector()[$this->need_captcha];
    }


    public static function highlightSelector()
    {
        return [
            self::OFF_SELECTOR,
            self::ON_SELECTOR,
        ];
    }


    public function synonymizeStatus()
    {
        return self::synonymizeStatusText()[$this->need_synonymizer];
    }

    public static function synonymizeStatusText()
    {
        return [
            self::SYNONYMIZE     => 'Вкл',
            self::NOT_SYNONYMIZE => 'Выкл',
        ];
    }

    public function proxyStatus()
    {
        return self::proxyStatusText()[$this->need_proxy];
    }

    public static function proxyStatusText()
    {
        return [
            self::PROXY     => 'Вкл',
            self::NOT_PROXY => 'Выкл',
        ];
    }

    public function captchaStatus()
    {
        return self::captchaStatusText()[$this->need_captcha];
    }

    public static function captchaStatusText()
    {
        return [
            self::CAPTCHA     => 'Вкл',
            self::NOT_CAPTCHA => 'Выкл',
        ];
    }

    static function listSources() 
    {
        return ArrayHelper::map( self::find()->all(), 'id', 'title' );
    }


    public function getCategorySources()
    {
        return $this->hasMany(CategorySource::className(), ['source_id' => 'id']);
    }


    public function getHeaderSources()
    {
        return $this->hasMany(HeaderSource::className(), ['source_id' => 'id']);
    }


    public function getHeaderValues()
    {
        $arrayHeaders = [];
        
        foreach ($this->headerSources as $header) {
            $title = Header::findOne($header['header_id'])->title;
            $value = HeaderValue::findOne($header['header_value_id'])->value;

            if ( $header->status )
                $arrayHeaders[strtolower($title)][] = [
                    $title . ': ' . $value,
                ];
        }

        return $arrayHeaders;
    }





    public function getProxies()
    {
        $arrayProxies = [];
        
        foreach ($this->proxySources as $proxySource) {
            $proxy = Proxy::findOne($proxySource['proxy_id']);

            $address = $proxy->ip . ($proxy->port ? (':' . $proxy->port) : '');
            $password = $proxy->login ? ($proxy->login . ':' . $proxy->password) : '';

            if ($proxySource->status) {
                $arrayProxies['ipv' . $proxy->version][] = [
                    'address'   => $address,
                    'password'  => $password,
                ];
            }
        }

        foreach (Proxy::find()->all() as $proxy) {
            $address = $proxy->ip . ($proxy->port ? (':' . $proxy->port) : '');
            $password = $proxy->login ? ($proxy->login . ':' . $proxy->password) : '';

            if (!in_array($address, $arrayProxies['ipv' . $proxy->version])) {
                $arrayProxies['ipv' . $proxy->version][] = [
                    'address'   => $address,
                    'password'  => $password,
                ];
            }
        }

        return $arrayProxies;
    }



    public function getProxySources()
    {
        return $this->hasMany(ProxySource::className(), ['source_id' => 'id'])->orderBy(['queue' => SORT_ASC]);
    }



    public function getAllProxies()
    {
        $arrayProxies = [];
        
        foreach (Proxy::find()->all() as $proxy) {
            $address = $proxy->ip . ( $proxy->port ? ':' . $proxy->port : '' );
            $password = $proxy->login ? $proxy->login . ':' . $proxy->password : '';

            $arrayProxies['ipv' . $proxy->version][] = [
                'address'   => $address,
                'password'  => $password,
            ];
        }

        return $arrayProxies;
    }




    public function getAllHeaderValues()
    {
        $arrayHeaders = [];
        
        foreach (Header::find()->all() as $header) {
            $value = HeaderValue::findOne($header['header_value_id'])->value;

            if ( $header->status )
                $arrayHeaders[strtolower($title)][] = [
                    $title . ': ' . $value,
                ];
        }

        return $arrayHeaders;
    }

    


    public function getProducts()
    {
        return $this->hasMany(Product::className(), ['source_id' => 'id']);
    }


    public function getAsyncProducts()
    {
        return Product::find()->where(['source_id' => $this->id, 'sync_status' => 0])->all();
    }


    public function getProductUrls()
    {
        return ArrayHelper::map( Product::find()->where(['source_id' => $this->id])->all(), 'id', 'source_url' );
        // $products = Yii::$app->db->createCommand('
        //     SELECT id, source_url
        //     FROM product
        // ')->queryAll();
        
        // return ArrayHelper::map($products, 'id', 'source_url');
    }

    public function getEmptyProducts()
    {
        $products = Product::find()->where(['source_id' => $this->id])->all();
        $productUrls = [];
        foreach ($products as $product) {
            if (!$product->productImages && !$product->descriptions && !$product->productAttributes) {
                $productUrls[$product->id] = $product->source_url;
            }
        }
        return $productUrls;
    }

    public function getKeywordSources()
    {
        return $this->hasMany(KeywordSource::className(), ['source_id' => 'id']);
    }

    public function getWords()
    {   
        $keywords = [];
        foreach ($this->keywordSources as $keywordSource) {
            $keywords[] = Keyword::find()->where(['id' => $keywordSource->keyword_id])->one();
        }
        return $keywords;
    }

    static function findKeyword(string $keyword)
    {   
        return Keyword::find()->where(['word' => $keyword])->one();
    }


    public function listRegions()
    {
        return ArrayHelper::map( Region::find()->all(), 'id', 'alias' );
    }


    public function getRegionSources()
    {
        return $this->hasMany(RegionSource::className(), ['source_id' => 'id']);
    }

    public function getRegions()
    {   
        $regions = [];
        foreach ($this->regionSources as $regionSource) {
            $regions[] = Region::find()
                ->select('*')
                ->join('join', 'region_source rs', 'rs.region_id = region.id')
                ->where(['region.id' => $regionSource->region_id, 'rs.source_id' => $regionSource->source_id])
                ->asArray()
                ->one();
        }
        return $regions;
    }

    static function findRegion(string $region)
    {   
        return Region::find()->where(['alias' => $region])->one();
    }

    static function findRegionSource(string $region)
    {   
        return RegionSource::find()->where(['alias' => $region])->one();
    }

}
