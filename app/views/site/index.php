<?php

use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\widgets\MaskedInput;

/** @var yii\web\View $this */
/** @var app\models\ShortLinkForm $model */

$this->title = 'Short Link + QR';
$createUrl = Url::to(['site/create-link']);
$this->registerJsFile('@web/js/short-link-form.js', ['depends' => [\yii\web\JqueryAsset::class]]);
?>
<div class="site-index">
    <div class="row justify-content-center mt-5">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h3 mb-3">Создание короткой ссылки</h1>
                    <p class="text-muted mb-4">Введите URL, чтобы получить короткую ссылку и QR-код.</p>

                    <?php $form = ActiveForm::begin([
                        'id' => 'short-link-form',
                        'action' => $createUrl,
                        'enableClientValidation' => true,
                        'validateOnSubmit' => true,
                        'options' => [
                            'data-short-link-form' => '1',
                        ],
                    ]); ?>
                        <?= $form->field($model, 'url')->widget(MaskedInput::class, [
                            'options' => [
                                'placeholder' => 'https://example.com/page',
                                'autocomplete' => 'off',
                            ],
                            'clientOptions' => [
                                'alias' => 'url',
                            ],
                        ]) ?>

                        <div class="d-grid d-sm-flex gap-2">
                            <button type="submit" class="btn btn-primary" data-role="submit">OK</button>
                            <button type="button" class="btn btn-outline-secondary" data-role="copy" disabled>Скопировать ссылку</button>
                        </div>
                    <?php ActiveForm::end(); ?>

                    <div class="alert alert-danger mt-4 d-none" role="alert" data-role="error"></div>

                    <div class="mt-4 d-none" data-role="result">
                        <h2 class="h5">Результат</h2>
                        <p class="mb-2">
                            <a href="#" target="_blank" rel="noopener noreferrer" data-role="short-url"></a>
                        </p>
                        <img src="" alt="QR code" class="img-thumbnail" style="max-width: 220px;" data-role="qr-image">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
