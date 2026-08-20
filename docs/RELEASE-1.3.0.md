# Lend Sure 1.3.0 Release Notes

## Summary

Version 1.3.0 focuses on borrower maintenance, portfolio totals, and lightweight lending analytics. It does not change the core interest, payment-allocation, extension, penalty, acknowledgement, or reminder calculation rules.

## New Features

### Editable borrower records

Administrators can now open **Lend Sure → Borrowers → Edit** to correct incomplete borrower information or update details that have changed.

Editable fields include:

- Full name
- Phone
- Email
- Address
- National ID / identification
- Notes

Borrower records now also store an update timestamp. Existing uploaded signed acknowledgements are not modified when borrower data changes.

### Dashboard Total Expected Amount

The dashboard now displays **Total Expected Amount** for all active loans:

`Outstanding Principal + Accrued Interest + Accrued Penalties`

This is the amount currently receivable. It does not include interest that has not yet been charged.

### Loan Register Totals

The bottom of the Loans screen now summarizes:

- Loans listed
- Original principal issued
- Outstanding principal
- Interest due
- Penalties due
- Current total expected

### 12-month lending performance

The Dashboard includes a dependency-free 12-month graph for:

- principal issued per month;
- interest and penalties actually collected per month; and
- number of loans issued per month.

The graph is intended to show growth or decline in lending activity. **Lending income collected is not accounting profit**, because Lend Sure does not currently record operating expenses, taxes, bad-debt write-offs, or other costs.

## Database Upgrade

The Lend Sure database schema version is now `1.3.0`. WordPress `dbDelta()` adds borrower update tracking automatically during plugin upgrade.

## Compatibility

- WordPress 6.4+
- PHP 7.4+
- Existing Lend Sure 1.x loan data remains in the same custom tables.
