# Tabs

Composable Blade primitives for the [`tabs`](../controllers/tabs.md) Stimulus controller. They render tab roles, wire
controller targets/actions, and can server-render the active tab for progressive enhancement.

## Usage

```blade
<hw:tabs id="settings" active="profile">
    <hw:tabs.list aria-label="Settings">
        <hw:tabs.trigger value="profile">Profile</hw:tabs.trigger>
        <hw:tabs.trigger value="billing">Billing</hw:tabs.trigger>
    </hw:tabs.list>

    <hw:tabs.panel value="profile">
        Profile settings…
    </hw:tabs.panel>

    <hw:tabs.panel value="billing">
        Billing settings…
    </hw:tabs.panel>
</hw:tabs>
```

## Components

| Component | Element | Description |
| --- | --- | --- |
| `tabs` | `div` | Root element with `data-controller="tabs"`. |
| `tabs.list` | `div` | `role="tablist"` wrapper with delegated click and keyboard actions. |
| `tabs.trigger` | `button` | `role="tab"` button matched to a panel by `value`. |
| `tabs.panel` | `div` | `role="tabpanel"` matched to a trigger by `value`. |

## Props

| Component | Prop | Default | Description |
| --- | --- | --- | --- |
| `tabs` | `id` | generated | Base id used to derive trigger and panel ids. |
| `tabs` | `active` | `null` | Value of the tab to render selected on the server. |
| `tabs` | `selectedIndex` | `null` | Zero-based fallback passed to the controller as `selected-index`. |
| `tabs` | `controller` | `tabs` | Stimulus identifier, useful for subclasses. |
| `tabs` | `orientation` | `horizontal` | Set `vertical` for a vertical tab layout and Up/Down keyboard navigation. |
| `tabs` | `stimulus` | `null` | Optional extra Stimulus binding merged into the root element. |
| `tabs.list` | `variant` | `default` | Use `line` for an underline-style tab list. |
| `tabs.list` | `stimulus` | `null` | Optional extra Stimulus binding merged into the list element. |
| `tabs.trigger` | `value` | required | Stable value paired with a panel. |
| `tabs.trigger` | `disabled` | `false` | Disables the tab trigger and removes it from controller navigation. |
| `tabs.trigger` | `stimulus` | `null` | Optional extra Stimulus binding merged into the trigger element. |
| `tabs.panel` | `value` | required | Stable value paired with a trigger. |
| `tabs.panel` | `stimulus` | `null` | Optional extra Stimulus binding merged into the panel element. |

## Active tab

Pass `active` when the server knows the current tab. The matching trigger gets `aria-selected="true"` and `tabindex="0"`;
other triggers get `aria-selected="false"` and `tabindex="-1"`. Matching panels render visible, and inactive panels render
with `hidden`.

```blade
@php
    $active = $errors->hasAny(['card_number', 'billing_address']) ? 'billing' : 'profile';
@endphp

<hw:tabs id="account" :active="$active">
    …
</hw:tabs>
```

If `active` is omitted, the markup stays minimally opinionated and the controller selects the initial tab on connect.

## Line variant

```blade
<hw:tabs id="project" active="preview">
    <hw:tabs.list variant="line" aria-label="Project">
        <hw:tabs.trigger value="preview">Preview</hw:tabs.trigger>
        <hw:tabs.trigger value="code">Code</hw:tabs.trigger>
    </hw:tabs.list>

    <hw:tabs.panel value="preview">Preview content…</hw:tabs.panel>
    <hw:tabs.panel value="code">Code content…</hw:tabs.panel>
</hw:tabs>
```

## Disabled tabs

```blade
<hw:tabs id="account" active="profile">
    <hw:tabs.list aria-label="Account">
        <hw:tabs.trigger value="profile">Profile</hw:tabs.trigger>
        <hw:tabs.trigger value="billing" disabled>Billing</hw:tabs.trigger>
    </hw:tabs.list>

    <hw:tabs.panel value="profile">Profile settings…</hw:tabs.panel>
    <hw:tabs.panel value="billing">Billing settings…</hw:tabs.panel>
</hw:tabs>
```

Disabled triggers render `disabled` and `aria-disabled="true"` and are skipped by keyboard navigation.

## Icons

```blade
<hw:tabs id="project" active="preview">
    <hw:tabs.list aria-label="Project">
        <hw:tabs.trigger value="preview">
            <hw:icon name="app-window" />
            Preview
        </hw:tabs.trigger>
        <hw:tabs.trigger value="code">
            <hw:icon name="code" />
            Code
        </hw:tabs.trigger>
    </hw:tabs.list>

    <hw:tabs.panel value="preview">Preview content…</hw:tabs.panel>
    <hw:tabs.panel value="code">Code content…</hw:tabs.panel>
</hw:tabs>
```

## Vertical orientation

```blade
<hw:tabs id="settings" active="general" orientation="vertical">
    <hw:tabs.list aria-label="Settings">
        <hw:tabs.trigger value="general">General</hw:tabs.trigger>
        <hw:tabs.trigger value="security">Security</hw:tabs.trigger>
    </hw:tabs.list>

    <hw:tabs.panel value="general">General settings…</hw:tabs.panel>
    <hw:tabs.panel value="security">Security settings…</hw:tabs.panel>
</hw:tabs>
```

Vertical tab lists use Arrow Up/Down through the underlying controller.

## Custom ids

By default, `value="profile"` inside `<hw:tabs id="settings">` renders `settings-tab-profile` and
`settings-panel-profile`. Override ids only when you also pass the matching ARIA attributes:

```blade
<hw:tabs.trigger value="profile" id="profile-tab" aria-controls="profile-panel">Profile</hw:tabs.trigger>
<hw:tabs.panel value="profile" id="profile-panel" aria-labelledby="profile-tab">Profile settings…</hw:tabs.panel>
```

For URL syncing or analytics, listen to the `tabs:change` event documented in the controller docs.

```blade
<hw:tabs
    id="settings"
    :active="request()->query('tab')"
    :stimulus="stimulus()->controller('tab-url')->action('tab-url', 'update', 'tabs:change')"
>
    <hw:tabs.list variant="line" aria-label="Settings">
        <hw:tabs.trigger value="profile" data-tab-name="profile">Profile</hw:tabs.trigger>
        <hw:tabs.trigger value="billing" data-tab-name="billing">Billing</hw:tabs.trigger>
    </hw:tabs.list>

    ...
</hw:tabs>
```

Regular `data-controller` / `data-action` attributes and the `stimulus` prop are merged and deduplicated. Internal
`data-tabs-*` attributes remain component-owned; use the component props instead of overriding them directly.
