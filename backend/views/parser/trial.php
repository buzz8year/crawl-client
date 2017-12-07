<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use backend\models\parser\ParserProvisioner;
use backend\models\Source;
use backend\models\CategorySource;
use backend\models\opencart\OcSettler;
use yii\widgets\Breadcrumbs;

\app\assets\ParserTrialAsset::register($this);

$this->title = 'Парсинг ' . $model->title;
$this->params['newcrumbs'][] = $model->title;

echo Breadcrumbs::widget([
    'homeLink' => ['label' => 'Парсинг', 'url' => 'index.php'],
    'links' => isset($this->params['newcrumbs']) ? $this->params['newcrumbs'] : [],
]);

// OcSettler::saveProducts();

?>

<div class="row">
    <div class="col-lg-6">
        <h1><?= $model->title ?></h1>
        <small><?= Html::a($model->domain, $model->domain, ['target' => '_blank']) ?></small>
    </div>
    <div class="col-lg-4 pull-right">
        <h4>&nbsp;</h4>
        <span><?php echo Html::a('Редактировать', ['source/update', 'id' => $model->id]); ?></span>
        <span class="pull-right"><?php echo Html::a('Спарсить категории', ['parser/tree', 'id' => $model->id]); ?></span>
    </div>
</div>

<div class="keyword-form"><br/><br/>

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">

        <div class="col-md-8">

            <?= $form->field($model, 'url')->textInput(['id' => 'parseInput', 'readonly' => true, 'placeholder' => 'Select a category →'])->label('URL') ?>
            <?php if (isset($model->url)) : ?>
                <small class="pull-right" style="margin-top: 5px; word-break: break-all; width: 40%"><?= Html::a($model->url, $model->url, ['target' => '_blank']) ?></small>
            <?php endif; ?>
            <div class="text-muted pull-left">Категория и поиск по ней:</div><br/>
            <div class="text-muted">
                <small>Если при выборе очередного значения, поле для ссылки обнуляется, 
                    <br/>значит в подобной связке поиск по данному ресурсу невозможен.
                </small><br/>
            </div><br/><br/><br/><br/>

            <div class="form-group pull-left text-muted">
                <?= Html::submitButton('Parse Goods', ['class' => 'btn btn-success', 'name' => 'parseGoods', 'value' => 1]) ?><br/><br/>
                <small><del>Ограничение хождения по страницам товаров: 2</del></small><br/>
                <small><del>Количество страниц каталога с товарами: 10</del></small><br/><br/>
                <label style="font-weight:normal">
                    <?= Html::input('checkbox', 'parseSales', 1, ['style' => 'position:relative;top:1px', 'checked' => $model->saleFlag]) ?>
                    Парсинг только акционных товаров
                </label><br/><br/>
            </div>

            <div class="form-group pull-right text-right text-muted">
                <?= Html::submitButton(
                        'Выгрузить в магазин', 
                        [
                            'name'  => 'syncGoods', 
                            'class' => 'btn btn-primary', 
                            'value' => 1, 
                            'disabled' => (bool)count($model->products),
                            'title' => (bool)count($model->products) ? 'У вас есть результаты парсинга, которые ожидают команды к детализации' : '',
                        ]
                    ) 
                ?><br/><br/>
                <?php if ($syncData) : ?>
                    <small>Обработано: <?= $syncData['processed'] ?>, Синхронизировано: <?= $syncData['synced'] ?></small><br/>
                <?php else: ?>
                    <small>Товаров: <?= count(Source::findOne($model->id)->products) ?></small><br/>
                    <small>Несинх.: <?= count(Source::findOne($model->id)->asyncProducts) ?></small><br/>
                <?php endif; ?>
            </div>

            <div class="row">
                <?php if (isset($model->products)) : ?>
                    <br/><br/>
                    <div class="col-xs-12">

                        <h3>Найдено новых позиций: <?= count($model->details) ?></h3>
                        <small>Всего найдено: <?= count($model->products) ?></small>
                        <?php if (count($model->products)) : ?>
                            <div><?= Html::a('<i class="glyphicon glyphicon-share"></i> К просмотру товаров', ['product/index'], ['target' => '_blank']) ?></div><br/><br/>
                            <div class="form-group">
                                <?= Html::submitButton('Parse Details', ['class' => 'btn btn-primary', 'name' => 'parseDetails', 'value' => json_encode($model->details)]) ?>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                <?php endif; ?>
                
                <?php if ($detailsParsed) : ?>
                    <br/><br/>
                    <div class="col-xs-10 text-muted">
                        <h4>Детализировано позиций: <?= $detailsParsed ?></h4>
                        <div><?= Html::a('<i class="glyphicon glyphicon-share"></i> К просмотру товаров', ['product/index'], ['target' => '_blank']) ?></div><br/><br/>
                        <small>И записано в базу. Описания, аттрибуты, изображения.</small><br/><br/>
                        <small>Детализация проходит отдельно по ряду причин: итерация совместно с основным парсингом не дает никаких преимуществ - обращения к страницам товаров в любом случае происходят отдельными запросами; фиксация изменения цены во всех случаях возможна при основном парсинге; цели, следить за изменениями описаний, аттрибутов, изображений - нет, т.к. и смысла в этом мало; раздельный парсинг увеличивает отзывчивость/интерактивность в рамках пользовательского интерфейса; распределяет нагрузку на сервер.</small><br/><br/>
                    </div>
                <?php endif; ?>

            </div><br/><br/>

        </div>

        <div class="col-md-4">
            <label>
                <?= $sourceRegions ? 'Регион, категория' : 'Категория' ?> и ключевое слово:
                <div>
                    <?= Html::submitButton('Обновить кэш', [
                        'value' => 1,
                        'name'  => 'flushTree', 
                        'class' => 'label', 
                        'style' => 'color: #37a; padding: 0; background: none; border: none; position: absolute; right: 15px; top: 5px',
                    ]) ?>
                </div>
            </label>

            <?php if ($sourceRegions) {
                echo Html::dropDownList('', '', $sourceRegions, [
                    'id' => 'regionList',
                    'class' => 'form-control',
                    'options'   => [
                        $model->regionId => [
                            'selected' => true
                        ],
                    ],
                    'onchange'  => 'location = \'index.php?r=parser/trial&reg=\' + $(this).val() + \'&id=' . $model->id . '&word=' . $model->regionId . '&cat=' . $model->categoryId . '\';',
                    'prompt'    => '-- Region --'
                ]);
            } ?><br/>


            <div id="categoryTree">
                <input type="hidden" id="categoryList" value="<?= $model->categoryId ?>" />
                <div class="col-xs-12" style="margin-bottom:10px">
                    <div class="row expanded">
                        <div class="category-tree-row">
                            <span   class="category-tree-select <?= $model->categorySourceId ? '' : 'selected' ?>" 
                                    onclick="categoryOnSelect('<?= $model->id ?>', '<?= $model->regionId ?>', '');"
                                    style="padding-left: 40px" >
                                        <span class="tree-zero">&#9900;</span>
                                        Без категории
                            </span>
                        </div>
                    </div>
                </div>

                <?php echo ParserProvisioner::displayNestedSelect($sourceCategories, $model->id, $model->regionId, $model->categorySourceId); ?>

            </div><br/>

            <?php
                echo Html::dropDownList('', '', $sourceKeywords, [
                    'id' => 'keywordList',
                    'class' => 'form-control',
                    'prompt' => '-- Keyword --',
                    'options'   => [
                        $model->keywordId => [
                            'selected' => true
                        ],
                    ],
                    'disabled' => false,
                    'onchange'  => '
                        var keyword = $(this).val() ? $(this).find(\':selected\').text() : \'\';
                        location = \'index.php?r=parser/trial&word=\' + keyword + \'&id=' . $model->id . '&reg=' . $model->regionId . '&cat=' . $model->categoryId . '\';
                    ',
                ]); 
            ?><br/>

            <?= Html::input('text', 'flyKeyword', '', [
                    'class' => 'form-control input', 
                    'placeholder' => '...или введите новое',
                    // 'onkeypress' => 'console.log($(this).val())',
                    // 'onchange' => 'location = \'index.php?r=parser/fly-keyword&word=\' + $(this).val() + \'&id=\'' . $model->id,
                ]) 
            ?>
        </div>
    </div><br/><br/>



    <?php ActiveForm::end(); ?>

</div><br/><br/>

<div class="body-content">

    <div class="row">
        <div class="col-lg-12">



            <?php 
            if ($model->warnings) {
                foreach ($model->warnings as $warning) {
                    echo '<span class="label label-danger">Сообщение с запр. страницы</span>' . $warning;
                }
            }
            ?>

            <table class="table table-striped">

                <?php foreach ($model->products ?? [] as $key => $product) : ?>

                    <?php if ($product['href'] && $key < 10) : ?>

                            <tr>
                                <td><span class="label label-success"><?= ($key + 1) ?></span></td>
                                <!-- <td style="padding:5px">
                                    <?php if (isset($product['images']) && count($product['images'])) : ?>
                                        <img src="<?= $product['images'][0]['thumb'] ? $product['images'][0]['thumb'] : $product['images'][0]['fullsize'] ?>" height="30" />
                                    <?php endif; ?>
                                </td> -->
                                <td><strong  style="display:block; max-width: 20vw; overflow: hidden;"><?= $product['name'] ?></strong></td>
                                <!-- <td width="10%">Цена: <?= $product['price'] ?></td> -->
                                <td><a href="<?= $product['href'] ?>" style="display:block; max-width: 30vw; overflow: hidden;"><?= $product['href'] ?></a></td>
                                
                            </tr>

                            <!-- <?php if (isset($product['descriptions'])) : ?>
                                <tr>
                                    <td class="text-muted" colspan="5">
                                        <?php foreach ($product['descriptions'] as $description) : ?>
                                            <?= $description['text'] ?><br/><br/>
                                        <?php endforeach; ?>
                                        <?php if (isset($product['attributes'])) foreach ($product['attributes'] as $attribute) : ?>
                                            <?= $attribute['title'] . ': ' . $attribute['value'] ?><br/>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                            <?php endif; ?> -->

                    <?php else: ?>

                        <tr>
                            <td><span class="label label-primary">...</span></td>
                            <td colspan="2"><?= Html::a('Еще товаров: ' . (count($model->details) - 10), ['product/index']) ?></td>
                        </tr>

                        <?php break; ?>

                    <?php endif; ?>


                <?php endforeach; ?>

            </table>

        </div>

    </div>

</div>

<?php

// $this->registerJs('
//     $(document).ready(function(){
//         $.ajax({
//             type: \'post\',
//             url: \'index.php?r=parser/load-select\',
//             data: {
//                 cats: \'' . json_encode($sourceCategories) . '\', 
//                 sourceId: \'' . $model->id . '\', 
//                 regionId: \'' . $model->regionId . '\', 
//                 currentCat: \'' . $model->categorySourceId . '\'
//             },
//             success: function (data) {
//                 html = $(data);
//                 html.hide();
//                 $(\'#categoryTree\').append(html);
//                 html.fadeIn(\'slow\');
//             }
//         });
//     });
// ');

?>