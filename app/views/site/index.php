<?php

use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\ActiveForm;
use yii\widgets\MaskedInput;

/** @var yii\web\View $this */
/** @var app\models\ShortLinkForm $model */

$this->title = 'Short Link + QR';
$createUrl = Url::to(['site/create-link']);
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
                    ]); ?>
                        <?= $form->field($model, 'url')->widget(MaskedInput::class, [
                            'clientOptions' => [
                                'alias' => 'url',
                            ],
                            'options' => [
                                'placeholder' => 'https://example.com/page',
                                'autocomplete' => 'off',
                            ],
                        ]) ?>

                        <div class="d-grid d-sm-flex gap-2">
                            <button type="submit" class="btn btn-primary">OK</button>
                            <button type="button" id="copy-short-url" class="btn btn-outline-secondary" disabled>Скопировать ссылку</button>
                        </div>
                    <?php ActiveForm::end(); ?>

                    <div id="short-link-error" class="alert alert-danger mt-4 d-none"></div>

                    <div id="short-link-result" class="mt-4 d-none">
                        <h2 class="h5">Результат</h2>
                        <p class="mb-2">
                            <a href="#" id="short-url-link" target="_blank" rel="noopener noreferrer"></a>
                        </p>
                        <img id="short-url-qr" src="" alt="QR code" class="img-thumbnail" style="max-width: 220px;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$js = new JsExpression(<<<JS
(() => {
    const form = $('#short-link-form');
    const errorBox = $('#short-link-error');
    const resultBox = $('#short-link-result');
    const shortUrlLink = $('#short-url-link');
    const shortQr = $('#short-url-qr');
    const copyButton = $('#copy-short-url');

    form.on('beforeSubmit', function () {
        errorBox.addClass('d-none').text('');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            dataType: 'json',
            data: {
                url: $('#shortlinkform-url').val(),
                _csrf: yii.getCsrfToken()
            }
        }).done(function (response) {
            if (!response || response.success !== true) {
                resultBox.addClass('d-none');
                copyButton.prop('disabled', true);
                const message = response && response.message ? response.message : 'Не удалось выполнить запрос.';
                errorBox.removeClass('d-none').text(message);
                return;
            }

            shortUrlLink.attr('href', response.short_url).text(response.short_url);
            shortQr.attr('src', response.qr_url);
            copyButton.prop('disabled', false);
            resultBox.removeClass('d-none');
        }).fail(function () {
            resultBox.addClass('d-none');
            copyButton.prop('disabled', true);
            errorBox.removeClass('d-none').text('Сервис временно недоступен.');
        });

        return false;
    });

    copyButton.on('click', function () {
        const shortUrl = shortUrlLink.text();
        if (!shortUrl) {
            return;
        }

        navigator.clipboard.writeText(shortUrl);
    });
})();
JS);
$this->registerJs($js);
?>
