<div align="center">

# Jobic

### Build CMS applications the Laravel way.

**A modular, developer-first CMS that keeps Laravel at the center.**

Jobic gives you the foundations of a CMS without asking you to abandon the framework, tools, and conventions you already use.

</div>

---

## Start with Laravel. Add what your project needs.

Jobic is built for developers who want CMS capabilities without turning their application into a closed platform.

Your application stays a Laravel application. Controllers are controllers. Models are Eloquent models. Views are Blade views. Validation, policies, events, queues, cache, Artisan, and Composer work the way you expect.

Jobic adds the structure needed to turn those familiar pieces into a modular CMS.

```text
Your Application
       │
       ├── Themes
       ├── Admin
       │
       └── Modules
            │
            ├── Content
            ├── Users
            ├── Media
            ├── Commerce
            └── Your Modules
                   │
                   ▼
               Jobic Core
                   │
                   ▼
                 Laravel
```

> **The idea is simple:** Jobic should feel like Laravel with CMS capabilities — not another framework living on top of Laravel.

---

## Why Jobic?

### Still Laravel

Jobic does not introduce a second application model for you to learn.

Use the Laravel tools you already know:

```text
Controllers   Eloquent      Policies      Validation
Events        Queues        Cache         Blade
Artisan       Composer      Migrations    Service Providers
```

When you need something beyond the CMS, the Laravel ecosystem is still there.

### Modular by design

A CMS tends to grow.

What starts with pages and users can eventually include products, orders, payments, reviews, reports, integrations, or entirely custom business domains.

Jobic is designed so those capabilities do not have to become one large application layer.

```text
Jobic
│
├── Core
│
├── Users
├── Media
├── Settings
│
├── Products
├── Orders
├── Payments
│
├── Reviews
└── Your Domain
```

Modules can own their routes, services, resources, data, permissions, and administrative experience while participating in the normal Laravel lifecycle.

### Built to be extended

CMS projects rarely stay exactly as they were on day one.

Jobic is designed around explicit extension points so features can contribute to the system without requiring modifications to the core.

Depending on the capability, extensions can participate through modules, Composer packages, hooks, filters, services, themes, and admin integrations.

The goal is straightforward:

```text
Install capability
       ↓
Register with Jobic
       ↓
Extend the application
       ↓
Keep the core untouched
```

### Blade first. JavaScript when it matters.

Jobic does not require the administration panel to become a full JavaScript SPA just to provide a responsive experience.

The default philosophy is simpler:

```text
              Page request
                   │
                   ▼
             Laravel + Blade
                   │
                   ▼
          Admin page is visible
                   │
            ┌──────┴──────┐
            │             │
            ▼             ▼
       Static UI      Dynamic data
                          │
                          ▼
                         AJAX
                          │
                          ▼
                       Laravel
```

Blade owns the page. Laravel owns authorization, validation, and business logic. AJAX is used where asynchronous interaction improves the experience.

When a feature genuinely needs richer client-side behavior, JavaScript can be introduced where it belongs instead of becoming a requirement for the entire CMS.

---

## An admin panel built for real data

Administrative interfaces spend a lot of time listing, searching, filtering, sorting, and editing data.

Jobic keeps those workflows backend-friendly.

A typical data-heavy screen can render its structure immediately and load records asynchronously:

```text
┌──────────────────────────────────────────────────────┐
│ Products                               + Add Product │
│                                                      │
│ Search  [____________________]    Status [ All ▼ ]   │
│                                                      │
│ ┌──────────────────────────────────────────────────┐ │
│ │ Product        Price       Status       Actions  │ │
│ ├──────────────────────────────────────────────────┤ │
│ │                                                  │ │
│ │            Loaded asynchronously                 │ │
│ │                                                  │ │
│ └──────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────┘
                         │
                         ▼
                       AJAX
                         │
                         ▼
                      Laravel
                ┌────────┼────────┐
                │        │        │
              Search   Sort   Pagination
```

Large datasets stay on the server. The browser receives the records it needs instead of becoming responsible for the entire dataset.

This keeps the frontend lightweight while leaving query optimization, authorization, filtering, and pagination where they can be controlled consistently.

---

## Packages should own their features

A reusable capability should be able to live together instead of scattering itself throughout the application.

Conceptually, a Jobic module can look like this:

```text
Module
│
├── Application code
├── Routes
├── Database
├── Configuration
├── Permissions
├── Views
├── Assets
└── Admin integration
```

That boundary matters as a project grows.

A commerce feature should not need to leak through unrelated CMS code. A review feature should not require edits throughout the product module. A project-specific domain should be able to exist without becoming part of the Jobic core.

Modules provide those boundaries while Composer provides the familiar PHP dependency and distribution model.

---

## Extend the CMS, not the core

Jobic separates the stable foundation from application-specific behavior.

```text
                       Your Project
                             │
                ┌────────────┴────────────┐
                │                         │
              Themes                    Admin
                │                         │
                └────────────┬────────────┘
                             │
                          Modules
                             │
              ┌──────────────┼──────────────┐
              │              │              │
           Module A       Module B       Module C
              │              │              │
              └──────────────┼──────────────┘
                             │
                     Extension Points
                             │
                         Jobic Core
                             │
                          Laravel
```

The core provides contracts and extension points.

Modules provide capabilities.

Projects decide how those capabilities come together.

---

## Performance without unnecessary frontend complexity

Jobic does not treat frontend architecture as a substitute for backend performance.

The foundation remains compatible with the performance techniques used in normal Laravel applications:

- server-side pagination and filtering;
- efficient database queries and indexes;
- Laravel cache and Redis;
- queues for expensive or asynchronous work;
- HTTP and asset caching;
- configurable database, session, filesystem, and queue drivers;
- stateless application patterns when horizontal scaling is needed.

For administration screens, dynamic datasets can be requested independently from the page shell. This allows pages to become usable quickly without forcing the entire admin application into a client-side state model.

Performance remains an architectural responsibility — not a framework marketing claim.

---

## A CMS that can become your application

Jobic is not limited to pages and posts.

It is intended as a foundation for applications where content management is only part of the problem:

```text
Websites
    │
    ├── Content platforms
    ├── E-commerce
    ├── Customer portals
    ├── Internal systems
    ├── Intranets
    └── Custom business applications
```

Start with the capabilities you need.

Add new domains as the project grows.

Keep using Laravel when your requirements go beyond what the CMS provides.

---

## Who is Jobic for?

Jobic is a good fit when you want:

- a CMS foundation without giving up Laravel conventions;
- modular boundaries for a growing application;
- Composer-friendly reusable capabilities;
- an extensible administration panel;
- server-rendered interfaces with selective AJAX enhancement;
- freedom to choose the frontend approach appropriate for each feature;
- application-specific business logic alongside traditional CMS functionality.

If your project needs a fixed website builder or a no-code editing platform, Jobic is probably not trying to solve the same problem.

Jobic is built primarily for developers.

---

## Getting started

Jobic is a Laravel application, so its environment and deployment model remain familiar to Laravel developers.

Clone the project:

```bash
git clone git@github.com:jobmetric/jobic.git
cd jobic
```

Install PHP dependencies:

```bash
composer install
```

Prepare the environment:

```bash
cp .env.example .env
php artisan key:generate
```

Configure your database in `.env`, then run the migrations:

```bash
php artisan migrate
```

Install and build frontend assets:

```bash
npm install
npm run build
```

Start the application:

```bash
php artisan serve
```

> Installation will become even simpler as Jobic's installer and distribution workflow evolve. The commands above intentionally follow the standard Laravel workflow.

---

## The Jobic philosophy

A CMS should give your application structure without taking ownership of your application.

```text
Laravel provides the framework.
Jobic provides the CMS foundation.
Modules provide capabilities.
Your project makes the decisions.
```

That is the direction behind Jobic.

**Build CMS applications the Laravel way.**

---

## Documentation

Documentation for Jobic's public APIs, module system, extension points, admin components, and theme development will evolve alongside the project.

Until an API is documented as public, internal implementation details should not be considered stable extension points.

---

## Contributing

Jobic is developed as an open-source project.

Bug reports, focused proposals, discussions, and pull requests are welcome. When reporting an issue, include enough environment information and reproduction steps to make the problem actionable.

---

## License

Jobic is open-source software licensed under the [MIT License](https://opensource.org/license/mit/).
