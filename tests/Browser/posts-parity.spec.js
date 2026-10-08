import {test, expect} from '@playwright/test';

test('post list and form use the shared CRUD layout without browser errors', async ({page}) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));

    await page.goto('/admin/posts/post');
    await expect(page.locator('[data-post-list]')).toBeVisible();
    await expect(page.locator('#posts-table')).toBeVisible();
    await expect(page.locator('[data-crud-toolbar] [data-crud-action="create"]')).toBeVisible();
    await expect(page.locator('[data-table-loading]')).toBeHidden();

    await page.goto('/admin/posts/post/create');
    await expect(page.locator('.crud-taxonomy-layout.has-sidebar')).toBeVisible();
    await expect(page.locator('.crud-translation-group')).toBeVisible();
    await expect(page.locator('.post-media-field')).toBeVisible();
    await expect(page.locator('[data-crud-toolbar] button[type="submit"]')).toHaveCount(3);
    expect(errors).toEqual([]);
});
