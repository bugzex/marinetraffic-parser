<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Track */

$this->title = $model->id_track;
$this->params['breadcrumbs'][] = ['label' => 'Tracks', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="track-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id_track' => $model->id_track, 'id_marine' => $model->id_marine], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id_track' => $model->id_track, 'id_marine' => $model->id_marine], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id_track',
            'id_marine',
            'lat',
            'lon',
            'speed',
            'course',
            'age',
            'date_add',
        ],
    ]) ?>

</div>
