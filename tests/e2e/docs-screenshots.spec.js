import { test, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';

/**
 * Playwright Automated Visual Documentation Generator
 * Captures clean, high-resolution screenshots for all roles:
 * - Admin, Bendahara, Ketua, Pengawas, Anggota
 * Saves screenshots into docs/screenshots/<category>/<filename>.png
 */

const SCREENSHOT_DIR = path.resolve(process.cwd(), 'docs/screenshots');

function getScreenshotPath(subDir, filename) {
    const targetDir = path.join(SCREENSHOT_DIR, subDir);
    if (!fs.existsSync(targetDir)) {
        fs.mkdirSync(targetDir, { recursive: true });
    }
    return path.join(targetDir, filename);
}

/**
 * Logs in with specified credentials and waits for redirection.
 */
async function loginAs(page, noKaryawan, password) {
    await page.goto('/login', { waitUntil: 'networkidle' });
    await page.waitForSelector('input[name="no_karyawan"]', { timeout: 10000 });

    await page.fill('input[name="no_karyawan"]', noKaryawan);
    await page.fill('input[name="password"]', password);
    await page.click('button[type="submit"]');

    await page.waitForURL((url) => !url.pathname.endsWith('/login'), { timeout: 15000 });
    await page.waitForLoadState('networkidle').catch(() => {});
    await page.waitForTimeout(600);
}

/**
 * Automatically satisfies PIN confirmation prompt if prompted.
 */
async function handlePinIfRequired(page) {
    await page.waitForTimeout(600);
    if (page.url().includes('confirm-password')) {
        const pinDigits = ['1', '2', '3', '4', '5', '6'];
        for (const digit of pinDigits) {
            const btn = page.locator(`button:has-text("${digit}")`).first();
            if (await btn.isVisible()) {
                await btn.click();
            } else {
                const input = page.locator('input[type="password"]');
                await input.press(digit);
            }
            await page.waitForTimeout(80);
        }
        await page.waitForURL((url) => !url.pathname.includes('confirm-password'), { timeout: 10000 }).catch(() => {});
        await page.waitForLoadState('networkidle').catch(() => {});
        await page.waitForTimeout(600);
    }
}

test.describe('Automated Documentation Visual Generator', () => {

    test('01 - Auth Views (Desktop & Mobile)', async ({ browser }) => {
        const context = await browser.newContext({
            viewport: { width: 1440, height: 900 },
            deviceScaleFactor: 2,
        });
        const page = await context.newPage();

        // 1. Desktop Login Page
        await page.goto('/login', { waitUntil: 'networkidle' });
        await page.waitForTimeout(500);
        await page.screenshot({
            path: getScreenshotPath('01-auth', '01-login-desktop.png'),
            fullPage: false,
        });

        // 2. Mobile Login Page
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto('/login', { waitUntil: 'networkidle' });
        await page.waitForTimeout(500);
        await page.screenshot({
            path: getScreenshotPath('01-auth', '02-login-mobile.png'),
            fullPage: false,
        });

        await context.close();
    });

    test('02 - Dashboard (Admin & Pengawas)', async ({ browser }) => {
        // Admin Dashboard
        const adminContext = await browser.newContext({
            viewport: { width: 1440, height: 900 },
            deviceScaleFactor: 2,
        });
        const adminPage = await adminContext.newPage();
        await loginAs(adminPage, 'ADM-000001', 'ADM-000001');
        await adminPage.goto('/dashboard', { waitUntil: 'networkidle' });
        await adminPage.waitForTimeout(800);
        await adminPage.screenshot({
            path: getScreenshotPath('02-dashboard', '01-dashboard-admin.png'),
            fullPage: false,
        });
        await adminContext.close();

        // Pengawas Dashboard
        const wasContext = await browser.newContext({
            viewport: { width: 1440, height: 900 },
            deviceScaleFactor: 2,
        });
        const wasPage = await wasContext.newPage();
        await loginAs(wasPage, 'WAS-000001', 'WAS-000001');
        await wasPage.goto('/dashboard', { waitUntil: 'networkidle' });
        await wasPage.waitForTimeout(800);
        await wasPage.screenshot({
            path: getScreenshotPath('02-dashboard', '02-dashboard-pengawas.png'),
            fullPage: false,
        });
        await wasContext.close();
    });

    test('03 - Pinjaman Approval Pipeline (Admin, Bendahara, Ketua)', async ({ browser }) => {
        // 1. Admin Pipeline
        const adminContext = await browser.newContext({
            viewport: { width: 1440, height: 900 },
            deviceScaleFactor: 2,
        });
        const adminPage = await adminContext.newPage();
        await loginAs(adminPage, 'ADM-000001', 'ADM-000001');
        await adminPage.goto('/pinjaman', { waitUntil: 'networkidle' });
        await adminPage.waitForTimeout(800);
        await adminPage.screenshot({
            path: getScreenshotPath('03-pinjaman', '01-pipeline-approval-admin.png'),
            fullPage: false,
        });
        await adminContext.close();

        // 2. Bendahara Evaluasi
        const benContext = await browser.newContext({
            viewport: { width: 1440, height: 900 },
            deviceScaleFactor: 2,
        });
        const benPage = await benContext.newPage();
        await loginAs(benPage, 'BEN-000001', 'BEN-000001');
        await benPage.goto('/pinjaman', { waitUntil: 'networkidle' });
        await benPage.waitForTimeout(800);
        await benPage.screenshot({
            path: getScreenshotPath('03-pinjaman', '02-evaluasi-bendahara.png'),
            fullPage: false,
        });
        await benContext.close();

        // 3. Ketua Otorisasi
        const ketContext = await browser.newContext({
            viewport: { width: 1440, height: 900 },
            deviceScaleFactor: 2,
        });
        const ketPage = await ketContext.newPage();
        await loginAs(ketPage, 'KET-000001', 'KET-000001');
        await ketPage.goto('/pinjaman', { waitUntil: 'networkidle' });
        await ketPage.waitForTimeout(800);
        await ketPage.screenshot({
            path: getScreenshotPath('03-pinjaman', '03-otorisasi-ketua.png'),
            fullPage: false,
        });
        await ketContext.close();
    });

    test('04 - Simpanan & Pengeluaran Kas', async ({ browser }) => {
        const context = await browser.newContext({
            viewport: { width: 1440, height: 900 },
            deviceScaleFactor: 2,
        });
        const page = await context.newPage();
        await loginAs(page, 'ADM-000001', 'ADM-000001');

        // 1. Simpanan
        await page.goto('/simpanan', { waitUntil: 'networkidle' });
        await page.waitForTimeout(800);
        await page.screenshot({
            path: getScreenshotPath('04-simpanan', '01-daftar-simpanan.png'),
            fullPage: false,
        });

        // 2. Pengeluaran Kas
        await page.goto('/pengeluaran', { waitUntil: 'networkidle' });
        await page.waitForTimeout(800);
        await page.screenshot({
            path: getScreenshotPath('04-simpanan', '02-pengeluaran-kas.png'),
            fullPage: false,
        });

        await context.close();
    });

    test('05 - Perhitungan SHU', async ({ browser }) => {
        const context = await browser.newContext({
            viewport: { width: 1440, height: 900 },
            deviceScaleFactor: 2,
        });
        const page = await context.newPage();
        await loginAs(page, 'ADM-000001', 'ADM-000001');

        await page.goto('/shu', { waitUntil: 'networkidle' });
        await page.waitForTimeout(800);
        await page.screenshot({
            path: getScreenshotPath('05-shu', '01-perhitungan-shu.png'),
            fullPage: false,
        });

        await context.close();
    });

    test('06 - Portal Anggota (Desktop & Mobile)', async ({ browser }) => {
        const context = await browser.newContext({
            viewport: { width: 390, height: 844 }, // Mobile Viewport for Member Portal
            deviceScaleFactor: 2,
            isMobile: true,
        });
        const page = await context.newPage();
        await loginAs(page, 'TOP-2571209', 'TOP-2571209');

        // 1. Dashboard Portal Anggota
        await page.goto('/portal/dashboard', { waitUntil: 'networkidle' });
        await page.waitForTimeout(800);
        await page.screenshot({
            path: getScreenshotPath('06-portal-anggota', '01-portal-dashboard.png'),
            fullPage: false,
        });

        // 2. Form Ajukan Pinjaman
        await page.goto('/portal/pinjaman/ajukan', { waitUntil: 'networkidle' });
        await page.waitForTimeout(800);
        await page.screenshot({
            path: getScreenshotPath('06-portal-anggota', '02-pengajuan-pinjaman.png'),
            fullPage: false,
        });

        // 3. Riwayat Transaksi
        await page.goto('/portal/riwayat', { waitUntil: 'networkidle' });
        await page.waitForTimeout(800);
        await page.screenshot({
            path: getScreenshotPath('06-portal-anggota', '03-riwayat.png'),
            fullPage: false,
        });

        // 4. Profil Anggota & Rekening
        await page.goto('/portal/profil', { waitUntil: 'networkidle' });
        await page.waitForTimeout(800);
        await page.screenshot({
            path: getScreenshotPath('06-portal-anggota', '04-profil.png'),
            fullPage: false,
        });

        // 5. Modal Panduan
        await page.goto('/portal/dashboard', { waitUntil: 'networkidle' });
        await page.waitForTimeout(500);
        // Open panduan modal
        await page.evaluate(() => window.dispatchEvent(new CustomEvent('open-panduan')));
        await page.waitForTimeout(600);
        await page.screenshot({
            path: getScreenshotPath('06-portal-anggota', '05-modal-panduan.png'),
            fullPage: false,
        });

        await context.close();
    });

    test('07 - Pengaturan & Kebijakan Sistem', async ({ browser }) => {
        const context = await browser.newContext({
            viewport: { width: 1440, height: 900 },
            deviceScaleFactor: 2,
        });
        const page = await context.newPage();
        await loginAs(page, 'ADM-000001', 'ADM-000001');

        // Navigasi ke pengaturan
        await page.goto('/pengaturan?tab=aturan-pinjaman', { waitUntil: 'networkidle' });
        await handlePinIfRequired(page);
        await page.waitForTimeout(800);

        // 1. Kebijakan & Aturan Pinjaman
        await page.screenshot({
            path: getScreenshotPath('07-pengaturan', '01-kebijakan-koperasi.png'),
            fullPage: false,
        });

        // 2. Audit Log
        await page.goto('/pengaturan?tab=audit', { waitUntil: 'networkidle' });
        await handlePinIfRequired(page);
        await page.waitForTimeout(800);
        await page.screenshot({
            path: getScreenshotPath('07-pengaturan', '02-audit-log.png'),
            fullPage: false,
        });

        await context.close();
    });

});
