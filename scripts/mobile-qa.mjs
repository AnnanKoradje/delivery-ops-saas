import { chromium } from 'playwright-core';

const baseUrl = process.env.MOBILE_QA_BASE_URL;
if (!baseUrl) throw new Error('MOBILE_QA_BASE_URL is required');

const browser = await chromium.launch({
  headless: true,
  executablePath: '/usr/bin/chromium',
  args: ['--no-sandbox'],
});
const page = await browser.newPage({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 1 });
await page.goto(`${baseUrl}/login`, { waitUntil: 'networkidle' });
await page.getByLabel('Email').fill('local-admin@example.test');
await page.getByLabel('Password').fill('TemporaryPreviewOnly-2026');
await page.getByRole('button', { name: /log in/i }).click();
await page.waitForURL('**/platform');
await page.screenshot({ path: '/home/ubuntu/laravel-mobile-dashboard.png', fullPage: true });

const before = await page.evaluate(() => ({
  width: window.innerWidth,
  horizontalOverflow: document.documentElement.scrollWidth > document.documentElement.clientWidth,
  mobileTrigger: Boolean(document.querySelector('button[aria-label="Open navigation menu"]')),
  desktopSidebarVisible: getComputedStyle(document.querySelector('aside')).display !== 'none',
}));
await page.getByRole('button', { name: 'Open navigation menu' }).click();
await page.waitForTimeout(300);
const afterOpen = await page.evaluate(() => ({
  drawerVisible: getComputedStyle(document.querySelector('[role="dialog"]')).display !== 'none',
  mobileOverview: Boolean(document.querySelector('[aria-label="Mobile primary navigation"] a')),
  drawerWidth: document.querySelector('[role="dialog"] aside')?.getBoundingClientRect().width ?? 0,
}));
await page.getByRole('button', { name: 'Close navigation menu' }).click();
await page.waitForTimeout(300);
const afterClose = await page.evaluate(() => getComputedStyle(document.querySelector('[role="dialog"]')).display === 'none');

console.log(JSON.stringify({ before, afterOpen, afterClose }));
await browser.close();
