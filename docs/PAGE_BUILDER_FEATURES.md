# WolfBooks Page Builder — Feature Documentation

## Overview

The Page Builder is a dynamic form builder that allows users to create complex forms with calculations, conditional logic, auto-fill, and database-driven dropdowns. Forms can be used standalone (via Preview) or generated into full CRUD pages.

**URL:** `/master/page-builder/{page_id}/fields`  
**Preview:** `/master/page-builder/{page_id}/preview`

---

## Database Schema

### `pages` table
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | FK | Owner |
| page_name | string | Form name (e.g., "Invoice") |
| is_generated | boolean | Whether CRUD has been generated |
| settings | JSON | Form-level settings (currency, precision, round-off) |

### `page_fields` table
| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| page_id | FK | Parent page |
| field_name | string | Display name |
| field_key | string | Unique key for formula references (e.g., `qty`, `rate`) |
| field_type | string | Type of field (see Field Types below) |
| sort_order | int | Position in form |
| col_span | int (1-3) | Column width (1/3, 2/3, or full) |
| label | string | Label shown to user |
| column_name | string | Database column name (for generated CRUD) |
| placeholder | string | Input placeholder text |
| default_value | string | Default value |
| is_required | boolean | Required validation |
| is_unique | boolean | Unique constraint |
| is_nullable | boolean | Allow null |
| column_length | int | Max length |
| description | string | Help text |
| repeater_columns | JSON | Column definitions for repeater/table fields |
| options | JSON | Select options (static/dynamic) |
| formula | JSON | Formula expression and format |
| visibility_rules | JSON | Conditional show/hide rules |
| validation_rules | JSON | Custom validation rules |
| auto_fill | JSON | Auto-fill configuration |
| summary_config | JSON | Summary block line items |
| tax_config | JSON | Tax group configuration |

---

## Field Types

### Basic Input Fields
| Type | Description | HTML Input |
|------|-------------|------------|
| `title` | Short text | `<input type="text">` |
| `content` | Long textarea | `<textarea>` |
| `number` | Integer | `<input type="number" step="1">` |
| `decimal` | Float/decimal | `<input type="number" step="0.01">` |
| `currency` | Money/price | `<input type="number" step="0.01">` |
| `email` | Email address | `<input type="email">` |
| `phone` | Phone number | `<input type="tel">` |
| `url` | Website link | `<input type="url">` |
| `password` | Masked field | `<input type="password">` |

### Date & Time
| Type | Description |
|------|-------------|
| `date` | Date picker (supports "use current date as default" option) |
| `datetime` | Date + time picker |
| `time` | Time picker |
| `date_range` | From-to date pair |

### Selection Fields
| Type | Description |
|------|-------------|
| `select` | Dropdown (static or database-driven) |
| `multi_select` | Tag-style multi-select |
| `radio` | Radio button group |
| `checkbox` | Single checkbox (on/off) |
| `toggle` | Boolean switch |

### Calculation Fields
| Type | Description |
|------|-------------|
| `formula` | Auto-calculated read-only field |
| `tax_group` | GST tax calculation block |
| `summary` | Invoice totals summary block |

### Media & Files
| Type | Description |
|------|-------------|
| `image` | Image upload (multiple) |
| `file` | Document upload (multiple) |
| `signature` | Touch/mouse signature pad |

### Layout & Data
| Type | Description |
|------|-------------|
| `divider` | Section separator |
| `color` | Color picker |
| `rating` | Star rating (1-5) |
| `slider` | Numeric range slider |
| `json` | Raw JSON editor |
| `repeater` | Multi-row table with configurable columns |

---

## Feature: Formula / Calculation Fields

### How it works
- A formula field references other fields by their `field_key` using `{key}` syntax
- Calculations happen client-side in real-time (no server round-trip)
- Uses a safe expression parser (no `eval()`)

### Formula Syntax
```
{qty} * {rate}                              → simple multiplication
{amount} - ({amount} * {discount_pct} / 100) → percentage discount
SUM({items.total_amt})                       → sum of a repeater column
ROUND({subtotal} * 0.18, 2)                  → round to 2 decimals
IF({discount} > 0, {amount} - {discount}, {amount}) → conditional
```

### Supported Operators
`+`, `-`, `*`, `/`, `%` (modulo), `()` parentheses

### Supported Functions
| Function | Description |
|----------|-------------|
| `SUM(values)` | Sum of array values |
| `AVG(values)` | Average |
| `MIN(values)` | Minimum |
| `MAX(values)` | Maximum |
| `ROUND(value, decimals)` | Round to N decimals |
| `ABS(value)` | Absolute value |
| `CEIL(value)` | Round up |
| `FLOOR(value)` | Round down |
| `IF(condition, true_val, false_val)` | Conditional |

### Referencing Table/Repeater Columns
- `{table_key.column_key}` → returns array of all values in that column
- `SUM({items.amount})` → sums the "amount" column across all rows

### Formula Field Settings (stored in `formula` JSON column)
```json
{
  "expression": "SUM({items.total_amt})",
  "format": "currency"  // "currency" | "number" | "percentage"
}
```

---

## Feature: Repeater / Table Field

### What it does
Creates a multi-row table where users can add/remove rows. Each column is configurable.

### Column Types
| Type | Description |
|------|-------------|
| `text` | Plain text input |
| `number` | Integer input |
| `decimal` | Decimal input |
| `date` | Date picker |
| `datetime` | DateTime picker |
| `time` | Time picker |
| `select` | Dropdown (static or database) |
| `textarea` | Multi-line text |
| `checkbox` | Boolean |
| `formula` | Auto-calculated (read-only) |

### Column Configuration (stored in `repeater_columns` JSON)
```json
[
  {
    "key": "qty",
    "label": "Qty",
    "type": "number",
    "required": true,
    "default": "1",
    "formula": "",
    "show_summary": true,
    "options": [],
    "dynamic": null,
    "auto_fill_enabled": false,
    "auto_fill_mappings": []
  },
  {
    "key": "amt",
    "label": "Amount",
    "type": "formula",
    "formula": "{qty} * {mrp} - {dis_flat}",
    "show_summary": true
  }
]
```

### Per-Row Formula Columns
- Formula columns auto-calculate per row as user types
- Can reference other columns in the same row: `{qty} * {rate}`
- Can reference form-level fields: `{total_km} * {per_km_rate}`
- Supports conditional: `IF({dis_on} == "after_tax", {qty} * {mrp}, {qty} * {mrp} - {dis_flat})`

### Column Summary (Footer)
- Numeric and formula columns can show SUM in the table footer
- Enabled via `show_summary: true`

### Column Reorder
- Drag-and-drop reorder via drag handle
- Move up/down buttons

---

## Feature: Select Field — Database Source

### Loading Modes
| Mode | Description | Best For |
|------|-------------|----------|
| `preload` | Loads all options on page render | Small lists (<100 items) |
| `server_search` | Fetches from server as user types | Large lists |
| `select2` | Searchable dropdown with server filtering | Large datasets |

### Configuration (stored in `options.dynamic`)
```json
{
  "enabled": true,
  "table": "vendors",
  "label_col": "name",
  "value_col": "id",
  "load_mode": "server_search",
  "search_cols": "name,code,email",
  "min_chars": 2,
  "max_results": 20
}
```

### API Endpoints
- **Get columns:** `GET /master/page-builder/get-columns?table=vendors`
- **Search options:** `GET /master/page-builder/search-options?table=vendors&search=abc&label_col=name&value_col=id&search_cols=name,code&limit=20`
- **Lookup row:** `GET /master/page-builder/lookup?table=vendors&id=5&columns=address,gstin`

---

## Feature: Auto-Fill on Select

### What it does
When a user selects a value from a dropdown, automatically fills other fields with data from the same database row.

### Configuration (stored in `auto_fill` JSON column)
```json
{
  "enabled": true,
  "source_table": "vendors",
  "mappings": [
    {
      "source_column": "address",
      "target_field_key": "vendor_address",
      "readonly": true
    },
    {
      "source_column": "gstin",
      "target_field_key": "vendor_gstin",
      "readonly": false
    }
  ]
}
```

### Behavior
- `readonly: true` → target field becomes read-only after auto-fill (user can't edit)
- `readonly: false` → target field is editable after auto-fill
- Clearing the select clears all auto-filled fields and removes readonly

### Auto-Fill in Repeater Columns
Same concept but within a row:
```json
{
  "auto_fill_enabled": true,
  "auto_fill_mappings": [
    { "source_col": "hsn_code", "target_col_key": "hsn" },
    { "source_col": "rate", "target_col_key": "mrp" }
  ]
}
```

---

## Feature: Visibility Rules (Conditional Show/Hide)

### What it does
Show or hide a field based on the value of another field.

### Configuration (stored in `visibility_rules` JSON column)
```json
{
  "logic": "AND",
  "rules": [
    { "field": "calc_base", "operator": "==", "value": "km_base" },
    { "field": "total", "operator": ">", "value": "1000" }
  ]
}
```

### Supported Operators
| Operator | Description |
|----------|-------------|
| `==` | Equals |
| `!=` | Not equals |
| `>` | Greater than |
| `<` | Less than |
| `>=` | Greater than or equal |
| `<=` | Less than or equal |
| `contains` | String contains |
| `is_empty` | Field is empty |
| `is_not_empty` | Field has a value |

### Logic
- `AND` → ALL rules must match for field to show
- `OR` → ANY rule matching shows the field

### Applies to
- All standard fields
- Repeater/table fields (show/hide entire table)
- Formula fields

---

## Feature: Summary Block

### What it does
Displays a styled right-aligned summary (like invoice totals) with configurable lines.

### Configuration (stored in `summary_config` JSON column)
```json
{
  "lines": [
    { "label": "Subtotal", "formula": "SUM({items.amt})", "style": "normal" },
    { "label": "CGST", "formula": "SUM({items.amt}) * AVG({items.cgst_pct}) / 100", "style": "normal" },
    { "label": "SGST", "formula": "SUM({items.amt}) * AVG({items.sgst_pct}) / 100", "style": "normal" },
    { "label": "Round Off", "formula": "{round_off}", "style": "small" },
    { "label": "Grand Total", "formula": "{grand_total}", "style": "bold" }
  ],
  "alignment": "right"
}
```

### Line Styles
- `normal` → regular text
- `small` → smaller text
- `bold` → bold with top border (for totals)

---

## Feature: Form-Level Settings

Stored in `pages.settings` JSON column:
```json
{
  "currency": "₹",
  "locale": "en-IN",
  "decimal_precision": 2,
  "round_off_rule": "round",
  "auto_save_draft": false,
  "allow_edit_after_submit": false,
  "title": "Purchase Invoice",
  "description": "Invoice entry with line items"
}
```

---

## Feature: Preview

**URL:** `/master/page-builder/{page_id}/preview`

### What it does
- Renders the form exactly as a user would see it
- All formula calculations work in real-time
- Repeater tables with add/remove rows
- Auto-fill triggers on select change
- Visibility rules show/hide fields dynamically
- Summary block updates live
- Server-side search for select fields

### Formula Engine
- Located at `/public/js/formula-engine.js`
- Class: `FormulaEngine`
- Safe expression parser (no eval)
- Supports string comparisons for IF() conditions
- Handles array values for SUM/AVG of table columns

---

## Feature: Code Generation

**URL:** POST `/master/page-builder/{page}/generate`

### What it generates
1. **Migration** — creates/alters database table with proper column types
2. **Model** — Eloquent model with fillable, casts, relationships
3. **Controller** — Full CRUD with validation, file uploads, repeater handling
4. **Views** — index (with search, pagination, export), create, edit, show
5. **Export** — Excel export with styling
6. **Routes** — auto-appended to `routes/generated.php`

---

## Helper Class: `App\Helpers\PageFieldHelper`

Reusable helper for rendering fields and validation:

| Method | Description |
|--------|-------------|
| `columnName($field)` | Get database column name |
| `inputName($field)` | Get HTML input name |
| `validationRules($fields, $table, $ignoreId)` | Build Laravel validation rules |
| `resolveOptions($field)` | Get select options (static or from DB) |
| `defaultValue($field, $existing)` | Get default value (handles use_current_date) |
| `htmlInputType($fieldType)` | Map field type to HTML input type |
| `colSpanClass($field, $useGrid)` | Get CSS grid class |
| `stepValue($fieldType)` | Get step attribute for number inputs |

---

## Example: Invoice Form Structure

```
Page: Invoice (ID: 92)
Settings: { currency: "₹", decimal_precision: 2, round_off_rule: "round" }

Fields:
├── invoice_no (title, required, col_span: 1)
├── invoice_date (date, required, default: today, col_span: 1)
├── po_no (title, col_span: 1)
├── po_date (date, col_span: 1)
├── buyer (select, database: companies, auto_fill → buyer_address, col_span: 1)
├── vendor (select, database: vendors, server_search, auto_fill → vendor_address, col_span: 1)
├── buyer_address (content, col_span: 1)
├── vendor_address (content, col_span: 1)
├── dispatch_through (title, col_span: 1)
├── dispatch_date (date, col_span: 1)
├── items (repeater, col_span: 3)
│   ├── particular (text, required)
│   ├── hsn (text)
│   ├── qty (number, required, default: 1)
│   ├── unit (select, static: PCS/PACKS/KG/LTR/MTR/BOX/NOS)
│   ├── mrp (decimal, required)
│   ├── dis_flat (decimal)
│   ├── dis_pct (decimal)
│   ├── dis_on (select: before_tax/on_mrp/after_tax)
│   ├── amt (formula: conditional based on dis_on)
│   ├── cgst_pct (decimal)
│   ├── sgst_pct (decimal)
│   ├── igst_pct (decimal)
│   ├── cess_pct (decimal)
│   └── total_amt (formula: amt + tax, show_summary: true)
├── subtotal (formula: SUM({items.total_amt}), format: currency)
├── additional_discount (decimal)
├── round_off (formula: ROUND({subtotal} - {additional_discount}, 0) - ({subtotal} - {additional_discount}))
├── grand_total (formula: ROUND({subtotal} - {additional_discount}, 0))
├── invoice_summary (summary block with tax breakup)
├── remark (content, col_span: 3)
└── auto_approve (radio: yes/no)
```

---

## Example: Conveyance Form with Conditional Logic

```
Page: Conveyance (ID: 91)

Fields:
├── voucher_no (title, required)
├── voucher_date (date, required, default: today)
├── employee_name (title, required)
├── mode (select: bike/car/auto/bus/train/flight)
├── calc_base (select: km_base/fixed/actual)
├── per_km_rate (decimal, visibility: calc_base == "km_base")  ← CONDITIONAL
├── entries (repeater)
│   ├── date (date, required)
│   ├── from_place (text, required)
│   ├── to_place (text, required)
│   ├── opening_km (decimal)
│   ├── closing_km (decimal)
│   ├── total_km (formula: {closing_km} - {opening_km}, show_summary)
│   └── amount (formula: {total_km} * {per_km_rate}, show_summary)  ← CROSS-REFERENCE
├── total_km_all (formula: SUM({entries.total_km}), format: number)
├── total_amount (formula: SUM({entries.amount}), format: currency)
├── remark (content)
└── auto_approve (radio: yes/no)
```

Key features demonstrated:
- `per_km_rate` only shows when `calc_base == "km_base"` (visibility rules)
- `amount` formula references form-level field `{per_km_rate}` (cross-reference)
- `total_km_all` uses `SUM({entries.total_km})` to aggregate repeater column
