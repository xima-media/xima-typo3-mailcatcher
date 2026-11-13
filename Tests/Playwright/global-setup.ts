import { resetMails } from './helpers/mail-log';

export default async function globalSetup(): Promise<void> {
    resetMails();
}
