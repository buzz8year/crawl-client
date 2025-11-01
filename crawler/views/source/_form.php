<?php

use crawler\models\region\Region;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use crawler\models\source\Source;
use crawler\models\proxy\Proxy;
use crawler\models\keyword\Keyword;
use crawler\models\header\Header;
use crawler\models\header\HeaderValue;

\app\assets\SourceFormAsset::register($this);

?>

<div class="source-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-lg-6">
            <?= $form->field($model, 'title')->textInput(['maxlength' => true, 'readonly' => false]) ?>
            <?= $form->field($model, 'class_namespace')->textInput() ?>
        </div>
        <div class="col-lg-5 pull-right">
            <?= $form->field($model, 'source_url')->textInput(['maxlength' => true, 'readonly' => false]) ?>
            <?= $form->field($model, 'status')->radioList([1 => 'On', 0 => 'Off'], [
                'item' => function ($index, $label, $name, $checked, $value) {
                    return '<label class="radio-inline">' . Html::radio($name, $checked, ['value'  => $value]) . $label . '</label>';
                }
            ] ) ?>
        </div>
    </div><br/>

    <div class="row">
        <div class="col-lg-2">
            <?= $form->field($model, 'need_synonymizer')->radioList(Source::synonymizeStatusText(), [
                'item' => function ($index, $label, $name, $checked, $value) {
                    return '<label class="radio-inline">' . Html::radio($name, $checked, ['value'  => $value]) . $label . '</label>';
                }
            ]) ?>
        </div>
        <div class="col-lg-2">
            <?= $form->field($model, 'need_proxy')->radioList( Source::proxyStatusText(), [
                'item' => function ($index, $label, $name, $checked, $value) {
                    return '<label class="radio-inline">' . Html::radio($name, $checked, ['value'  => $value]) . $label . '</label>';
                }
            ] ) ?>
        </div>
        <div class="col-lg-2">
            <?= $form->field($model, 'need_captcha')->radioList( Source::captchaStatusText(), [
                'item' => function ($index, $label, $name, $checked, $value) {
                    return '<label class="radio-inline">' . Html::radio($name, $checked, ['value'  => $value]) . $label . '</label>';
                }
            ] ) ?>
        </div>
        <div class="col-lg-4 pull-right">
            <?= $form->field($model, 'search_applicable')->radioList([1 => 'Yes', 0 => 'No (use constants from object class)'], [
                'item' => function ($index, $label, $name, $checked, $value) {
                    return '<label class="radio-inline">' . Html::radio($name, $checked, ['value'  => $value]) . $label . '</label>';
                },
                'onchange'  => '$(this).find(\':checked\').val() == 1 ? $(this).closest(\'.wrap-query\').find(\'input[type=text]\').removeAttr(\'readonly\') : $(this).closest(\'.wrap-query\').find(\'input[type=text]\').prop(\'readonly\', true);',
            ]) ?>
            </div>
    </div><br/>


    <div class="row">

        <div class="col-lg-7">
            <?= $form->field($model, 'description')->textarea(['rows' => 8]) ?>
        </div>


        <div class="wrap-query col-xs-4 pull-right">
        
            <?= $form->field($model, 'search_action')->textInput(['readonly' => $model->search_applicable ? false : true, 'placeholder' => 'Напр.: ?context=search OR search.php?']) ?>
            <?= $form->field($model, 'search_category')->textInput(['readonly' => $model->search_applicable ? false : true, 'placeholder' => 'Напр.: category_group=']) ?>
            <?= $form->field($model, 'search_keyword')->textInput(['readonly' => $model->search_applicable ? false : true, 'placeholder' => 'Напр.: text=']) ?>            

        </div>

        

    </div>


    <br/><hr/><br/>


    <div class="row">


        <div class="col-lg-4 pull-right">

            <?= $form->field($model, 'limit_page')->textInput() ?>
            <?= $form->field($model, 'limit_detail')->textInput() ?>

            <hr/>

            <div class="row">
                <div class="col-lg-12">
                    <label>Keywords:</label>
                    <?= Html::dropDownList(
                            '', 
                            [],
                            Keyword::listKeywords(),
                            [
                                'class'     => 'form-control',
                                'prompt'    => 'Select to add an existing keyword',
                                'onchange'  => 'pasteToEmpty( $(this).find(":selected").text() ); $(this).prop(\'selectedIndex\', 0);',
                            ]
                        );
                    ?>
                </div>
            </div><br/>

            <?php if ($model->words) :  ?>

                <?php foreach ($model->words as $keyword) : ?>

                    <div class="row keyword">
                        <div class="col-lg-10">
                            <input type="text" value="<?= $keyword->word ?>" name="keywords[]" class="form-control">
                        </div>
                        <div class="col-lg-1">
                            <i class="btn btn-danger glyphicon glyphicon-trash keyword-remove"></i>
                        </div>
                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

            <button class="btn btn-primary keyword-add" type="button"><i class="glyphicon glyphicon-plus"></i></button>



            
            <div class="row">
                <div class="col-sm-12"><br><br><hr><br>
                    <label>Regions:</label>
                    <?= Html::dropDownList(
                            '', 
                            [],
                            Region::listRegions(),
                            [
                                'class'     => 'form-control',
                                'prompt'    => 'Select to add an existing region',
                                'onchange'  => 'pasteRegionToEmpty( $(this).find(":selected").text() ); $(this).prop(\'selectedIndex\', 0);',
                            ]
                        );
                    ?>
                </div>
            </div><br/>

            <?php if ($model->regions) :  ?>

                <?php foreach ($model->regions as $key => $region) : ?>

                    <div class="row region region-value-<?= $key ?>">
                        <div class="col-sm-10">
                            <span   class="pull-left btn btn-default label label-<?= $region['status'] == 2 ? 'primary' : 'white' ?> region-value-status"
                                    title="Является ли регион глобальным?" 
                                    onclick="$.get('index.php?r=source/region-status&id=<?= $region['id'] ?>&source=<?= $model->id ?>')
                                        .done( function (data) {
                                            console.log(data);
                                            var regionValue  = $('.region-value-<?= $key ?> input[name=\'regions[]\']');
                                            var regionStatus = $('.region-value-<?= $key ?> .region-value-status');
                                            if (data == 2) {
                                                regionStatus.removeClass('label-white').addClass('label-primary');
                                                regionValue.val('<?= $region['alias'] ?>' + '+' + data);
                                            } else if (data == 1) {
                                                regionStatus.removeClass('label-primary').addClass('label-white');
                                                regionValue.val('<?= $region['alias'] ?>' + '+' + data);
                                            } else {
                                                alert(data);
                                            }
                                        });
                            ">
                                <small>Global</small>
                            </span>

                            <input type="hidden" value="<?= $region['alias'] . '+' . $region['status'] ?>" name="regions[]">
                            <input type="text" value="<?= $region['alias'] ?>" class="form-control text-right">
                        </div>
                        <div class="col-sm-1">
                            <i class="btn btn-danger glyphicon glyphicon-trash region-remove"></i>
                        </div>
                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

            <button class="btn btn-primary region-add" type="button"><i class="glyphicon glyphicon-plus"></i></button>


        </div>

    

        <div class="col-lg-7">

            <div class="row">
                <div class="col-xs-12">
                    <label>HTTP Headers:</label>

                    <?php 
                    echo Html::dropDownList(
                        '', 
                        [],
                        Header::listHeaders(),
                        [
                            'id'        => 'headerSelect',
                            'class'     => 'form-control',
                            'prompt'    => 'Select an existing Header (lowered to user-agent only)',
                            'onchange'  => 'unhideSelector( $(this).find(\':selected\').val() );',
                        ]
                    );
                    ?>

                </div>
            </div><br/>

            <div class="row">
                <div class="col-xs-12">

                    <?php 
                    foreach (Header::listHeaders() as $id => $header) :
                        // if ( Header::headerValues($id) ) :
                            echo Html::dropDownList(
                                '', 
                                [],
                                Header::headerValues($id),
                                [   
                                    'data-id'   => $id,
                                    'disabled'  =>  Header::headerValues($id) ? false : true,
                                    'class'     => 'form-control header-value-select hidden',
                                    'prompt'    => 'Select from list to add a Value',
                                    'onchange'  => 'pasteHeaderValue( $(this).find(\':selected\').val(), \'' . $model->id . '\' );',
                                ]
                            );
                        // endif;
                    endforeach; 
                    ?>

                </div>
            </div><br/>


            <label>Assigned Values:</label><br/><br/>

            <div class="row">
                <div class="col-lg-2">On/Off/Queue</div>
                <div class="col-lg-9"><small class="text-muted">* Queue is claimed among values of same header</small></div>
            </div><br/>

            <button class="btn btd-success header-value-add hidden" type="button"><i class="glyphicon glyphicon-plus"></i></button>


            <?php if ($model->headerSources) :  ?>

                <?php foreach ($model->headerSources as $key => $headerSource) : ?>

                    <div class="row header-value header-value-<?= $key ?>">

                        <div class="col-lg-2">

                            <span   class="pull-left btn btn-default label label-<?= $headerSource->status ? 'primary' : 'danger' ?> header-value-status"
                                    title="Turn on/off" 
                                    onclick="$.get('index.php?r=source/header-status&id=<?= $headerSource->id ?>')
                                        .done( function (data) {
                                            var valueRow = $('.header-value-<?= $key ?> .header-value-status');
                                            if (data == 0) valueRow.removeClass('label-primary').addClass('label-danger').find('small').text('OFF');
                                            else valueRow.removeClass('label-danger').addClass('label-primary').find('small').text('ON');
                                        });
                            ">
                                <small><?= $headerSource->status ? 'ON' : 'OFF' ?></small>
                            </span>

                            <input  type="text" 
                                    value="<?= $headerSource->queue ?>" 
                                    class="form-control input-sm text-right header-value-queue" 
                                    title="Очередный порядок значения в массиве опций для Сurl сессии (при неудачной попытке происходит ротация опций до достижения успешной, при истощении массива предпринимается крайняя попытка без заголовка, т.е. используется настоящий, в продакшене эту попытку стоит упразднить)."
                                    onblur="$.get( 'index.php?r=source/header-queue&id=<?= $headerSource->id ?>&queue=' + $(this).val() )
                                        .done( function(data) { if (data) $('.header-value-<?= $key ?> .header-value-queue').parent().addClass('has-success'); });"
                            />

                        </div>


                        <div class="col-lg-9">
                            <input type="text" value="<?= Header::findOne($headerSource->header_id)->title ?>: <?= HeaderValue::findOne($headerSource->header_value_id)->value ?>" class="form-control input-sm" readonly>
                            <input type="hidden" value="<?= $headerSource->header_value_id ?>" name="header-old-values[<?= $headerSource['id'] ?>][]" >
                        </div>

                        <div class="col-lg-1">
                            <i class="btn btn-sm btn-danger glyphicon glyphicon-trash header-value-remove" onclick="deleteHeaderValue(<?= $headerSource->id ?>, <?= $key ?>);"></i>
                        </div>
                       
                    </div><br/>

                <?php endforeach; ?>

            <?php endif; ?>



            <br/><hr/><br/>

            <div class="row">
                <div class="col-lg-9">
                    <label>Proxy:</label>

                    <?php 

                    echo Html::dropDownList(
                        '', 
                        [],
                        Proxy::listProxies(),
                        [
                            'id'        => 'proxySelect',
                            'class'     => 'form-control',
                            'prompt'    => 'Select to add an existing Proxy',
                            'onchange'  => 'pasteProxyValue( $(this).find(\':selected\').val(), \'' . $model->id . '\' ); unhideSelector( $(this).find(\':selected\').val() );',
                        ]
                    );

                    ?>

                    <br/><br/>


                    <label>Assigned Proxies:</label><br/><br/>

                    <button class="btn btd-success proxy-value-add hidden" type="button"><i class="glyphicon glyphicon-plus"></i></button>


                    <?php if ($model->proxySources) :  ?>

                        <?php foreach ($model->proxySources as $key => $proxySource) : ?>

                            <div class="row proxy-value proxy-value-<?= $key ?>">

                                <div class="col-lg-3">
                                    <span   class="pull-left btn btn-default label label-<?= $proxySource->status ? 'primary' : 'danger' ?> proxy-value-status"
                                            title="Turn on/off" 
                                            onclick="$.get( 'index.php?r=source/proxy-status&id=<?= $proxySource->id ?>' )
                                                .done( function (data) {
                                                    var valueRow = $('.proxy-value-<?= $key ?> .proxy-value-status');
                                                    if (data == 0) valueRow.removeClass('label-primary').addClass('label-danger').find('small').text('OFF');
                                                    else valueRow.removeClass('label-danger').addClass('label-primary').find('small').text('ON');
                                                }
                                            );"
                                    ><small><?= $proxySource->status ? 'ON' : 'OFF' ?></small></span>

                                    <input  type="text" value="<?= $proxySource->queue ?>" class="form-control input-sm text-right proxy-value-queue" 
                                            title="Очередный порядок значения в массиве опций для Сurl сессии (при неудачной попытке происходит ротация опций до достижения успешной, при истощении массива предпринимается крайняя попытка без прокси, т.е. используется настоящий, в продакшене эту попытку стоит упразднить)."
                                            onblur="$.get( 'index.php?r=source/proxy-queue&id=<?= $proxySource->id ?>&queue=' + $(this).val() )
                                                .done( function(data) { 
                                                    if (data) 
                                                        $('.proxy-value-<?= $key ?> .proxy-value-queue').parent().addClass('has-success'); 
                                                });"
                                    />

                                </div>


                                <div class="col-lg-7">
                                    <input type="text" value="<?= Proxy::findOne($proxySource->proxy_id)->ip . ':' . ( Proxy::findOne($proxySource->proxy_id)->port ?? '????' ) ?>" class="form-control input-sm" readonly>
                                    <input type="hidden" value="<?= $proxySource->proxy_id ?>" name="proxy-old-values[<?= $proxySource['id'] ?>][]" >
                                </div>

                                <div class="col-lg-2 text-center">
                                    <i class="btn btn-sm btn-danger glyphicon glyphicon-trash proxy-value-remove" onclick="deleteProxyValue(<?= $proxySource->id ?>, <?= $key ?>);"></i>
                                </div>
                               
                            </div><br/>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>

            </div>

        </div>


        <div class="col-sm-4 pull-right">

        </div>


        

    </div><br/><br/>



    <div class="row">
        <div class="col-xs-12"><br/><br/>
            <div class="form-group">
                <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update',
                    ['class' => 'btn btn-success']) ?>
            </div>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
