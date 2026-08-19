<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LendSure_Calculator {
    public static function interest( $principal, $rate ) {
        return round( (float) $principal * ( (float) $rate / 100 ), 2 );
    }

    public static function total_due( $loan ) {
        return round(
            (float) $loan->current_principal +
            (float) $loan->accrued_interest +
            (float) $loan->accrued_penalty,
            2
        );
    }

    /**
     * Allocation order intentionally follows the agreed rule:
     * interest -> penalty -> principal.
     */
    public static function allocate_payment( $amount, $loan ) {
        $remaining = max( 0, (float) $amount );

        $interest = min( $remaining, max( 0, (float) $loan->accrued_interest ) );
        $remaining -= $interest;

        $penalty = min( $remaining, max( 0, (float) $loan->accrued_penalty ) );
        $remaining -= $penalty;

        $principal = min( $remaining, max( 0, (float) $loan->current_principal ) );
        $remaining -= $principal;

        return array(
            'interest'  => round( $interest, 2 ),
            'penalty'   => round( $penalty, 2 ),
            'principal' => round( $principal, 2 ),
            'unused'    => round( $remaining, 2 ),
        );
    }

    public static function penalty( $loan, $type, $value ) {
        if ( 'fixed' === $type ) {
            return max( 0, round( (float) $value, 2 ) );
        }

        return round( (float) $loan->current_principal * ( max( 0, (float) $value ) / 100 ), 2 );
    }
}
