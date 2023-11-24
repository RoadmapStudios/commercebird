import {expect, test} from '@playwright/test';

test.beforeEach('Login to wordpress', async ({page}, testInfo) => {
    await page.goto('https://roadmapstudio.dev/wp-login.php?redirect_to=https%3A%2F%2Froadmapstudio.dev%2Fwp-admin%2Fadmin.php%3Fpage%3Dzoho-inventory-admin&reauth=1');
    await page.getByLabel('Username or Email Address').click();
    await page.getByLabel('Username or Email Address').fill('admin');
    await page.getByLabel('Password', {exact: true}).click();
    await page.getByLabel('Password', {exact: true}).fill('pass');
    await page.getByLabel('Password', {exact: true}).press('Enter');
    await expect(page).toHaveURL('https://roadmapstudio.dev/wp-admin/admin.php?page=zoho-inventory-admin');
    await page.locator('#zoho-inventory-admin').getByRole('link', {name: 'Zoho Inventory'}).click();
    await page.getByText('ZohoInventory').waitFor({state: 'visible'});
    await page.locator('#zoho-inventory-admin').getByRole('link', {name: 'Zoho Inventory'}).click();
    await page.locator('.divide-y > .relative > .absolute').waitFor({state: 'hidden'})
});
test('test_tax_save_and_load', async ({page}) => {
    await page.getByRole('button', { name: 'Tax' }).click();
    await page.getByText('Enable Decimal Tax').click();
    await page.locator('div').filter({ hasText: /^US ALPlease select onebasicproultimate$/ }).getByRole('combobox').selectOption('5^^4586309000000095036##basic##tax##10');
    await page.locator('div:nth-child(5) > .col-span-3 > .relative > .mt-1').selectOption('2^^4586309000000095036##basic##tax##10');
    await page.locator('div:nth-child(6) > .col-span-3 > .relative > .mt-1').selectOption('3^^4586309000000095036##basic##tax##10');
    await page.locator('div').filter({ hasText: /^Vat ExemptPlease select onebasicproultimate$/ }).getByRole('combobox').selectOption('4586309000000095036');
    await page.getByText('Save Reset').click();
    await page.getByRole('button', { name: 'Save' }).click();
    await page.getByRole('link', { name: 'Checkout Form' }).click();
    await page.getByRole('link', { name: 'Zoho Inventory', exact: true }).click();
    await page.locator('#zoho-inventory-admin').getByRole('link', { name: 'Zoho Inventory' }).click();
    await page.getByRole('button', { name: 'Tax' }).click();
    await page.locator('.divide-y > .relative > .absolute').waitFor({state: 'hidden'})
    await expect(await page.locator('.mt-1').first()).toContainText('basic');
});

test('test_tax_reset_and_load', async ({page}) => {
    await page.getByRole('button', {name: 'Reset'}).click();
    
})


