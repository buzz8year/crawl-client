<?php

namespace console\controllers;

use crawler\parser\ParserFactory;
use crawler\parser\ParserSettler;
use crawler\parser\ParserProvisioner;
use crawler\models\category\CategorySource;
use crawler\models\source\Source;
use crawler\models\oc\OcSettler;
use yii\caching\DbCache;
use Yii;

class ParseController extends \yii\console\Controller
{
    public $src;
    public $sale;

    /**
     * Class instance properties
     * @return array
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);
        if ($actionID == 'index')
            $options[] = 'sale';

        $options[] = 'src';
        return $options;
    }

    /**
     * TODO: Remove
     * @return void
     */
    public function actionInfo()
    {
        print_r(phpinfo());
    }

    /**
     * Initiates Parsing (global or by source ID)
     * @return void
     */
    public function actionIndex()
    {
        if (empty($this->src)) 
        {
            $provisioner = new ParserProvisioner();
            foreach ($provisioner->listActiveSources() as $sourceId => $source)
                $this->parseSource($sourceId);
            return;
        }

        $source = Source::findOne($this->src);
        if ($source && $source->status)
            $this->parseSource($this->src);

        else {
            $log = sprintf('Source %s status is OFF', $source->title);
            $this->stdoutOut($log);
        }
    }

    /**
     * Lists Sources IDs
     * @return void
     */
    public function actionList()
    {
        foreach (Source::findAllActive() as $source)
            $this->stdoutLog($source->id);
    }

    /**
     * Lists Sources IDs and their Names
     * @return void
     */
    public function actionListName()
    {
        foreach (Source::findAllActive() as $source)
            $this->stdoutLog(sprintf('%d - %s', $source->id, $source->title));
    }

    /**
     * Sync Products to OC
     * @return void
     */
    public function actionSync()
    {
        if ($this->src && !Source::findOne($this->src)->status)
            return;

        if ($syncData = OcSettler::saveProducts($this->src)) 
        {
            $log = sprintf('Processed: %s, Synced/Updated: %s/%s', $syncData['processed'], $syncData['synced'], $syncData['updated']);
            $this->stdoutLog($log);
        }
    }

    /**
     * Deletes all Misfit Products (those, not in Yii) from OC
     * @return void
     */
    public function actionDeleteMisfits()
    {
        if ($data = OcSettler::deleteMisfits()) 
        {
            $log = sprintf('DELETING MISFITS FROM OC / Total processed: %s / Misfits deleted: %s', $data['total'], $data['misfits']);
            $this->stdoutLog($log);
        }
    }


    /**
     * @return void
     */
    public function actionCacheTree()
    {
        $this->stdoutLog('CACHE: Category Tree');
        if (empty($this->src)) 
        {
            $this->stdoutLog('No Source ID specified');
            return;
        }

        $rawCategories = CategorySource::findAllAsArrayById($this->src);
        $sourceCategories = ParserProvisioner::buildTree($rawCategories);

        $cache = new DbCache();
        $cache->set(sprintf('categoryTreeId=%d', $this->src), $sourceCategories);

        $this->stdoutLog('CACHED');
    }

    /**
     * @return void
     */
    public function actionTree()
    {
        if (empty($this->src)) 
        {
            $this->stdoutLog('Source ID must be defined as follows: php {path}/yii parse/tree --src={int | Source ID}');      
            return;
        }

        $source = Source::findOne($this->src);
        if (empty($source) || $source->status === 0) 
        {
            $this->stdoutLog('Source status is OFF');
            return;
        }

        $factory = new ParserFactory();        
        $factory->setParser($this->src);
        $parser = $factory->parser;

        $this->stdoutLog('Source category tree parsing is started: ');
        $categories = $parser->parseCategories();
        
        $log = sprintf('Source category tree is parsed. Parsed categories: %d. Writing to DB is started: ', count($categories));
        $this->stdoutLog($log);

        if (empty($categories)) 
            return;

        $settler = new ParserSettler($this->src);
        if ($settler->saveCategories(json_decode(json_encode($categories)))) 
        {
            Yii::info('Category tree has been parsed and saved. Check it here: 
                http://77.222.63.105/crawler/web/index.php?r=parser%2Ftree&id=' . $this->src,
                'parse-console');

            $this->stdout('Category tree has been parsed and saved. Check it here: 
                http://77.222.63.105/crawler/web/index.php?r=parser%2Ftree&id=' . $this->src);
        }
    }

    /**
     * @return void
     */
    public function actionUpdateDetails()
    {
        if ($this->src) 
            $this->updateDetails($this->src);

        else {
            foreach (Source::findAllActive() as $source)
                $this->updateDetails($source->id);
        }
    }

    /**
     * @return void
     */
    public function updateDetails(int $sourceId)
    {
        $factory = new ParserFactory();        
        $factory->setParser($sourceId);

        $model = $factory->model;
        $parser = $factory->parser;

        $source = Source::findOne($model->id);
        $log = sprintf('UPDATING DETAILS of all empty (%d) %s Products', count($source->emptyProducts), $model->title);
        
        $this->stdoutLog($log);
        $parser->parseDetails();

        $this->stdoutLog(sprintf('DONE: %s', $log));
    }

    /**
     * Parses source by ID
     * @return void
     */
    public function parseSource(int $sourceId)
    {
        $factory = new ParserFactory();        
        $factory->setParser($sourceId);
        $model = $factory->model;

        $rep = str_repeat('|', mb_strlen($model->title));
        $log = sprintf('%s %s', $rep, strtoupper($model->title));
        $this->stdoutLog($log);

        $provisioner = new ParserProvisioner();
        $categories = $provisioner->listActiveCategories($sourceId);
        $keywords = $provisioner->listSourceKeywords($sourceId);

        // LOG: console/runtime/logs/parse.log
        Yii::info(sprintf('Keywords to parse: %d', count($keywords)), 'parse-console');
        Yii::info(sprintf('Categories to parse: %d', count($categories)), 'parse-console');
        $this->stdout(sprintf('Categories Found: %d', count($categories)));

        // SALE: Sale flag
        if ($this->sale) 
            $this->parseSourceSales($model, $categories, $keywords);
    }

    /**
     * Parses source sales
     * @return void
     */
    public function parseSourceSales($factory, $categories, $keywords)
    {
        $model = $factory->model;
        $parser = $factory->parser;

        $model->saleFlag = true;
        $model->url = '';

        if (method_exists($model->class, 'xpathSale') && $categories) 
        {
            $this->stdout('ITERATE: Categories');
            foreach ($categories as $key => $category) 
            {
                $model->categorySourceId = $category['csid'];
                $model->categoryId = $category['id'];
                $model->url = $parser->urlBuild('', $category['csid'], '');
            }
        } 
        elseif ($keywords)
        {
            $this->stdout('ITERATE: Keywords');
            foreach ($keywords as $keywordId => $keyword) 
            {
                $model->keywordId = $keywordId;
                $model->url = $parser->urlBuild('', '', $keyword);
            }
        }

        if (empty($model->url)) 
        {
            $this->stdout('URL not built.');
            return;
        }

        $productsReturn = $parser->parseCatalog($model->url);
        if ($productsReturn) 
        {
            $log = sprintf('%s %s Products', $model->url, $productsReturn);
            $this->stdoutLog($log);
        }

        $detailsReturn = $parser->parseDetails();
        if ($detailsReturn) 
        {
            $log = sprintf('%s Detailed', $detailsReturn);
            $this->stdoutLog($log);
        } 
        else $this->stdout('.');
    }

    /**
     * Logging and standard output
     * @return void
     */
    public function stdoutLog(string $s)
    {
        Yii::info($s, 'parse-console');
        $this->stdout($s);
    }
}
