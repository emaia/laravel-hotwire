# Table

Responsive table primitives that keep markup semantic while exposing stable `data-slot` styling hooks.

## Usage

```blade
<hw:table>
    <hw:table.caption>Recent invoices</hw:table.caption>
    <hw:table.header>
        <hw:table.row>
            <hw:table.head>Invoice</hw:table.head>
            <hw:table.head>Status</hw:table.head>
        </hw:table.row>
    </hw:table.header>
    <hw:table.body>
        <hw:table.row>
            <hw:table.cell>INV001</hw:table.cell>
            <hw:table.cell>Paid</hw:table.cell>
        </hw:table.row>
    </hw:table.body>
</hw:table>
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

## Styling hooks

The component exposes stable `data-slot` hooks for preset and application CSS:

- `data-slot="table-container"`
- `data-slot="table"`
- `data-slot="table-header"`
- `data-slot="table-body"`
- `data-slot="table-footer"`
- `data-slot="table-row"`
- `data-slot="table-head"`
- `data-slot="table-cell"`
- `data-slot="table-caption"`
