<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Track */

$this->title = 'Update Track: ' . ' ' . $model->id_track;
$this->params['breadcrumbs'][] = ['label' => 'Tracks', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id_track, 'url' => ['view', 'id_track' => $model->id_track, 'id_marine' => $model->id_marine]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="track-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
