const { test } = require('node:test');
const assert = require('node:assert/strict');
const { chromium } = require('@playwright/test');
const { execFileSync } = require('node:child_process');
const { readFileSync } = require('node:fs');
const { createServer } = require('node:http');

// Render the real Blade views; the local server stubs transport, never Firebase.
test('workspace navigation, device settings, sending feedback, preview states, and copying', async () => {
  execFileSync('php', ['vendor/bin/phpunit', '--group', 'browser-fixture'], { stdio: 'pipe' });
  const fixtures = Object.fromEntries(['notification', 'empty', 'no-device', 'failed', 'accepted', 'saved-device'].map(name => [name, readFileSync(`build/browser/${name}.html`, 'utf8')]));
  const posts = [];
  let sendMode = 'failed';
  let base;
  const server = createServer((req, res) => {
    const isSend = req.method === 'POST' && req.url === '/pushbox/send';
    if (req.method === 'POST') posts.push(req.url);
    const name = isSend ? sendMode : req.url === '/empty' ? 'empty' : req.url === '/no-device' ? 'no-device' : req.url === '/saved-device' ? 'saved-device' : 'notification';
    setTimeout(() => {
      if (isSend && sendMode === 'limited') { res.writeHead(429); res.end('Too many attempts'); return; }
      res.writeHead(200, { 'content-type': 'text/html' });
      res.end((fixtures[name] || fixtures.notification).replaceAll('http://localhost', base));
    }, isSend ? 180 : 0);
  });
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  let browser;
  try {
    browser = await chromium.launch(process.env.PUSHBOX_CHROMIUM ? { executablePath: process.env.PUSHBOX_CHROMIUM } : {});
    const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
    await page.addInitScript(() => {
      window.copiedValues = [];
      Object.defineProperty(navigator, 'clipboard', { value: { writeText: async value => window.copiedValues.push(value) } });
    });
    const errors = [];
    const remoteRequests = [];
    const dialogs = [];
    page.on('pageerror', error => errors.push(error.message));
    page.on('dialog', async dialog => { dialogs.push(dialog.message()); await dialog.dismiss(); });
    base = `http://127.0.0.1:${server.address().port}`;
    await page.route('**/*', route => {
      if (!route.request().url().startsWith(base)) { remoteRequests.push(route.request().url()); return route.abort(); }
      return route.continue();
    });
    await page.goto(base + '/pushbox');
    assert.equal(await page.getByRole('button', { name: 'Send notification', exact: true }).isEnabled(), true);
    assert.equal(await page.getByRole('checkbox').count(), 0);
    assert.equal(await page.getByText('Target:', { exact: false }).count(), 0);
    assert.equal(await page.getByText('Payload ready', { exact: true }).count(), 1);
    assert.equal(await page.getByRole('dialog').count(), 0);
    const contentBox = await page.getByRole('heading', { name: 'Notification content', exact: true }).boundingBox();
    const previewBox = await page.getByRole('heading', { name: 'Android preview', exact: true }).boundingBox();
    assert.ok(previewBox.x > contentBox.x && Math.abs(previewBox.y - contentBox.y) < 2);
    await page.getByRole('searchbox').fill('payment');
    assert.equal(await page.getByRole('link', { name: 'Payment received', exact: true }).isVisible(), true);
    assert.equal(await page.getByRole('link', { name: 'Order shipped', exact: true }).isVisible(), false);
    await page.getByRole('searchbox').fill('no-such-notification');
    assert.equal(await page.getByText('No matching notifications.', { exact: true }).isVisible(), true);
    await page.getByRole('searchbox').fill('BrowserOrderNotification');
    assert.equal(await page.getByRole('link', { name: 'Order shipped', exact: true }).isVisible(), true);
    await page.getByRole('searchbox').fill('');
    await page.getByRole('button', { name: 'Copy class name', exact: true }).click();
    await page.waitForFunction(() => window.copiedValues.length === 1);
    assert.match((await page.evaluate(() => window.copiedValues))[0], /BrowserOrderNotification$/);
    await page.getByRole('button', { name: 'Copy event value', exact: true }).click();
    await page.locator('#pushbox-raw-payload summary').click();
    await page.getByRole('button', { name: 'Copy JSON', exact: true }).click();
    await page.waitForFunction(() => window.copiedValues.length === 3);
    assert.equal((await page.evaluate(() => window.copiedValues))[1], 'order.shipped');
    assert.equal(JSON.parse((await page.evaluate(() => window.copiedValues))[2]).data.event, 'order.shipped');
    await page.locator('#pushbox-raw-payload summary').click();
    assert.match(await page.locator('#pushbox-preview-note').textContent(), /truncated/);
    await page.getByRole('button', { name: 'Expanded', exact: true }).click();
    assert.equal(await page.getByRole('button', { name: 'Expanded', exact: true }).getAttribute('aria-pressed'), 'true');
    assert.equal(await page.locator('.pushbox-phone').getAttribute('data-expanded'), 'true');
    assert.equal(await page.locator('[data-preview-body]').evaluate(el => el.scrollHeight <= el.clientHeight + 1), true);
    await page.getByRole('button', { name: 'Collapse notification', exact: true }).click();
    assert.equal(await page.getByRole('button', { name: 'Collapsed', exact: true }).getAttribute('aria-pressed'), 'true');
    await page.getByRole('button', { name: 'Send notification', exact: true }).click();
    assert.equal(await page.getByRole('button', { name: 'Sending…', exact: true }).isDisabled(), true);
    // A second submit while pending must not create another request.
    await page.locator('#pushbox-send-form').evaluate(form => form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true })));
    await page.getByRole('heading', { name: 'Device token rejected', exact: true }).waitFor();
    assert.deepEqual(posts, ['/pushbox/send']);
    assert.equal(new URL(page.url()).pathname, '/pushbox');
    assert.equal(await page.getByText('Firebase rejected this device token. Update it in Device settings and try again.', { exact: true }).isVisible(), true);
    assert.equal(await page.getByText('The registration token is not a valid FCM registration token', { exact: true }).isVisible(), false);
    await page.getByText('Technical details', { exact: true }).click();
    assert.equal(await page.getByText('The registration token is not a valid FCM registration token', { exact: true }).isVisible(), true);
    sendMode = 'accepted';
    await page.getByRole('button', { name: 'Send notification', exact: true }).click();
    await page.getByRole('heading', { name: 'Accepted by FCM', exact: true }).waitFor();
    sendMode = 'limited';
    await page.getByRole('button', { name: 'Send notification', exact: true }).click();
    await page.getByText('Too many send attempts. Wait a minute before trying again.', { exact: true }).waitFor();
    assert.equal(await page.getByRole('button', { name: 'Send notification', exact: true }).isEnabled(), true);
    await page.getByRole('button', { name: 'Device settings', exact: true }).click();
    assert.equal(await page.getByRole('dialog').isVisible(), true);
    await page.keyboard.press('Escape');
    assert.equal(await page.getByRole('dialog').count(), 0);
    assert.equal(await page.getByRole('button', { name: 'Device settings', exact: true }).evaluate(el => el === document.activeElement), true);
    await page.getByRole('button', { name: 'Device settings', exact: true }).click();
    await page.getByLabel('FCM test device token', { exact: true }).fill('new-device');
    await Promise.all([page.waitForNavigation(), page.getByRole('button', { name: 'Save token', exact: true }).click()]);
    assert.equal(await page.getByRole('dialog').count(), 0);
    assert.ok(posts.includes('/pushbox/device-token'));
    await page.goto(base + '/saved-device');
    await page.getByRole('button', { name: 'Device settings', exact: true }).click();
    await Promise.all([page.waitForNavigation(), page.getByRole('button', { name: 'Clear saved token', exact: true }).click()]);
    assert.ok(posts.includes('/pushbox/device-token/clear'));
    // Missing device configuration should be actionable before a send attempt.
    await page.goto(base + '/no-device');
    assert.equal(await page.getByRole('button', { name: 'Send notification', exact: true }).isDisabled(), true);
    await page.getByRole('button', { name: 'Configure device', exact: true }).click();
    assert.equal(await page.getByRole('dialog').isVisible(), true);
    await page.getByRole('button', { name: 'Cancel', exact: true }).click();
    await page.goto(base + '/pushbox');
    await page.locator('#pushbox-main').evaluate(el => { el.scrollTop = 120; });
    const beforeScroll = await page.locator('#pushbox-main').evaluate(el => el.scrollTop);
    await page.getByRole('button', { name: 'Expanded', exact: true }).click();
    await Promise.all([page.waitForNavigation(), page.getByLabel('Variant', { exact: true }).selectOption('Long message')]);
    assert.ok(Math.abs((await page.locator('#pushbox-main').evaluate(el => el.scrollTop)) - beforeScroll) < 3);
    assert.equal(await page.locator('.pushbox-phone').getAttribute('data-expanded'), 'true');
    await page.screenshot({ path: 'build/browser/workspace-desktop.png', fullPage: true });
    await page.setViewportSize({ width: 390, height: 844 });
    const mobileContent = await page.getByRole('heading', { name: 'Notification content', exact: true }).boundingBox();
    const mobilePreview = await page.getByRole('heading', { name: 'Android preview', exact: true }).boundingBox();
    assert.ok(mobilePreview.y > mobileContent.y);
    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), true);
    await page.getByRole('button', { name: 'Open menu', exact: true }).click();
    assert.equal(await page.getByRole('button', { name: 'Close menu', exact: true }).count(), 1);
    assert.equal(await page.locator('#pushbox-main').evaluate(el => el.inert), true);
    await page.keyboard.press('Escape');
    assert.equal(await page.locator('#pushbox-main').evaluate(el => el.inert), false);
    await page.screenshot({ path: 'build/browser/workspace-mobile.png', fullPage: true });
    await page.goto(base + '/empty');
    assert.equal(await page.getByRole('heading', { name: 'Register your first notification' }).count(), 1);
    assert.equal(await page.getByRole('button', { name: 'Send notification', exact: true }).count(), 0);
    assert.deepEqual(remoteRequests, []);
    assert.deepEqual(errors, []);
    assert.deepEqual(dialogs, []);
  } finally {
    if (browser) await browser.close();
    await new Promise(resolve => server.close(resolve));
  }
});
