# Application PHPDoc conventions

The reference is `App\Services\Auth\Authentication`. These conventions apply to every PHP class and named method in `app/`, including controllers, requests, services, facades, models, middleware, commands, providers and support classes. Private and protected methods are included.

## Required structure

Use a concise English summary that explains the operation. Preserve useful detail about side effects, constraints and failure behavior.

```php
/**
 * Login user with email and password.
 *
 * @param array<string, mixed> $data
 * @param array<string, mixed> $context
 *
 * @return Response
 * @throws Throwable
 */
```

This example reflects the Auth service; `Throwable` is not a mandatory tag for every method. Document exceptions only when they can escape the method, including deliberate propagation from dependencies. Do not list internally caught exceptions as escaping failures.

- List every parameter once, in signature order, with its exact name and an accurate type. Add descriptions when the meaning is not obvious.
- Leave a blank PHPDoc line between the summary and tags, and between parameters and the return tag. If there are no parameters, put `@return` directly after the summary separator.
- Include `@return`, including `void` for constructors and void methods. Put `@throws` immediately after the return tag.
- Express nullable types as `Type|null` and keep union types consistent with the signature. PHPDoc may refine native types but must not contradict them.
- Describe arrays with key/value generics, `list<Type>` or array shapes. Prefer precise element types when established by the code; use `mixed` only for genuinely variable values.
- Give each class a short responsibility summary. Preserve facade `@method` declarations, model annotations, trait `@use` annotations and useful property `@var` types.
- Use imported type names when available, otherwise fully qualified names. Do not add runtime imports or change method signatures solely to simplify documentation.

## Maintenance and verification

Update documentation alongside changes to parameters, returned values, validation, side effects and propagated errors. Avoid copied summaries that describe a different operation. Never include real secrets in examples.

For documentation-only updates, check PHP syntax and compare executable tokens with comments and whitespace excluded. This proves that documentation changes did not change runtime code. Review coverage and type accuracy separately; token equality does not verify the truth of a comment.

Codex follows this requirement through `AGENTS.md`; Cursor follows `.cursor/rules/phpdoc.mdc` for `app/**/*.php`.
