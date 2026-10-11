import {test, expect} from '@playwright/test';

test('post list and form use the shared CRUD layout without browser errors', async ({page}) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));

    await page.goto('/admin/posts/post');
    await expect(page.locator('[data-post-list]')).toBeVisible();
    await expect(page.locator('#posts-table')).toBeVisible();
    await expect(page.locator('[data-crud-toolbar] [data-crud-action="create"]')).toBeVisible();
    const operations = page.locator('[data-crud-toolbar] .post-list-operations');
    await expect(operations.locator('summary')).toBeVisible();
    await expect(operations.locator('[data-post-action]').first()).toHaveAttribute('data-crud-action', 'activate');
    await expect(operations.locator('[data-post-action="help"]')).toHaveCount(1);
    await expect(operations.locator('[data-post-action] svg')).toHaveCount(0);
    await expect(page.locator('[data-table-loading]')).toBeHidden();

    await page.goto('/admin/posts/post/create');
    await expect(page.locator('.crud-taxonomy-layout.has-sidebar')).toBeVisible();
    await expect(page.locator('.crud-translation-group')).toBeVisible();
    await expect(page.locator('.post-media-field')).toBeVisible();
    await expect(page.locator('[data-crud-toolbar] button[type="submit"]')).toHaveCount(3);

    const categories = page.locator('[data-taxonomy-quick-add][data-collection="categories"]');
    await expect(categories).toHaveAttribute('data-multiple', 'true');
    await categories.locator('[data-taxonomy-add-open]').click();
    await expect(categories.locator('[data-taxonomy-dialog]')).toBeVisible();
    await expect(categories.locator('[data-taxonomy-parent]')).toHaveCount(1);
    await categories.locator('[data-taxonomy-dialog-close]').last().click();

    const tags = page.locator('[data-taxonomy-quick-add][data-collection="tags"]');
    await expect(tags).toHaveAttribute('data-hierarchical', 'false');
    await tags.locator('[data-taxonomy-add-open]').click();
    await expect(tags.locator('[data-taxonomy-dialog]')).toBeVisible();
    await expect(tags.locator('[data-taxonomy-parent]')).toHaveCount(0);
    await tags.locator('[data-taxonomy-dialog-close]').last().click();
    expect(errors).toEqual([]);
});
