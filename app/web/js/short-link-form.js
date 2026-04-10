(function ($) {
    'use strict';

    $(function () {
        var $form = $('[data-short-link-form="1"]');
        if ($form.length === 0) {
            return;
        }

        var $urlField = $form.find('#shortlinkform-url');
        var $submitButton = $form.find('[data-role="submit"]');
        var $copyButton = $form.find('[data-role="copy"]');
        var $errorBox = $('[data-role="error"]');
        var $resultBox = $('[data-role="result"]');
        var $shortUrl = $('[data-role="short-url"]');
        var $qrImage = $('[data-role="qr-image"]');

        function resetResultState() {
            $errorBox.text('').addClass('d-none');
            $resultBox.addClass('d-none');
            $copyButton.prop('disabled', true);
        }

        function showResult(response) {
            $shortUrl.attr('href', response.short_url).text(response.short_url);
            $qrImage.attr('src', response.qr_url);
            $resultBox.removeClass('d-none');
            $copyButton.prop('disabled', false);
        }

        function showError(message) {
            $errorBox.text(message).removeClass('d-none');
        }

        function getDetailsFromErrors(errors) {
            if (!errors || typeof errors !== 'object') {
                return '';
            }

            var flattened = [];

            Object.keys(errors).forEach(function (field) {
                if (!Array.isArray(errors[field])) {
                    return;
                }

                errors[field].forEach(function (message) {
                    if (typeof message === 'string' && message.length > 0) {
                        flattened.push(message);
                    }
                });
            });

            return flattened.join(' ');
        }

        function submit() {
            resetResultState();
            $submitButton.prop('disabled', true);
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                dataType: 'json',
                data: {
                    url: $urlField.val()
                }
            }).done(function (response) {
                if (!response || response.success !== true) {
                    var baseMessage = response && response.message ? response.message : 'Не удалось выполнить запрос.';
                    var details = response ? getDetailsFromErrors(response.errors) : '';

                    showError(details ? baseMessage + ' ' + details : baseMessage);
                    return;
                }

                showResult(response);
            }).fail(function () {
                showError('Сервис временно недоступен.');
            }).always(function () {
                $submitButton.prop('disabled', false);
            });
        }

        $form.on('beforeSubmit', function () {
            submit();
            return false;
        });

        $copyButton.on('click', function () {
            var shortUrl = $shortUrl.text();
            if (!shortUrl || !navigator.clipboard) {
                return;
            }

            navigator.clipboard.writeText(shortUrl);
        });
    });
})(jQuery);
