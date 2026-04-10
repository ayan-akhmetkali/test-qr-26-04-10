<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\grid\GridView;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\models\ClickStatsSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Публичная статистика переходов';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="site-stats mt-4">
    <h1 class="h3 mb-3"><?= Html::encode($this->title) ?></h1>
    <p class="text-muted">
        Поиск по short-коду, IP и количеству переходов с IP.
    </p>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <?php $form = ActiveForm::begin([
                'method' => 'get',
                'action' => Url::to(['site/stats']),
                'options' => ['class' => 'row g-3 align-items-end'],
            ]); ?>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'short_code', [
                        'template' => "{label}\n{input}\n{error}",
                    ])->textInput(['placeholder' => 'Например: Ab3k9Q']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'ip', [
                        'template' => "{label}\n{input}\n{error}",
                    ])->textInput(['placeholder' => 'Например: 203.0.113.10']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'min_ip_clicks', [
                        'template' => "{label}\n{input}\n{error}",
                    ])->input('number', ['min' => 1, 'placeholder' => 'Например: 2']) ?>
                </div>
                <div class="col-md-3">
                    <div class="d-grid gap-2">
                        <?= Html::submitButton('Поиск', ['class' => 'btn btn-primary']) ?>
                        <?= Html::a('Сбросить', ['site/stats'], ['class' => 'btn btn-outline-secondary']) ?>
                    </div>
                </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'emptyText' => 'Данные пока отсутствуют.',
                'tableOptions' => ['class' => 'table table-striped table-hover align-middle'],
                'columns' => [
                    [
                        'attribute' => 'short_code',
                        'label' => 'Short code',
                        'value' => static fn (array $row): string => (string) $row['short_code'],
                    ],
                    [
                        'attribute' => 'ip',
                        'label' => 'IP',
                        'value' => static fn (array $row): string => (string) $row['ip'],
                    ],
                    [
                        'attribute' => 'ip_clicks',
                        'label' => 'Переходов с IP',
                        'value' => static fn (array $row): int => (int) $row['ip_clicks'],
                    ],
                    [
                        'attribute' => 'total_clicks',
                        'label' => 'Всего переходов по ссылке',
                        'value' => static fn (array $row): int => (int) $row['total_clicks'],
                    ],
                    [
                        'attribute' => 'last_click_at',
                        'label' => 'Последний переход',
                        'value' => static fn (array $row): string => (string) $row['last_click_at'],
                    ],
                    [
                        'label' => 'Ссылка',
                        'format' => 'raw',
                        'value' => static fn (array $row): string => Html::a(
                            Html::encode((string) $row['original_url']),
                            (string) $row['original_url'],
                            ['target' => '_blank', 'rel' => 'noopener noreferrer']
                        ),
                    ],
                ],
            ]) ?>
        </div>
    </div>
</div>
