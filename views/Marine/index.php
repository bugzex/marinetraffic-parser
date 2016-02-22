<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Marines';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="marine-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'id_marine',
            'mmsi',
            'flag',
            'name',
            'type',
            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>

</div>
