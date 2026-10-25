const { test } = require('node:test');
const assert = require('node:assert/strict');
const { chromium } = require('@playwright/test');
const { execFileSync } = require('node:child_process');
const { readFileSync } = require('node:fs');
const { createServer } = require('node:http');

// Use the actual Blade-rendered page, with the PHP route behavior covered by the feature tests.
test('token actions, send confirmation, offline assets, and mobile navigation', async () => {
  execFileSync('php', ['vendor/bin/phpunit', '--group', 'browser-fixture'], { stdio: 'pipe' });
  const pageHtml = readFileSync('build/browser/notification.html');
  const emptyHtml = readFileSync('build/browser/empty.html');
  const posts = [];
  let base;
  const server = createServer((req, res) => {
    if (req.method === 'POST') posts.push(req.url);
    res.writeHead(200, { 'content-type': 'text/html' });
    res.end((req.url === '/empty' ? emptyHtml : pageHtml).toString().replaceAll('http://localhost', base));
  });
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  const browser = await chromium.launch(process.env.PUSHBOX_CHROMIUM ? { executablePath: process.env.PUSHBOX_CHROMIUM } : {});
  try {
    const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    const remoteRequests = [];
    base = `http://127.0.0.1:${server.address().port}`;
    await page.route('**/*', route => {
      if (!route.request().url().startsWith(base)) {
        remoteRequests.push(route.request().url());
        return route.abort();
      }
      return route.continue();
    });
    await page.goto(base + '/pushbox');
    assert.equal(await page.getByRole('button', { name: 'Send notification', exact: true }).count(), 1);
    await page.getByLabel('I confirm this is my test device.').check();
    page.once('dialog', async dialog => {
      assert.match(dialog.message(), /Configured default/);
      await dialog.dismiss();
    });
    await page.getByRole('button', { name: 'Send notification', exact: true }).click();
    assert.deepEqual(posts, []);
    page.once('dialog', dialog => dialog.accept());
    await Promise.all([
      page.waitForNavigation(),
      page.getByRole('button', { name: 'Send notification', exact: true }).click(),
    ]);
    await page.waitForLoadState();
    assert.deepEqual(posts, ['/pushbox/send']);
    await page.getByLabel('FCM test device token').fill('new-device');
    await Promise.all([
      page.waitForNavigation(),
      page.getByRole('button', { name: 'Save token', exact: true }).click(),
    ]);
    await page.waitForLoadState();
    await Promise.all([
      page.waitForNavigation(),
      page.getByRole('button', { name: 'Clear saved token', exact: true }).click(),
    ]);
    await page.waitForLoadState();
    assert.deepEqual(posts, ['/pushbox/send', '/pushbox/device-token', '/pushbox/device-token/clear']);
    assert.deepEqual(remoteRequests, []);
    assert.deepEqual(errors, []);
    await page.screenshot({ path: 'build/browser/notification.png', fullPage: true });
    await page.setViewportSize({ width: 390, height: 844 });
    await page.getByRole('button', { name: 'Open menu', exact: true }).click();
    assert.equal(await page.getByRole('button', { name: 'Close menu', exact: true }).count(), 1);
    await page.goto(base + '/empty');
    assert.equal(await page.getByRole('heading', { name: 'Register your first notification' }).count(), 1);
    assert.equal(await page.getByRole('button', { name: 'Send notification', exact: true }).count(), 0);
    await page.screenshot({ path: 'build/browser/empty-mobile.png', fullPage: true });
  } finally {
    await browser.close();
    await new Promise(resolve => server.close(resolve));
  }
});
