# Database seeders

The default `DatabaseSeeder` creates the bootstrap languages and the development administrator (`admin@jobic.test`) with its super-administrator role. It no longer inserts random settings. On a real installation, the installation wizard creates the administrator with the password chosen by the owner; do not rely on the development account or its default password for a public deployment.

```sh
php artisan db:seed
```

Sample content is **opt-in**. Run this only against a disposable development database after migrations have completed:

```sh
php artisan db:seed --class='Database\Seeders\Demo\DemoSeeder'
```

The demo seeder includes one scoped content-editor role (no extra login), safe SEO examples with indexing disabled, a real image in File Manager, two nested categories, two tags, one menu with two items, and a bilingual sample post and page with Editor.js documents. It also saves a three-stage **draft** workflow for posts; it does not activate that workflow. Review and activate it explicitly in the workflow designer if needed.

The sample records have internal `jobic_demo_key` markers. Re-running the seeder skips those records, preserving subsequent edits, and existing SEO settings are not overwritten. The image is placed in the normal file-manager storage tree; the post references it through the normal media relation. No SMTP credentials, public URLs, passwords for extra accounts, or external media are seeded.

The demo seeder does not erase existing data, but its sample post is published immediately when there is no active advanced workflow. Do not run it on production, staging, or any database with valuable content. For a fresh disposable development database, `migrate:fresh --seed` is allowed only after confirming the connection and database name; it is never an upgrade path for an existing deployment.
