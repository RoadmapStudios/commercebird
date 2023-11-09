import {expect, test} from '@playwright/test';

test.beforeEach('Login to wordpress', async ({page}, testInfo) => {
    await page.goto('https://roadmapstudio.dev/wp-login.php?redirect_to=https%3A%2F%2Froadmapstudio.dev%2Fwp-admin%2Fadmin.php%3Fpage%3Dzoho-inventory-admin&reauth=1');
    await page.getByLabel('Username or Email Address').click();
    await page.getByLabel('Username or Email Address').fill('admin');
    await page.getByLabel('Password', {exact: true}).click();
    await page.getByLabel('Password', {exact: true}).fill('pass');
    await page.getByLabel('Password', {exact: true}).press('Enter');
    await expect(page).toHaveURL('https://roadmapstudio.dev/wp-admin/admin.php?page=zoho-inventory-admin');
});
test('test_connect_save_and_load', async ({page}) => {
    await page.locator('#zoho-inventory-admin').getByRole('link', {name: 'Zoho Inventory'}).click();
    await page.getByText('ZohoInventory').waitFor({state: 'visible'});
    await page.locator('#zoho-inventory-admin').getByRole('link', {name: 'Zoho Inventory'}).click();
    await page.locator('div').filter({hasText: /^Organization ID$/}).getByRole('textbox').click();
    await page.locator('div').filter({hasText: /^Organization ID$/}).getByRole('textbox').fill('833886385');
    await page.locator('div').filter({hasText: /^Client ID$/}).getByRole('textbox').click();
    await page.locator('div').filter({hasText: /^Client ID$/}).getByRole('textbox').fill('1000.YYVMUP2DN8I6SYE4TAVEB3YJDBQNNF');
    await page.locator('div').filter({hasText: /^Client Secret$/}).getByRole('textbox').click();
    await page.locator('div').filter({hasText: /^Client Secret$/}).getByRole('textbox').fill('f008e26c2a9fb5d069bea9942f83cab18336fbb828');
    await page.getByRole('combobox').selectOption('com');
    await page.getByRole('button', {name: 'Save'}).click();
    await expect(page.getByText('Sign in', {exact: true})).toBeVisible();
});

test('test_subscribe_reset_and_load', async ({page}) => {
    await page.getByRole('button', {name: 'Reset'}).click();
    await expect(page.getByRole('link', {name: 'Subscribe for Live Notifications, Fastest IOS/Android App, Staff Members, Integrations and more'})).toBeVisible();
    await expect(page.getByRole('textbox')).toBeEmpty();
    await page.locator('#zoho-inventory-admin').getByRole('link', {name: 'Zoho Inventory'}).click();
    await expect(page.locator('.max-w-screen-xl > div > .absolute')).toBeVisible()
    await page.locator('.max-w-screen-xl > div > .absolute').click();
})


