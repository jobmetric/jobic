# JobMetric package development

JobMetric packages are developed in sibling repositories under `../maral/packages/`. Application-specific integration belongs in Jobic; defects in the package belong in the package repository. Never repair installed vendor files with application scripts or Composer lifecycle hooks.

## Release workflow

1. Locate the package repository and read its contribution rules. Inspect uncommitted changes, the current branch, remote HEAD and existing tags.
2. Make the smallest compatible fix in package source. Preserve unrelated work and add regression coverage where behavior needs it. Check supported PHP/framework versions and dependency requirements.
3. Test the package and its Jobic integration. Record the release in the package changelog. Commit only the intended changes.
4. When publication is authorized, push the commit and a new semantic-version tag. Never overwrite an existing tag. Verify that Packagist exposes the new stable release.
5. Upgrade the application's Composer constraint and lock using Composer. A direct requirement may establish a minimum fixed version of an otherwise transitive dependency. Do not hand-edit lock references or leave local path repositories in deployment configuration.
6. Remove superseded repair hooks/scripts and update the integration documentation. Verify package discovery, dependency validation and the affected application tests with the installed release.

## Compatibility releases used by Jobic

| Package | Source | Minimum version | Fix |
| --- | --- | --- | --- |
| `jobmetric/domi` | `../maral/packages/domi` | `1.14.1` | PackageCore EnumMacros compatibility and explicit nullable service/helper parameters |
| `jobmetric/laravel-translation` | `../maral/packages/laravel-translation` | `3.9.1` | Explicit nullable locale in translation helpers |

The application keeps its documented Domi view override for CMS presentation. That supported view customization is separate from repairing package PHP source.
