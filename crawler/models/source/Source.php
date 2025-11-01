<?php

namespace crawler\models\source;

use crawler\models\category\CategorySource;
use crawler\models\header\Header;
use crawler\models\header\HeaderSource;
use crawler\models\header\HeaderValue;
use crawler\models\keyword\Keyword;
use crawler\models\keyword\KeywordSource;
use crawler\models\product\Product;
use crawler\models\proxy\Proxy;
use crawler\models\proxy\ProxySource;
use crawler\models\region\Region;
use crawler\models\region\RegionSource;
use yii\helpers\ArrayHelper;
use Yii;

/**
 * This is the model class for table "source".
 *
 * @property int $id
 * @property string $source_url
 */
class Source extends \yii\db\ActiveRecord
{
    public const int SYNONYMIZE = 1;
    public const int NOT_SYNONYMIZE = 0;
    public const int PROXY = 1;
    public const int NOT_PROXY = 0;
    public const int CAPTCHA = 1;
    public const int NOT_CAPTCHA = 0;

    public const string ON_SELECTOR = 'text-primary';
    public const string OFF_SELECTOR = 'text-danger';

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

    public function captchaStatus()
    {
        return self::captchaStatusText()[$this->need_captcha];
    }

    public function synonymizeStatus()
    {
        return self::synonymizeStatusText()[$this->need_synonymizer];
    }

    public function proxyStatus()
    {
        return self::proxyStatusText()[$this->need_proxy];
    }

    public function getCategorySources()
    {
        return $this->hasMany(CategorySource::class, ['source_id' => 'id']);
    }

    public function getCountCategorySources()
    {
        return CategorySource::find()->where(['source_id' => $this->id])->count();
    }

    public function getHeaderSources()
    {
        return $this->hasMany(HeaderSource::class, ['source_id' => 'id'])->where(['status' => 1]);
    }

    public function getHeaderValues()
    {
        $arrayHeaders = [];
        foreach ($this->headerSources as $header) 
        {
            $title = Header::findOne($header['header_id'])->title;
            $value = HeaderValue::findOne($header['header_value_id'])->value;

            $arrayHeaders[strtolower($title)][] = [$title . ': ' . $value];
        }
        return $arrayHeaders;
    }

    public function getProxies()
    {
        $arrayProxies = [];
        
        foreach ($this->proxySources as $proxySource) 
        {
            $proxy = Proxy::findOne($proxySource['proxy_id']);
            
            if ($proxySource->status) 
                $arrayProxies['ipv' . $proxy->version][] = [
                    'password' => $proxy->getLoginPassword(),
                    'address' => $proxy->getIpPort(),
                ];
        }

        foreach (Proxy::find()->all() as $proxy) 
        {
            $address = $proxy->getIpPort();
            $password = $proxy->getLoginPassword();

            if (!in_array($address, $arrayProxies['ipv' . $proxy->version])) 
                $arrayProxies['ipv' . $proxy->version][] = [
                    'password' => $password,
                    'address' => $address,
                ];
        }

        return $arrayProxies;
    }

    public function getProxySources()
    {
        return $this->hasMany(ProxySource::class, ['source_id' => 'id'])->orderBy(['queue' => SORT_ASC]);
    }

    public function getAllProxies()
    {
        $arrayProxies = [];
        
        foreach (Proxy::find()->all() as $proxy) 
            $arrayProxies['ipv' . $proxy->version][] = [
                'password' => $proxy->getLoginPassword(),
                'address' => $proxy->getIpPort(),
            ];

        return $arrayProxies;
    }

    public function getAllHeaderValues(string $title)
    {
        $arrayHeaders = [];
        foreach (Header::findAllActive() as $header) 
        {
            $value = HeaderValue::findOne($header['header_value_id'])->value ?? '';
            $arrayHeaders[strtolower($title)][] = [$title . ': ' . $value];
        }
        return $arrayHeaders;
    }

    public function getProducts()
    {
        return $this->hasMany(Product::class, ['source_id' => 'id']);
    }

    public function getCountProducts()
    {
        return Product::find()->where(['source_id' => $this->id])->count();
    }

    public function getAsyncProducts()
    {
        return Product::find()->where(['source_id' => $this->id, 'sync_status' => 0])->asArray()->all();
    }

    public function getCountAsyncProducts()
    {
        return Product::find()->where(['source_id' => $this->id, 'sync_status' => 0])->count();
    }

    public function getProductUrls()
    {
        return ArrayHelper::map( Product::find()->where(['source_id' => $this->id])->all(), 'id', 'source_url' );
    }

    public function getEmptyProducts()
    {
        $products = Product::find()->where(['source_id' => $this->id])->all();
        $productUrls = [];

        foreach ($products as $product)
            if (!$product->productImages && !$product->descriptions && !$product->productAttributes)
                $productUrls[$product->id] = $product->source_url;

        return $productUrls;
    }

    public function getKeywordSources()
    {
        return $this->hasMany(KeywordSource::class, ['source_id' => 'id']);
    }

    public function getWords()
    {   
        $keywords = [];
        foreach ($this->keywordSources as $keywordSource)
            $keywords[] = Keyword::find()->where(['id' => $keywordSource->keyword_id])->one();

        return $keywords;
    }

    public function getRegionSources()
    {
        return $this->hasMany(RegionSource::class, ['source_id' => 'id']);
    }

    public function getRegions()
    {   
        $regions = [];
        foreach ($this->regionSources as $regionSource)
            $regions[] = Region::findByRegionSource($regionSource);

        return $regions;
    }

    public function getUrl(string $url)
    {   
        $domain = $this->source_url;

        if (strpos($domain, 'www') === false) 
        {
            $protocol = explode('//', $domain)[0] . '//';
            $domain = explode('//', $domain)[1];
        } 
        else {
            $protocol = explode('www.', $domain)[0] . 'www.';
            $domain = explode('www.', $domain)[1];
        }

        if (strpos($url, $domain) === false)
            $url = $protocol . trim($domain, '/') . $url;

        return $url;
    }

    public static function findRegion(string $region)
    {   
        return Region::find()->where(['alias' => $region])->one();
    }

    public static function findRegionSource(string $region)
    {   
        return RegionSource::find()->where(['alias' => $region])->one();
    }

    public static function findAllActive()
    {   
        return self::find()->where(['status' => 1])->all();
    }

    public static function findKeyword(string $keyword)
    {   
        return Keyword::find()->where(['word' => $keyword])->one();
    }
    
    public static function listSources() 
    {
        return ArrayHelper::map( self::find()->all(), 'id', 'title' );
    }

    public static function highlightSelector()
    {
        return [
            self::OFF_SELECTOR,
            self::ON_SELECTOR,
        ];
    }

    public static function synonymizeStatusText()
    {
        return [
            self::SYNONYMIZE => 'On',
            self::NOT_SYNONYMIZE => 'Off',
        ];
    }

    public static function proxyStatusText()
    {
        return [
            self::PROXY => 'On',
            self::NOT_PROXY => 'Off',
        ];
    }

    public static function captchaStatusText()
    {
        return [
            self::CAPTCHA => 'On',
            self::NOT_CAPTCHA => 'Off',
        ];
    }

}
