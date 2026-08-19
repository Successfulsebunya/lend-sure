# Lend Sure

Lend Sure is a lightweight private loan-management plugin for WordPress. It is designed for administrators who lend money and need a clear record of borrowers, loan balances, monthly interest, partial payments, loan extensions, penalties, signed acknowledgements, and transaction history without running a separate application.

## Key Features

- Default **20% monthly interest**, configurable per loan and in Settings.
- Borrower records with contact and identification details.
- Loan creation with automatic first-month interest.
- Partial payment allocation: **Interest → Penalty → Principal**.
- Projected next-month interest based on the remaining principal.
- Loan extensions with optional capitalization of unpaid interest and penalties.
- Fixed or percentage penalties.
- Printable Loan Acknowledgement & Acceptance document.
- Upload and retain signed acknowledgement copies as PDF, JPG, JPEG, or PNG.
- Full payment and transaction history.
- Dashboard for active and overdue loans.
- CSV export of the loan register.
- Dedicated custom database tables for loan data.

## Requirements

- WordPress 6.4 or later
- PHP 7.4 or later
- Administrator access to WordPress

## Installation

1. Download the plugin ZIP.
2. In WordPress, go to **Plugins → Add New → Upload Plugin**.
3. Choose the Lend Sure ZIP file and click **Install Now**.
4. Activate **Lend Sure**.
5. Open **Lend Sure → Settings** and configure your loan defaults and lender details.
6. Add a borrower before creating the first loan.

## Recommended First-Time Setup

Go to **Lend Sure → Settings** and confirm:

- Currency: `UGX`
- Default Monthly Interest: `20%`
- Default Duration: `1 month`
- Grace Period: your preferred number of days
- Penalty Type: percentage or fixed amount
- Penalty Value
- Lender name, phone, and address

## Documentation

See the full administrator guide:

- [Administrator User Guide](docs/USER-GUIDE.md)

## Loan Calculation Model

For a loan of **UGX 1,000,000 at 20% monthly interest**:

- Principal: UGX 1,000,000
- First-month interest: UGX 200,000
- Initial total due: UGX 1,200,000

If the borrower pays UGX 400,000:

1. UGX 200,000 clears accrued interest.
2. The remaining UGX 200,000 reduces principal.
3. New principal becomes UGX 800,000.
4. Projected next-month interest at 20% becomes UGX 160,000.

## Payment Allocation

Payments are allocated in this order:

1. Accrued interest
2. Accrued penalty
3. Principal

This allocation is recorded in the payment history for auditability.

## Loan Extensions

When extending a loan, the administrator selects the number of months and may choose to capitalize outstanding interest and penalties into the new principal. New extension-period interest is then calculated using the loan's interest rate.

## Signed Loan Acknowledgements

Each loan has a printable acknowledgement document. The recommended workflow is:

1. Create the loan.
2. Open the loan.
3. Select **View / Print Acknowledgement**.
4. Print and obtain the required signatures.
5. Scan or photograph the signed document.
6. Upload it back to the same loan record.

The signed document remains associated with that loan for record keeping.

## Security and Access

Lend Sure's administration screens require WordPress administrator-level `manage_options` capability. Forms use WordPress nonces and sanitization routines.

Because borrower records may contain personal information, administrators should also secure the WordPress site, maintain backups, restrict administrator accounts, and follow applicable privacy requirements.

## Legal Notice

Lend Sure is an administrative record-keeping tool, not legal or financial advice. Before relying on acknowledgement wording, interest rates, penalties, or lending practices as legally enforceable terms, review the laws and regulations that apply in your jurisdiction.

## License

GPL-2.0-or-later.
