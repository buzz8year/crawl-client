<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\assets\ParserTrialAsset;
use crawler\util\CategoryNestedPrinter;
use crawler\models\parser\ParserProvisioner;
use crawler\models\category\CategorySource;
use crawler\models\sync\OcSettler;
use crawler\models\keyword\Keyword;
use crawler\models\source\Source;
use yii\widgets\Breadcrumbs;

/** @var yii\web\View $this */

ParserTrialAsset::register($this);
                
$this->title = "Parsing {$model->title}";
$this->params['newcrumbs'][] = $model->title;

$word = Keyword::findOne($model->keywordId);
$count = Source::findOne($model->id)->countProducts;
$async = Source::findOne($model->id)->countAsyncProducts;

echo Breadcrumbs::widget([
    'homeLink' => ['label' => 'Parsing', 'url' => 'index.php'],
    'links' => $this->params['newcrumbs'] ?? [],
]);

?>


<div class="row">

    <div class="col-lg-6">
        <h1><?= $model->title ?></h1>
        <small><?= Html::a($model->domain, $model->domain, ['target' => '_blank']) ?></small>
    </div>

    <div class="col-lg-4 pull-right">
        <h4>&nbsp;</h4>
        <span><?php echo Html::a('Edit', ['source/update', 'id' => $model->id]); ?></span>
        <span class="pull-right"><?php echo Html::a('Parse Categories', ['parser/tree', 'id' => $model->id]); ?></span>
    </div>

</div>

<div class="keyword-form"><br/><br/>

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">

        <div class="col-md-8">

            <?= $form->field($model, 'url')->textInput(['id' => 'parseInput', 'readonly' => true, 'placeholder' => 'Select a category →'])->label('URL') ?>
            
            <?php if (isset($model->url)) : ?>
                <small class="pull-right link-source">
                    <?= Html::a($model->url, $model->url, ['target' => '_blank']) ?>
                </small>
            <?php endif; ?>
            
            <div class="text-muted pull-left">Category and search within it:</div><br/>

            <div class="text-muted">
                <small>If when selecting the next value, the link field is reset, 
                    <br/>it means that search on this resource is not possible in such a combination.
                </small><br/>
            </div>
            
            <br/><br/><br/><br/>

            <div class="form-group pull-left text-muted">

                <?= Html::submitButton('Parse Goods', ['class' => 'btn btn-success', 'name' => 'parseGoods', 'value' => 1]) ?><br/><br/>
                
                <small><del>Limit on navigating product pages: 2</del></small><br/>
                <small><del>Number of catalog pages with products: 10</del></small><br/><br/>
                
                <label style="font-weight:normal">

                    <?php echo Html::input('checkbox', 'parseSales', 1, [
                        'onchange' => sprintf("location='index.php?r=parser/trial&id=%d&reg=%d&cat=%d&word=%s&sale=%d'",
                            $model->id,
                            $model->regionId,
                            $model->categorySourceId,
                            $word ? $word->word : '',
                            $model->saleFlag ? 0 : 1
                        ),
                        'style' => 'position:relative;top:1px', 
                        'checked' => $model->saleFlag,
                    ]); ?>

                    <span>Parse only promotional products</span>

                </label>

                <br/><br/>
                
            </div>

            <div class="form-group pull-right text-right text-muted">

                <?php echo Html::submitButton(sprintf('Update Details (%d) Products', $count), [
                    'title' => 'Update details of all available products on the resource',
                    'disabled' => !(bool)$count,
                    'style' => 'display: block',
                    'class' => 'btn btn-white', 
                    'name' => 'updateDetails', 
                    'value' => 1, 
                ]); ?>

                <br/>

                <?php echo Html::submitButton('Export to Store', [
                    'title' => boolval($model->products) ? 'You have parsing results awaiting a command for detailing' : '',
                    'disabled' => boolval($model->products),
                    'class' => 'btn btn-primary', 
                    'name' => 'syncGoods', 
                    'value' => 1, 
                ]); ?>
                
                <br/><br/>

                <?php if ($syncData) : ?>
                    <small>Processed: <?= $syncData['processed'] ?>, Synchronized: <?= $syncData['synced'] ?></small><br/>
                <?php else: ?>
                    <small>Products: <?= $count ?></small><br/>
                    <small>Unsynced: <?= $async ?></small><br/>
                <?php endif; ?>

            </div>

            <div class="row">

                <?php if (isset($model->products)) : ?>

                    <br/><br/>

                    <div class="col-xs-12">

                        <h3>New positions found: <?= count($model->details) ?></h3>
                        <small>Total found: <?= count($model->products) ?></small>

                        <?php if (count($model->products)) : ?>

                            <div>
                                <?= Html::a('<i class="glyphicon glyphicon-share"></i> View Products', ['product/index'], ['target' => '_blank']) ?>
                            </div>
                            
                            <br/><br/>

                            <div class="form-group">

                                <?php echo Html::submitButton('Parse Details', [
                                    'value' => json_encode($model->details),
                                    'class' => 'btn btn-primary', 
                                    'name' => 'parseDetails', 
                                ]); ?>

                            </div>

                        <?php endif; ?>
                        
                    </div>

                <?php endif; ?>
                
                <?php if ($detailsParsed) : ?>

                    <br/><br/>

                    <div class="col-xs-10 text-muted">

                        <h4>Positions detailed: <?= $detailsParsed ?></h4>
                        <div><?= Html::a('<i class="glyphicon glyphicon-share"></i> View Products', ['product/index'], ['target' => '_blank']) ?></div><br/><br/>
                        <small>And saved to the database. Descriptions, attributes, images.</small><br/><br/>
                        <small>Detailing is done separately for several reasons: iteration together with the main parsing does not provide any advantages - requests to product pages are made separately in any case; price change tracking is possible in all cases during the main parsing; there is no goal to monitor changes in descriptions, attributes, images, as it makes little sense; separate parsing increases responsiveness/interactivity within the user interface; distributes server load.</small><br/><br/>
                    
                    </div>

                <?php endif; ?>

            </div><br/><br/>

        </div>

        <div class="col-md-4">

            <label>
                <span><?= $sourceRegions ? 'Region, Category' : 'Category' ?> and Keyword:</span>

                <div>
                    <?php echo Html::submitButton('Refresh Cache', [
                        'style' => '',
                        'class' => 'label label-categories', 
                        'name' => 'flushTree', 
                        'value' => 1,
                    ]); ?>
                </div>

            </label>

            <?php if ($sourceRegions)
                echo Html::dropDownList('', '', $sourceRegions, [
                    'onchange' => sprintf('location = \'index.php?r=parser/trial&reg=\' + $(this).val() + \'&id=%s&word=%s&cat=%s', 
                        $model->id, $model->regionId, $model->categorySourceId),
                    'prompt' => '-- Region --',
                    'class' => 'form-control',
                    'id' => 'regionList',
                    'options' => [
                        $model->regionId => [
                            'selected' => true
                        ],
                    ],
                ]);
            ?>
            
            <br/>

            <div id="categoryTree">

                <input type="hidden" id="categoryList" value="<?= $model->categoryId ?>" />

                <div class="col-xs-12" style="margin-bottom:10px">
                    <div class="row expanded">
                        <div class="category-tree-row">
                            <span class="category-tree-select <?= $model->categorySourceId ? '' : 'selected' ?>" 
                                onclick="categoryOnSelect('<?= $model->id ?>', '<?= $model->regionId ?>', '');"
                                style="padding-left: 40px" >
                                    <span class="tree-zero">&#9900;</span>
                                    <span>Without Category</span>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if ($model->categorySourceId > 0)
                    echo CategoryNestedPrinter::printNestedSelect($sourceCategories, $model->id, $model->regionId, $model->categorySourceId); ?>

            </div>
            
            <br/>

            <?php echo Html::dropDownList('', '', $sourceKeywords, [
                'onchange' => sprintf('var keyword = $(this).val() ? $(this).find(\':selected\').text() : \'\'; location = \'index.php?r=parser/trial&word=\' + keyword + \'&id=&reg=&cat=', 
                    $model->id, $model->regionId, $model->categorySourceId),
                'prompt' => '-- Keyword --',
                'class' => 'form-control',
                'id' => 'keywordList',
                'disabled' => false,
                'options'   => [
                    $model->keywordId => [
                        'selected' => true
                    ],
                ],
            ]); ?>
            
            <br/>

            <?php echo Html::input('text', 'flyKeyword', '', [
                'placeholder' => '...or enter a new one and press Enter',
                'class' => 'form-control input', 
            ]); ?>

        </div>

    </div>
    
    <br/><br/>

    <?php ActiveForm::end(); ?>

</div>

<br/><br/>

<div class="body-content">

    <div class="row">
        <div class="col-lg-12">

            <?php if ($model->warnings)
                    foreach ($model->warnings as $warning)
                        echo "<span class=\"label label-danger\">Message from the requested page</span>{$warning}"; ?>

            <table class="table table-striped">

                <?php foreach ($model->products ?? [] as $key => $product) : ?>

                    <?php if ($product['href'] && $key < 10) : ?>

                        <tr>
                            <td>
                                <span class="label label-success"><?= $key + 1 ?></span>
                            </td>

                            <td>
                                <strong class="strong-name">
                                    <?= $product['name'] ?>
                                </strong>
                            </td>

                            <td width="10%">
                                <span>Price: <?= $product['price'] ? $product['price'] : '' ?></span>
                            </td>

                            <td>
                                <a href="<?= $product['href'] ?>" class="link-href">
                                    <?= $product['href'] ?>
                                </a>
                            </td>
                        </tr>

                    <?php else: ?>

                        <tr>
                            <td>
                                <span class="label label-primary">...</span>
                            </td>

                            <td colspan="3">
                                <?= Html::a(sprintf('Total detailed products: ', count($model->details)), ['product/index']) ?>
                            </td>

                        </tr>

                    <?php break; endif; ?>

                <?php endforeach; ?>

            </table>

        </div>

    </div>

</div>
