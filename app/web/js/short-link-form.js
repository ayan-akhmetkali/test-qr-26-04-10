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
                    showError(response && response.message ? response.message : 'Не удалось выполнить запрос.');
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
            return;
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
