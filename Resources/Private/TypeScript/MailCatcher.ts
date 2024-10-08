import $ from 'jquery'
import Modal from '@typo3/backend/modal.js'
import AjaxRequest from '@typo3/core/ajax/ajax-request.js'
import Severity from '@typo3/backend/severity.js'


class MailCatcher {
    constructor() {
        this.bindListener();
    }

    public bindListener() {
        $('.content-type-switches a').on('click', this.onContentTypeSwitchClick.bind(this));
        $('button[data-delete]').on('click', this.onDeleteButtonClick.bind(this));
        $('#delete-all-messages').on('click', this.onDeleteAllMessagesClick.bind(this));
        $('.panel').on('click', this.onPanelClick.bind(this));
    }

    protected onPanelClick(e: Event) {
        // load html mail if no plain text body available
        const htmlIsLoaded = $(e.currentTarget).attr('data-html-loaded') === 'true';
        const firstHtmlButton = $('.content-type-switches a[data-content-type="html"]:first-child', e.currentTarget);
        if (firstHtmlButton.length && !htmlIsLoaded) {
            const messageId = $(e.currentTarget).attr('data-message-file');
            this.loadHtmlMail(messageId);
        }
    }

    protected onDeleteAllMessagesClick(e: Event) {
        e.preventDefault();
        const self = this;

        Modal.confirm(TYPO3.lang['js.delete.button'], TYPO3.lang['js.delete.message'], Severity.warning, [
            {
                text: TYPO3.lang['js.delete.yes'],
                btnClass: 'btn-danger',
                trigger: function () {
                    self.deleteAllMessages();
                    Modal.dismiss();
                }
            },
            {
                text: TYPO3.lang['js.delete.no'],
                btnClass: 'btn-default',
                active: true,
                trigger: function () {
                    Modal.dismiss();
                }
            }
        ]);
    }

    protected deleteAllMessages() {
        const self = this;
        const $panel = $('.panel[data-message-file]');

        new AjaxRequest(TYPO3.settings.ajaxUrls.mailcatcher_delete_all)
            .get()
            .then(async function (response) {
                const resolved = await response.resolve();
                if (resolved.success) {
                    $panel.remove();
                    self.refreshMessageCount();
                    top.TYPO3.Notification.success(TYPO3.lang['js.success.headline'], TYPO3.lang['js.success.text'], 3);
                    return;
                }
                top.TYPO3.Notification.error(TYPO3.lang['js.error.headline'], TYPO3.lang['js.error.text'], 3);
            });
    }

    protected refreshMessageCount() {
        const count = $('.panel[data-message-file]').length;
        $('*[data-message-count]').attr('data-message-count', count);
        $('.message-count').html(count.toString());
    }

    protected onDeleteButtonClick(e: Event) {
        e.preventDefault();
        const $panel = $(e.currentTarget).closest('.panel');
        const messageFile = $panel.attr('data-message-file');
        const self = this;

        new AjaxRequest(TYPO3.settings.ajaxUrls.mailcatcher_delete)
            .withQueryArguments({messageFile: messageFile})
            .get()
            .then(async function (response) {
                const resolved = await response.resolve();
                if (resolved.success) {
                    $panel.remove();
                    self.refreshMessageCount();
                    top.TYPO3.Notification.success(TYPO3.lang['js.success.headline'], TYPO3.lang['js.success.text2'], 3);
                    return;
                }
                top.TYPO3.Notification.error(TYPO3.lang['js.error.headline'], TYPO3.lang['js.error.text2'], 3);
            });
    }

    protected onContentTypeSwitchClick(e: Event) {
        e.preventDefault();

        const contentType = $(e.currentTarget).attr('data-content-type');
        const mId = $(e.currentTarget).attr('data-m-id');

        $('.content-type-switches a[data-m-id="' + mId + '"]').addClass('btn-default').removeClass('btn-primary');
        $('.content-type-switches a[data-m-id="' + mId + '"][data-content-type="' + contentType + '"]').removeClass('btn-default').addClass('btn-primary');

        $('.form-section[data-m-id="' + mId + '"]').addClass('hidden');
        const $formSection = $('.form-section[data-m-id="' + mId + '"][data-content-type="' + contentType + '"]');
        $formSection.removeClass('hidden');

        const $panel = $(e.currentTarget).closest('.panel');

        if ($panel.attr('data-html-loaded') === 'false' && contentType === 'html') {
            this.loadHtmlMail($panel.attr('data-message-file'));
        }
    }

    protected loadHtmlMail(messageFile: string) {
        new AjaxRequest(TYPO3.settings.ajaxUrls.mailcatcher_html)
            .withQueryArguments({messageFile: messageFile})
            .get()
            .then(async function (response) {
                const resolved = await response.resolve();
                const $iframe = $('<iframe />')
                    .attr('width', '100%')
                    .attr('frameBorder', '0')
                    .attr('height', '668px')
                    .attr('srcdoc', resolved.src);
                // @ts-ignore
                $('.panel[data-message-file="' + messageFile + '"] .form-section[data-content-type="html"]').html($iframe);
                $('.panel[data-message-file="' + messageFile + '"]').attr('data-html-loaded', 'true');
            })
            .catch(() => {
                $('.panel[data-message-file="' + messageFile + '"] .form-section[data-content-type="html"]').html('<div class="callout callout-danger">' + TYPO3.lang['js.error.text3'] + '</div>');
            })
    }
}

export const XmMailCatcher = new MailCatcher();
