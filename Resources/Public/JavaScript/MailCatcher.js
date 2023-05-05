define(['exports', 'jquery', 'TYPO3/CMS/Backend/Modal', 'TYPO3/CMS/Core/Ajax/AjaxRequest', 'TYPO3/CMS/Backend/Severity'], (function (exports, $, Modal, AjaxRequest, Severity) { 'use strict';

    function _interopDefaultLegacy (e) { return e && typeof e === 'object' && 'default' in e ? e : { 'default': e }; }

    var $__default = /*#__PURE__*/_interopDefaultLegacy($);
    var Modal__default = /*#__PURE__*/_interopDefaultLegacy(Modal);
    var AjaxRequest__default = /*#__PURE__*/_interopDefaultLegacy(AjaxRequest);
    var Severity__default = /*#__PURE__*/_interopDefaultLegacy(Severity);

    class MailCatcher {
        constructor() {
            this.bindListener();
        }
        bindListener() {
            $__default["default"]('.content-type-switches a').on('click', this.onContentTypeSwitchClick.bind(this));
            $__default["default"]('button[data-delete]').on('click', this.onDeleteButtonClick.bind(this));
            $__default["default"]('#delete-all-messages').on('click', this.onDeleteAllMessagesClick.bind(this));
            $__default["default"]('.panel').on('click', this.onPanelClick.bind(this));
        }
        onPanelClick(e) {
            // load html mail if no plain text body available
            const htmlIsLoaded = $__default["default"](e.currentTarget).attr('data-html-loaded') === 'true';
            const onlyHtmlButton = $__default["default"]('.content-type-switches a[data-content-type="html"]:only-child', e.currentTarget);
            if (onlyHtmlButton.length && !htmlIsLoaded) {
                const messageId = $__default["default"](e.currentTarget).attr('data-message-file');
                this.loadHtmlMail(messageId);
            }
        }
        onDeleteAllMessagesClick(e) {
            e.preventDefault();
            const self = this;
            Modal__default["default"].confirm('Delete Messages', 'Are you sure, you want to delete all messages?', Severity__default["default"].warning, [
                {
                    text: 'Yes, delete',
                    btnClass: 'btn-danger',
                    trigger: function () {
                        self.deleteAllMessages();
                        Modal__default["default"].dismiss();
                    }
                },
                {
                    text: 'No, abort',
                    btnClass: 'primary-outline',
                    active: true,
                    trigger: function () {
                        Modal__default["default"].dismiss();
                    }
                }
            ]);
        }
        deleteAllMessages() {
            const self = this;
            const $panel = $__default["default"]('.panel[data-message-file]');
            new AjaxRequest__default["default"](TYPO3.settings.ajaxUrls.mailcatcher_delete_all)
                .get()
                .then(async function (response) {
                const resolved = await response.resolve();
                if (resolved.success) {
                    $panel.remove();
                    self.refreshMessageCount();
                    top.TYPO3.Notification.success('Success', 'All messages have been deleted', 3);
                    return;
                }
                top.TYPO3.Notification.error('Error', 'Could not delete messages', 3);
            });
        }
        refreshMessageCount() {
            const count = $__default["default"]('.panel[data-message-file]').length;
            $__default["default"]('*[data-message-count]').attr('data-message-count', count);
            $__default["default"]('.message-count').html(count.toString());
        }
        onDeleteButtonClick(e) {
            e.preventDefault();
            const $panel = $__default["default"](e.currentTarget).closest('.panel');
            const messageFile = $panel.attr('data-message-file');
            const self = this;
            new AjaxRequest__default["default"](TYPO3.settings.ajaxUrls.mailcatcher_delete)
                .withQueryArguments({ messageFile: messageFile })
                .get()
                .then(async function (response) {
                const resolved = await response.resolve();
                if (resolved.success) {
                    $panel.remove();
                    self.refreshMessageCount();
                    return;
                }
                top.TYPO3.Notification.error('Error', 'Could not delete message', 3);
            });
        }
        onContentTypeSwitchClick(e) {
            e.preventDefault();
            const contentType = $__default["default"](e.currentTarget).attr('data-content-type');
            const mId = $__default["default"](e.currentTarget).attr('data-m-id');
            $__default["default"]('.content-type-switches a[data-m-id="' + mId + '"]').addClass('btn-outline-primary').removeClass('btn-primary');
            $__default["default"]('.content-type-switches a[data-m-id="' + mId + '"][data-content-type="' + contentType + '"]').removeClass('btn-outline-primary').addClass('btn-primary');
            $__default["default"]('.form-section[data-m-id="' + mId + '"]').addClass('hidden');
            const $formSection = $__default["default"]('.form-section[data-m-id="' + mId + '"][data-content-type="' + contentType + '"]');
            $formSection.removeClass('hidden');
            const $panel = $__default["default"](e.currentTarget).closest('.panel');
            if ($panel.attr('data-html-loaded') === 'false' && contentType === 'html') {
                this.loadHtmlMail($panel.attr('data-message-file'));
            }
        }
        loadHtmlMail(messageFile) {
            new AjaxRequest__default["default"](TYPO3.settings.ajaxUrls.mailcatcher_html)
                .withQueryArguments({ messageFile: messageFile })
                .get()
                .then(async function (response) {
                const resolved = await response.resolve();
                const $iframe = $__default["default"]('<iframe />')
                    .attr('width', '100%')
                    .attr('height', '650px')
                    .attr('srcdoc', resolved.src);
                // @ts-ignore
                $__default["default"]('.panel[data-message-file="' + messageFile + '"] .form-section[data-content-type="html"]').html($iframe);
            });
        }
    }
    const XmMailCatcher = new MailCatcher();

    exports.XmMailCatcher = XmMailCatcher;

    Object.defineProperty(exports, '__esModule', { value: true });

}));
