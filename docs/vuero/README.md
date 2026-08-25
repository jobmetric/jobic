# Vuero demo

The current administration UI is an isolated Blade and jQuery conversion of the Vuero demo. It is not the Jobic CMS administration implementation.

## URLs

- Demo entry: `/vuero`
- Login: `/vuero/login`
- Dashboard: `/vuero/admin`
- Dashboard pages: `/vuero/admin/dashboards/{dashboard}`
- Profile settings: `/vuero/admin/profile-settings`

## Source boundaries

- PHP controllers and requests: `app/Http/{Controllers,Requests}/Vuero`
- Routes: `routes/vuero.php`
- Configuration: `config/vuero.php`
- Views: `resources/views/vuero`
- Blade components: `resources/views/components/vuero`
- Styles: `resources/css/vuero`
- Scripts: `resources/js/vuero`
- Public assets: `public/vuero`
- Feature tests: `tests/Feature/Vuero`

Keep future Jobic CMS code outside these paths so the demo can be removed or replaced without affecting the application core.
