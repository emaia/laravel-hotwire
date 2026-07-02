# Table

Responsive table primitives that keep markup semantic while exposing stable `data-slot` styling hooks.

## Usage

```blade
<x-hwc::table>
    <x-hwc::table.caption>Recent invoices</x-hwc::table.caption>
    <x-hwc::table.header>
        <x-hwc::table.row>
            <x-hwc::table.head>Invoice</x-hwc::table.head>
            <x-hwc::table.head>Status</x-hwc::table.head>
        </x-hwc::table.row>
    </x-hwc::table.header>
    <x-hwc::table.body>
        <x-hwc::table.row>
            <x-hwc::table.cell>INV001</x-hwc::table.cell>
            <x-hwc::table.cell>Paid</x-hwc::table.cell>
        </x-hwc::table.row>
    </x-hwc::table.body>
</x-hwc::table>
```

## Components

| Component | Element | Slot |
| --- | --- | --- |
| `table` | `table` wrapped in a responsive container | `table`, `table-container` |
| `table.header` | `thead` | `table-header` |
| `table.body` | `tbody` | `table-body` |
| `table.footer` | `tfoot` | `table-footer` |
| `table.row` | `tr` | `table-row` |
| `table.head` | `th` | `table-head` |
| `table.cell` | `td` | `table-cell` |
| `table.caption` | `caption` | `table-caption` |
