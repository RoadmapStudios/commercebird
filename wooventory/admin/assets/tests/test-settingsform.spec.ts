import {expect, test} from '@playwright/test';

test.beforeEach('Login to wordpress', async ({page}, testInfo) => {
    await page.goto('https://roadmapstudio.dev/wp-login.php?redirect_to=https%3A%2F%2Froadmapstudio.dev%2Fwp-admin%2Fadmin.php%3Fpage%3Dzoho-inventory-admin&reauth=1');
    await page.getByLabel('Username or Email Address').fill('admin');
    await page.getByLabel('Password', {exact: true}).fill('pass');
    await page.getByLabel('Password', {exact: true}).press('Enter');
    await expect(page).toHaveURL('https://roadmapstudio.dev/wp-admin/admin.php?page=zoho-inventory-admin');
});
test('test_subscribe_save_and_load', async ({page}) => {
    await page.locator('#zoho-inventory-admin').getByRole('link', {name: 'Zoho Inventory'}).click();
    await expect(page.locator('.max-w-screen-xl > div > .absolute')).toBeVisible();
    await page.getByRole('link', {name: 'Welcome'}).click();
    await page.getByRole('textbox').click();
    await page.getByRole('textbox').fill('23');
    await page.getByRole('button', {name: 'Save'}).click();
    await expect(page.locator('div:nth-child(2) > div > .flex > .flex-shrink-0')).toBeVisible();
    await expect(page.getByText('Activated Integrations')).toBeVisible();
    await page.getByLabel('Main menu', {exact: true}).getByRole('link', {name: 'Zoho Inventory', exact: true}).click();
    await expect(page).toHaveURL('https://roadmapstudio.dev/wp-admin/admin.php?page=zoho-inventory');
    await page.getByLabel('Main menu', {exact: true}).getByRole('link', {name: 'Zoho Inventory Dev', exact: true}).click();
    await expect(page).toHaveURL('https://roadmapstudio.dev/wp-admin/admin.php?page=zoho-inventory-admin');
    await expect(page.getByText('Activated Integrations')).toBeVisible();
    await page.locator('#zoho-inventory-admin').getByRole('link', {name: 'Zoho Inventory'}).click();
    await page.getByRole('button', {name: 'Tax'}).click();
    await page.getByRole('button', {name: 'Connect'}).click();
    await expect(page.getByText('Organization ID')).toBeVisible();
});

test('test_route_guard', async ({page}) => {
    await page.locator('#zoho-inventory-admin').getByRole('link', {name: 'Zoho Inventory'}).click();
    await expect(page.getByRole('heading', {name: 'Welcome'})).toBeVisible();
    await page.getByRole('textbox').click();
    await page.getByRole('textbox').fill('23');
    await page.getByRole('button', {name: 'Save'}).click();
    await page.getByText('Activated Integrations').waitFor({state: 'visible'});
    await expect(page.getByText('Activated Integrations')).toBeVisible();
    await page.locator('#zoho-inventory-admin').getByRole('link', {name: 'Zoho Inventory'}).click();
    await expect(page.getByRole('heading', {name: 'Zoho Inventory'})).toBeVisible();
    await page.locator('#zoho-inventory-admin').getByRole('link', {name: 'Welcome'}).click();
    await page.getByRole('button', {name: 'Reset'}).click();
    await page.getByRole('button', {name: 'Reset'}).waitFor({state: 'visible'});
    await page.locator('#zoho-inventory-admin').getByRole('link', {name: 'Zoho Inventory'}).click();
    await page.locator('#zoho-inventory-admin').getByRole('link', {name: 'Zoho Inventory'}).click();
    await page.locator('#zoho-inventory-admin').getByRole('link', {name: 'Zoho Inventory'}).click();
    await expect(page.getByRole('heading', {name: 'Welcome'})).toBeVisible();
})

test('test_subscribe_reset_and_load', async ({page}) => {
    await page.getByRole('button', {name: 'Reset'}).click();
    await expect(page.getByRole('link', {name: 'Subscribe for Live Notifications, Fastest IOS/Android App, Staff Members, Integrations and more'})).toBeVisible();
    await expect(page.getByRole('textbox')).toBeEmpty();
    await page.locator('#zoho-inventory-admin').getByRole('link', {name: 'Zoho Inventory'}).click();
    await expect(page.locator('.max-w-screen-xl > div > .absolute')).toBeVisible()
    await page.locator('.max-w-screen-xl > div > .absolute').click();
})


