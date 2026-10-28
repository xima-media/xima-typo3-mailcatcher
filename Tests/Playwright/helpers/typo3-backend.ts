import { Page, FrameLocator } from '@playwright/test';

export async function loginAsAdmin(page: Page): Promise<void> {
    await page.goto('/typo3');
    await page.waitForSelector('#t3-username', { timeout: 10000 });
    await page.fill('#t3-username', 'admin');
    await page.fill('#t3-password', 'Passw0rd!');
    await page.click('#t3-login-submit-section > button');
    await page.waitForSelector('.scaffold-header', { timeout: 20000 });
}

export async function openMailLog(page: Page): Promise<FrameLocator> {
    const contentFrame = page.frameLocator('iframe[name="list_frame"]');
    await page.click('a[data-modulemenu-identifier="system_mails"]');
    await contentFrame.locator('h1').waitFor({ state: 'visible', timeout: 10000 });
    return contentFrame;
}
