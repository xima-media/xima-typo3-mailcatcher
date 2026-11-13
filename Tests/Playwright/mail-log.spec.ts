import { test, expect } from '@playwright/test';
import * as fs from 'fs';
import { loginAsAdmin, openMailLog } from './helpers/typo3-backend';
import { MAIL_LOG_FILE, resetMails, writeMailLog } from './helpers/mail-log';
import { trackConsoleErrors, ConsoleErrorTracker } from './helpers/console-errors';

test.describe('Mail Log module', () => {
    // Serial: all tests share the same mbox log file
    test.describe.configure({ mode: 'serial' });

    let consoleErrors: ConsoleErrorTracker;
    test.beforeEach(async ({ page }) => {
        resetMails();
        consoleErrors = trackConsoleErrors(page);
        await loginAsAdmin(page);
    });
    test.afterEach(() => { consoleErrors.assertNoErrors(); });

    test('is listed in the module menu', async ({ page }) => {
        await expect(page.locator('a[data-modulemenu-identifier="system_mails"]')).toBeVisible();
    });

    test('shows a mail from the log file', async ({ page }) => {
        writeMailLog('mail-env-tool.log');
        const contentFrame = await openMailLog(page);

        // Opening the module moves the mails out of the log file
        expect(fs.readFileSync(MAIL_LOG_FILE, 'utf8')).toBe('');
        await expect(contentFrame.locator('body')).toContainText('TYPO3 CMS install tool <hello@example.com>');
        await expect(contentFrame.locator('body')).toContainText('Test TYPO3 CMS mail delivery from site "New TYPO3 site"');

        await contentFrame.getByText('TYPO3 CMS install tool <hello@example.com>').click();
        await expect(contentFrame.locator('#m1')).toContainText('Hey TYPO3 Administrator');

        await contentFrame.locator('#m1 .content-type-switches [data-content-type="html"]').click();
        await expect(contentFrame.frameLocator('#m1 iframe').locator('h4')).toContainText('Hey TYPO3 Administrator');
    });

    test('lists, shows files of and deletes multiple mails', async ({ page }) => {
        writeMailLog('mail-multiple.log');
        const contentFrame = await openMailLog(page);
        await expect(contentFrame.locator('div[data-message-file]')).toHaveCount(4);

        // Show the files of the first mail
        await contentFrame.getByText('Test1: TYPO3 loves you - here is why').click();
        await expect(contentFrame.locator('#m4 .content-type-switches')).toBeVisible();
        await contentFrame.locator('#m4 .content-type-switches').getByText('Files').click();
        await expect(contentFrame.locator('#m4 .form-section[data-content-type="files"]')).toBeVisible();
        await expect(contentFrame.locator('#m4')).toContainText('test.txt');
        await expect(contentFrame.locator('#m4')).toContainText('test.html');

        // Delete the first mail
        const firstMail = contentFrame.locator('.panel[data-message-file="1725800048-74be16979710d4c4e7c6647856088456.json"]');
        await firstMail.locator('button[data-delete]').click();
        await expect(firstMail).toBeHidden();
        await expect(contentFrame.locator('div[data-message-file]')).toHaveCount(3);

        // Delete all mails, the confirmation modal opens in the main frame
        await contentFrame.getByRole('button', { name: 'Delete all messages' }).click();
        await page.locator('.modal').getByRole('button', { name: 'Yes, delete' }).click();
        await expect(page.getByText('All messages have been deleted')).toBeVisible();
        await expect(contentFrame.locator('body')).toContainText('No messages');
    });
});
