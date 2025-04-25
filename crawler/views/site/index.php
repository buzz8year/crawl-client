<?php

/** @var yii\web\View $this */

$this->title = 'My Yii Application';

?>

<style>
#w2 {display: none;}
</style>

<div class="site-index">

    <div class="">
        <h2>Hello, this is crawler dashboard</h2>

        <p class="lead">What are you to parse today?</p>
    </div>

    <div class="body-content">

        <div class="row">
            <div class="col-lg-4">
                <h4>E-commerce ?</h4>
                <p>Parse goods, shops, marketplaces, e-commerce, etc.</p><br/>
                <p><a href="/index.php?r=parser/index" class="btn btn-success">Go &nbsp; &rarr;</a></p>
            </div>
            <div class="col-lg-4">
                <h4>News ?</h4>
                <p>Parse blogs, news, forums, etc.</p><br/>
                <p><a class="btn btn-default">Coming...</a></p>
            </div>
        </div>

    </div>
</div>
