# Artisan Commands

Laravel Hotwire ships Artisan commands for installation, controller authoring, CSS generation, validation and package
discovery. This page is the complete command-line API for the installed package version.

The tables list command-specific arguments and options. Standard Artisan options such as `--help`, `--quiet`,
`--verbose`, `--ansi`, `--no-ansi`, `--no-interaction` and `--env` remain available on every command. Array options can
be repeated; where noted, they also accept comma-separated values.

## `hotwire:install`

Scaffold the JavaScript and CSS integration, update frontend dependencies and configure controller discovery.

```bash
php artisan hotwire:install [--force] [--only=js|css] [--with-deps=CONTROLLER] [--core-only] [--preset=nova] [--skip-install] [--fix]
```

This command has no command-specific arguments.

| Option           | Default | Description                                                                                                          |
|------------------|---------|----------------------------------------------------------------------------------------------------------------------|
| `--force`        | `false` | Replace scaffold files that differ from the package stubs.                                                           |
| `--only=`        | `null`  | Install only `js` or `css`; omit it to install both.                                                                 |
| `--with-deps=*`  | `[]`    | Add optional npm dependencies only for the listed controllers. Repeat the option or use comma-separated identifiers. |
| `--core-only`    | `false` | Add Stimulus, Turbo and the lazy loader without optional controller dependencies.                                    |
| `--preset=`      | `nova`  | Import the named shipped preset into `resources/css/app.css`.                                                        |
| `--skip-install` | `false` | Update `package.json` without running the detected package manager.                                                  |
| `--fix`          | `false` | Forward `--fix` to the post-install `hotwire:check` for selective JavaScript installs.                               |

By default, the installer copies the JavaScript and CSS scaffolding, adds core and all catalog npm dependencies,
configures the `@hotwire` Vite alias, generates the controller loader and updates `ide.json`. It runs the detected
package manager only when it added dependencies.

`--core-only` and `--with-deps` are mutually exclusive. `--only=css` cannot be combined with `--fix`. A selective
JavaScript install made with `--core-only` or `--with-deps` finishes by running `hotwire:check`; a default install does
not need that extra drift check. Existing files prompt before replacement in an interactive terminal, are skipped in
non-interactive mode and can be replaced with `--force`.

See [Advanced installation](installation.md) for dependency modes, loader policy and CI examples.

## `hotwire:make-controller`

Create an application-owned Stimulus controller under `resources/js/controllers`.

```bash
php artisan hotwire:make-controller form/autosave [--ts] [--force]
```

| Argument | Default    | Description                                                               |
|----------|------------|---------------------------------------------------------------------------|
| `name`   | `required` | Lowercase namespace/name, such as `form/autosave` or `modal/close_modal`. |

| Option    | Default | Description                                                                         |
|-----------|---------|-------------------------------------------------------------------------------------|
| `--ts`    | `false` | Generate TypeScript instead of JavaScript.                                          |
| `--force` | `false` | Replace an existing file or deliberately use an identifier reserved by the package. |

The name must contain at least one namespace separator. For example, `form/auto-save` generates
`resources/js/controllers/form/auto_save_controller.js` with the Stimulus identifier `form--auto-save`.

Interactive mode asks for the language and can scaffold targets, values and CSS classes. With `--no-interaction`, the
command creates a basic JavaScript controller unless `--ts` is present. Generated controllers do not carry the package
ownership marker, so package update commands treat them as user-owned files.

## `hotwire:make-preset`

Create an application-owned CSS preset scaffold or clone a shipped preset.

```bash
php artisan hotwire:make-preset brand [--from=nova] [--force]
```

| Argument | Default    | Description                                                                                         |
|----------|------------|-----------------------------------------------------------------------------------------------------|
| `name`   | `required` | Preset name beginning with a lowercase letter and containing lowercase letters, numbers or hyphens. |

| Option    | Default | Description                                                                     |
|-----------|---------|---------------------------------------------------------------------------------|
| `--from=` | `null`  | Clone the named shipped preset instead of generating a blank complete scaffold. |
| `--force` | `false` | Replace the existing application preset.                                        |

The output is `resources/css/presets/<name>.css`. Without `--from`, the scaffold includes the complete visual slot and
token contract known to the registry. With `--from`, the shipped preset is flattened into an application-owned snapshot.
The command does not edit `resources/css/app.css`; it prints the import to add after writing the file.

`--force` replaces application customizations, so compare the existing file before using it. See [Presets](presets.md)
for authoring, cloning and upgrade guidance.

## `hotwire:bundle-preset`

Generate or regenerate a selective CSS bundle for an explicit set of components.

```bash
php artisan hotwire:bundle-preset --components=button,input [--include=tooltip] [--preset=nova] [--output=resources/css/hotwire.css] [--force]
php artisan hotwire:bundle-preset --from=resources/css/hotwire.css --force
```

This command has no command-specific arguments.

| Option           | Default                     | Description                                                                                                 |
|------------------|-----------------------------|-------------------------------------------------------------------------------------------------------------|
| `--preset=`      | `nova`                      | Use the named shipped preset.                                                                               |
| `--components=*` | `[]`                        | Include component keys. Repeat the option or use comma-separated keys.                                      |
| `--include=*`    | `[]`                        | Include additional component keys or controller identifiers. Repeat the option or use comma-separated keys. |
| `--output=`      | `resources/css/hotwire.css` | Write under `resources/css` at this application-relative `.css` path.                                       |
| `--from=`        | `null`                      | Regenerate an existing generated bundle in place from its recorded component selection.                    |
| `--force`        | `false`                     | Replace an existing bundle generated by this command.                                                       |

At least one selection is required for a new bundle. Component selections pull in their transitive visual dependencies.
Package controllers do not own visual modules and are never recorded in new plans. `--include` resolves a matching
component first, so identifiers such as `tooltip` select that component's package CSS. A controller-only identifier is
accepted for command compatibility, including slash notation such as `turbo/progress`, but does not select package CSS;
select the corresponding component when package styling is required.

`--from` reads `preset` and `components` from an existing bundle, recalculates its module closure against the current
package and writes back to the same path. It cannot be combined with `--preset`, `--components`, `--include` or `--output`.
Use `--force` when regeneration changes bytes. A readable v1 plan can be migrated manually this way.

The output must be a non-hidden `.css` file under `resources/css`; absolute paths, traversal and symlink escapes are
rejected. The generated marker and regeneration plan identify command-owned bundles. Even with `--force`, the command
never replaces a file that it cannot identify as one of its generated bundles. An identical bundle is reported as up to
date without rewriting it. New v2 plans include a normalized SHA-256 hash so `hotwire:check` can distinguish package
updates from application edits; CRLF-only checkout changes do not count as drift.

See [Presets](presets.md#generate-a-selective-bundle) for selection and import examples.

## `hotwire:controllers`

List or publish package Stimulus controllers for application customization.

```bash
php artisan hotwire:controllers [controllers...] [--all] [--outdated] [--force] [--list]
```

| Argument        | Default | Description                                                                                                |
|-----------------|---------|------------------------------------------------------------------------------------------------------------|
| `controllers?*` | `[]`    | Optional controller names, a substrate such as `turbo`, or substrate/name values such as `turbo/progress`. |

| Option       | Default | Description                                                                 |
|--------------|---------|-----------------------------------------------------------------------------|
| `--all`      | `false` | Publish every package controller.                                           |
| `--outdated` | `false` | Update published package-owned controllers that differ from package source. |
| `--force`    | `false` | Replace differing package-owned files without prompting.                    |
| `--list`     | `false` | Print the available controllers and local publication status.               |

With no selection, the command opens a multiselect in an interactive terminal and prints the list in non-interactive
mode. Publishing also copies shared package helpers imported by the selected controllers.

Package controllers already auto-load from `vendor`; publishing is optional and intended only for source customization.
The package marker is an explicit opt-in to future updates. A user-owned file without that marker is never overwritten,
even with `--force`. `--outdated` only considers already-published package-owned files and their shared dependencies.

When selection modes are combined, `--all` takes precedence over `--outdated`, and `--outdated` takes precedence over
explicit controller arguments. Prefer one mode per invocation so the intent remains clear. Unknown controller names are
reported as warnings without making the command fail.

See [Extending controllers](extending-controllers.md) before publishing a controller.

## `hotwire:components`

List every registered Blade component and its Stimulus dependencies.

```bash
php artisan hotwire:components
```

This command has no command-specific arguments or options.

The output includes the component name, configured Blade tag, controller identifier and local publication status. The
status is about customization files under `resources/js/controllers`, not controller availability:

- `not published` means the package controller will load directly from `vendor`;
- `up to date` means the local published copy matches package source;
- `outdated` means the local copy differs from package source.

Use `hotwire:docs --list --component` when you need the component catalog grouped by category rather than publication
status.

## `hotwire:check`

Validate controller loading, frontend dependencies, generated CSS and application preset contracts.

```bash
php artisan hotwire:check [--path=PATH] [--preset=PRESET] [--fix] [--skip-install]
```

This command has no command-specific arguments.

| Option           | Default | Description                                                                                        |
|------------------|---------|----------------------------------------------------------------------------------------------------|
| `--path=*`       | `[]`    | Scan these locations for Blade files; the effective default is `resources/views`.                  |
| `--preset=*`     | `[]`    | Validate application preset names or paths in addition to presets imported by the application CSS. |
| `--fix`          | `false` | Apply safe package-owned fixes without prompting.                                                  |
| `--skip-install` | `false` | Do not run the detected package manager after `--fix` adds dependencies.                           |

The check scans Blade components, `data-controller` attributes and Stimulus helper usage. It verifies package controller
and shared-helper drift, npm dependencies, controller loader policy, selective bundle integrity and coverage, and
application preset contracts. Repeat `--path` or `--preset` to supply more than one value.

Missing local controller files are valid because package controllers auto-load from `vendor`. A differing user-owned
file is reported but not overwritten. CSS selection and application preset problems require manual changes. An untouched
v2 selective bundle whose package sources changed is safe to regenerate automatically; an externally edited bundle is
reported with a diff and left alone.

In an interactive terminal, the command can offer safe fixes. `--fix` publishes or updates package-owned controller
files, regenerates supported loader metadata and untouched v2 selective bundles, and adds missing npm dependencies
without prompting. It never rewrites edited bundles, v1 plans, invalid metadata or missing presets. Safe fixes still run
when another issue needs manual work, and the command keeps a failing exit code until every issue is resolved. When
dependencies are added, the package manager runs unless `--skip-install` is present. Run the application's production
asset build separately; this command validates configuration and contracts, not compiled assets.

See [Advanced installation](installation.md#ci--automation-recipe) for CI usage and [Presets](presets.md) for CSS
validation.

## `hotwire:docs`

Search, list or render component and controller documentation in the terminal.

```bash
php artisan hotwire:docs [name] [--list] [--pager|--no-pager] [--controller|--component]
```

| Argument | Default | Description                                                                                   |
|----------|---------|-----------------------------------------------------------------------------------------------|
| `name?`  | `null`  | Optional component key or controller identifier; substrate controllers accept slash notation. |

| Option         | Default | Description                                                     |
|----------------|---------|-----------------------------------------------------------------|
| `--list`       | `false` | List catalog documentation entries instead of rendering one.    |
| `--pager`      | `false` | Force an attempt to render through an available terminal pager. |
| `--no-pager`   | `false` | Print directly without a pager.                                 |
| `--controller` | `false` | Search or list only controller entries.                         |
| `--component`  | `false` | Search or list only component entries.                          |

With no name, interactive mode opens catalog search. Non-interactive mode requires a name or `--list`. If a name exists
as both a component and controller, select its type with `--component` or `--controller` in non-interactive usage.

`--controller` and `--component` are mutually exclusive, as are `--pager` and `--no-pager`. `--list` cannot be combined
with a name. Without an explicit pager option, output uses an available pager only on an interactive TTY.

This command searches registry-backed component and controller pages. General guides, including this command API, remain
available through the main documentation index.

## `hotwire:ide-json`

Generate or merge Laravel Idea metadata for Stimulus controller discovery.

```bash
php artisan hotwire:ide-json
```

This command has no command-specific arguments or options.

The command discovers application `.js` and `.ts` controllers, combines their locations with package controller
locations and writes the root `ide.json`. Application controllers override package locations with the same Stimulus
identifier. Package Blade component metadata is supplied separately by the Composer package, so this command does not
duplicate those entries in `ide.json`.

The installer runs this command automatically after JavaScript setup.
See [Advanced installation](installation.md#laravel-idea-metadata)
for the resulting metadata layout.
