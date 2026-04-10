(function ($) {
    'use strict';

    function ShortLinkForm($form) {
        this.$form = $form;
        this.$urlField = $form.find('#shortlinkform-url');
        this.$submitButton = $form.find('[data-role="submit"]');
        this.$copyButton = $form.find('[data-role="copy"]');
        this.$errorBox = $('[data-role="error"]');
        this.$resultBox = $('[data-role="result"]');
        this.$shortUrl = $('[data-role="short-url"]');
        this.$qrImage = $('[data-role="qr-image"]');

        this.bindEvents();
    }

    ShortLinkForm.prototype.bindEvents = function () {
        var self = this;

        self.$form.on('beforeSubmit', function () {
            self.submit();
            return false;
        });

        self.$copyButton.on('click', function () {
            self.copyShortUrl();
        });
    };

    ShortLinkForm.prototype.submit = function () {
        var self = this;
        self.setLoading(true);
        self.hideError();

        $.ajax({
            url: self.$form.attr('action'),
            type: 'POST',
            dataType: 'json',
            data: {
                url: self.$urlField.val(),
                _csrf: yii.getCsrfToken()
            }
        }).done(function (response) {
            self.handleResponse(response);
        }).fail(function () {
            self.showError('Сервис временно недоступен.');
            self.hideResult();
        }).always(function () {
            self.setLoading(false);
        });
    };

    ShortLinkForm.prototype.handleResponse = function (response) {
        if (!response || response.success !== true) {
            this.hideResult();
            this.showError(response && response.message ? response.message : 'Не удалось выполнить запрос.');
            return;
        }

        this.$shortUrl.attr('href', response.short_url).text(response.short_url);
        this.$qrImage.attr('src', response.qr_url);
        this.$resultBox.removeClass('d-none');
        this.$copyButton.prop('disabled', false);
    };

    ShortLinkForm.prototype.copyShortUrl = function () {
        var shortUrl = this.$shortUrl.text();
        if (!shortUrl || !navigator.clipboard) {
            return;
        }

        navigator.clipboard.writeText(shortUrl);
    };

    ShortLinkForm.prototype.showError = function (message) {
        this.$errorBox.text(message).removeClass('d-none');
    };

    ShortLinkForm.prototype.hideError = function () {
        this.$errorBox.text('').addClass('d-none');
    };

    ShortLinkForm.prototype.hideResult = function () {
        this.$resultBox.addClass('d-none');
        this.$copyButton.prop('disabled', true);
    };

    ShortLinkForm.prototype.setLoading = function (isLoading) {
        this.$submitButton.prop('disabled', isLoading);
        this.$submitButton.text(isLoading ? 'Создание...' : 'OK');
    };

    $(function () {
        var $form = $('[data-short-link-form="1"]');
        if ($form.length === 0) {
            return;
        }

        new ShortLinkForm($form);
    });
})(jQuery);
