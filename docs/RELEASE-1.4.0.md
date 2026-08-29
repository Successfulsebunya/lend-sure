# Your Loan Ledger 1.4.0 — Data Management & Reporting

## Scope

Version 1.4.0 adds administrator-controlled backup/restore, correction controls, explicit data cleanup, and management reporting. Existing lending calculations and compatibility identifiers remain intact.

## Complete Backup & Restore

- Adds **Your Loan Ledger → Tools**.
- Complete Backup exports the five plugin-owned custom tables and relevant `lendsure_*` settings to a portable JSON file.
- Referenced company-logo and acknowledgement media are embedded when the files are readable.
- Restore is an administrator-only replacement operation and requires the typed confirmation `RESTORE`.
- Restore whitelists expected database columns and runs the table replacement inside a database transaction.
- Restored media receives new WordPress attachment IDs and loan/settings references are remapped accordingly.

## Payment corrections

- Adds **Void Payment** instead of silent payment deletion.
- Only the latest eligible payment can be voided when no later balance-changing activity depends on it.
- Voiding restores the original principal, interest, and penalty components to the loan balance.
- The operational payment amount/components are zeroed so the correction no longer inflates dashboard/report totals.
- The original payment values, reason, administrator ID, and void timestamp are retained in a `payment_voided` transaction record for audit purposes.

## Loan corrections

- **Void / Cancel Loan** preserves the record/history while removing the loan from active-portfolio totals and reports.
- **Permanent Delete Loan** is separately protected by nonce, capability check, and the typed confirmation `DELETE`.
- Permanent deletion removes the loan's related payments, transaction history, and reminder records while leaving the borrower record intact.
- Deletion of the loan acknowledgement media remains an explicit separate option.

## Uninstall and data cleanup

- Normal uninstall continues to preserve Your Loan Ledger data by default.
- Administrators can explicitly enable deletion of plugin database data on uninstall.
- **Erase All Data & Deactivate** provides an immediate destructive cleanup path and requires the typed confirmation `ERASE`.
- Referenced media deletion is optional and separate from database cleanup.
- Administrators are instructed to download a Complete Backup first.

## Management reports

Adds **Your Loan Ledger → Reports** with selectable reporting dates and:

- loans issued;
- borrowers served;
- principal issued;
- average loan size;
- total cash collected;
- principal collected;
- interest collected;
- penalties collected;
- Lending Income;
- current active-loan exposure;
- current expected amount;
- overdue loan count and overdue exposure; and
- monthly lending/collection activity.

Reports support CSV export and browser-native Print / Save PDF.

**Lending Income** is interest plus penalties actually collected. It is not described as accounting profit because the plugin does not track operating expenses, taxes, write-offs, salaries, rent, or other business costs.

## Backward compatibility

The public plugin identity remains **Your Loan Ledger**, but the established internal identifiers continue unchanged:

- `LendSure_*` PHP classes;
- `lendsure_*` database tables;
- `lendsure_*` WordPress options;
- `lendsure_*` actions/admin identifiers; and
- existing borrower, loan, payment, transaction, reminder, and acknowledgement records.

Version 1.4.0 does not require destructive migration of existing loan data.

## Security model

All new destructive/state-changing actions require the `manage_options` capability and WordPress nonces. Import/restore uses a typed confirmation, file-size limit, expected backup manifest, field whitelisting, and a database transaction. Permanent deletion and complete erasure require separate typed confirmations.
