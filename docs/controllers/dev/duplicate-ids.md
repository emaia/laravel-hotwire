# Duplicate IDs

Development-only diagnostic that warns when automatic Laravel Hotwire component IDs are duplicated inside one render
root.

**Identifier:** `dev--duplicate-ids`
**Loaded by:** auto-loaded after `php artisan hotwire:install`; publish only to customize with
`php artisan hotwire:controllers dev/duplicate-ids`.

## Requirements

- No external dependencies.

## Usage

Mount the controller on the application root in development:

```blade
<body @env('local') data-controller="dev--duplicate-ids" @endenv>
    ...
</body>
```

The controller checks existing nodes and observes later insertions and ID changes. It warns once while each duplicate is
present and warns again if the collision is removed and later reintroduced.

The diagnostic recognizes the reserved automatic-ID shape `hw-<prefix>-(page|frame-<id>)-<ordinal>`. Explicit IDs that
deliberately imitate that package shape are also reported; application IDs should use a different format.
