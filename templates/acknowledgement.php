<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo esc_html( sprintf( 'Loan Acknowledgement #%d', $loan->id ) ); ?></title>
<style>
body{font-family:Arial,sans-serif;color:#111;background:#f2f2f2;margin:0;padding:24px}.page{max-width:800px;margin:auto;background:#fff;padding:50px;box-shadow:0 2px 12px rgba(0,0,0,.12)}h1{text-align:center;font-size:24px;margin:0 0 8px}.sub{text-align:center;margin-bottom:32px}.row{display:flex;gap:30px}.col{flex:1}.box{border:1px solid #ccc;padding:18px;margin:18px 0}.amount{font-size:20px;font-weight:700}.signatures{display:grid;grid-template-columns:1fr 1fr;gap:50px;margin-top:60px}.line{border-top:1px solid #111;padding-top:8px;margin-top:50px}.actions{text-align:center;margin:20px}.actions button{padding:10px 18px;font-size:15px}table{width:100%;border-collapse:collapse}td{padding:7px 0;vertical-align:top}td:first-child{width:42%;font-weight:bold}@media print{body{background:#fff;padding:0}.page{box-shadow:none;max-width:none;padding:20px}.actions{display:none}}
</style>
</head>
<body>
<div class="actions"><button onclick="window.print()"><?php esc_html_e( 'Print / Save as PDF', 'lend-sure' ); ?></button></div>
<main class="page">
<h1><?php esc_html_e( 'LOAN ACKNOWLEDGEMENT & ACCEPTANCE', 'lend-sure' ); ?></h1>
<div class="sub"><?php echo esc_html( sprintf( __( 'Loan Reference: LS-%06d', 'lend-sure' ), $loan->id ) ); ?></div>

<p><?php esc_html_e( 'This document records the loan agreement between the lender and borrower named below. By signing, the borrower confirms receipt of the principal and accepts the repayment terms stated in this acknowledgement.', 'lend-sure' ); ?></p>

<div class="row">
<div class="col box">
<h3><?php esc_html_e( 'Lender', 'lend-sure' ); ?></h3>
<p><strong><?php echo esc_html( $lender_name ); ?></strong><br><?php echo nl2br( esc_html( $lender_address ) ); ?><br><?php echo esc_html( $lender_phone ); ?></p>
</div>
<div class="col box">
<h3><?php esc_html_e( 'Borrower', 'lend-sure' ); ?></h3>
<p><strong><?php echo esc_html( $loan->full_name ); ?></strong><br><?php echo nl2br( esc_html( $loan->address ) ); ?><br><?php echo esc_html( $loan->phone ); ?><br><?php echo esc_html( $loan->national_id ); ?></p>
</div>
</div>

<div class="box">
<table>
<tr><td><?php esc_html_e( 'Principal received', 'lend-sure' ); ?></td><td class="amount"><?php echo esc_html( $currency . ' ' . number_format_i18n( $loan->original_principal, 0 ) ); ?></td></tr>
<tr><td><?php esc_html_e( 'Monthly interest rate', 'lend-sure' ); ?></td><td><?php echo esc_html( number_format_i18n( $loan->interest_rate, 2 ) . '%' ); ?></td></tr>
<tr><td><?php esc_html_e( 'Initial interest charged', 'lend-sure' ); ?></td><td><?php echo esc_html( $currency . ' ' . number_format_i18n( $loan->initial_interest, 0 ) ); ?></td></tr>
<tr><td><?php esc_html_e( 'Loan start date', 'lend-sure' ); ?></td><td><?php echo esc_html( $loan->start_date ); ?></td></tr>
<tr><td><?php esc_html_e( 'Initial due date', 'lend-sure' ); ?></td><td><?php echo esc_html( $loan->original_due_date ); ?></td></tr>
<tr><td><?php esc_html_e( 'Amount due under this acknowledgement', 'lend-sure' ); ?></td><td class="amount"><?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $loan->original_principal + (float) $loan->initial_interest, 0 ) ); ?></td></tr>
<?php if ( $loan->purpose ) : ?><tr><td><?php esc_html_e( 'Purpose', 'lend-sure' ); ?></td><td><?php echo nl2br( esc_html( $loan->purpose ) ); ?></td></tr><?php endif; ?>
</table>
</div>

<h3><?php esc_html_e( 'Acceptance', 'lend-sure' ); ?></h3>
<p><?php esc_html_e( 'I, the borrower named above, acknowledge receiving the principal amount shown in this document. I understand and accept the interest rate, repayment due date, payment allocation rules, and any agreed penalty or extension terms. Any partial payment, extension, penalty, or other adjustment will be recorded in the lender’s loan ledger and will update the outstanding balance accordingly.', 'lend-sure' ); ?></p>

<?php if ( $loan->terms ) : ?>
<h3><?php esc_html_e( 'Additional Terms', 'lend-sure' ); ?></h3>
<p><?php echo nl2br( esc_html( $loan->terms ) ); ?></p>
<?php endif; ?>

<div class="signatures">
<div><div class="line"><?php esc_html_e( 'Borrower Signature', 'lend-sure' ); ?></div><p><?php esc_html_e( 'Date:', 'lend-sure' ); ?> __________________</p></div>
<div><div class="line"><?php esc_html_e( 'Lender Signature', 'lend-sure' ); ?></div><p><?php esc_html_e( 'Date:', 'lend-sure' ); ?> __________________</p></div>
<div><div class="line"><?php esc_html_e( 'Witness Name & Signature', 'lend-sure' ); ?></div><p><?php esc_html_e( 'Phone / ID:', 'lend-sure' ); ?> __________________</p></div>
<div><div class="line"><?php esc_html_e( 'Borrower Thumbprint (optional)', 'lend-sure' ); ?></div></div>
</div>
</main>
</body>
</html>
