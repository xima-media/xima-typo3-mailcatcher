import * as fs from 'fs';
import * as path from 'path';

export const MAIL_LOG_FILE = '/var/www/html/var/log/mail.log';
const MESSAGE_PATH = '/var/www/html/public/typo3temp/assets/xima_typo3_mailcatcher';
const FIXTURE_PATH = '/var/www/html/Tests/Playwright/Fixtures';

/**
 * Remove the mbox log and all messages the module has already extracted from it.
 */
export function resetMails(): void {
    fs.mkdirSync(path.dirname(MAIL_LOG_FILE), { recursive: true });
    fs.writeFileSync(MAIL_LOG_FILE, '');
    fs.rmSync(MESSAGE_PATH, { recursive: true, force: true });
}

export function writeMailLog(fixture: string): void {
    fs.copyFileSync(path.join(FIXTURE_PATH, fixture), MAIL_LOG_FILE);
}
