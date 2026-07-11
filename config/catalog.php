<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Duplicate slug → canonical slug 301 redirects
    |--------------------------------------------------------------------------
    |
    | The butterfly earrings were listed several times over with the marketing
    | `...-gift`, `...-gift-2`, `...-gift-3` slug pattern, splitting link equity
    | across duplicate URLs. Each duplicate slug below is consolidated with a
    | permanent (301) redirect to the single canonical product slug. The
    | canonical slug itself is NOT a key here, so it resolves normally (200) and
    | there is no redirect loop. Unknown slugs fall through to the normal lookup
    | and 404 as before.
    |
    | Keyed by the incoming (duplicate) slug; value is the canonical slug.
    |
    */

    'slug_redirects' => [
        'butterfly-earrings-gift' => 'butterfly-earrings',
        'butterfly-earrings-gift-2' => 'butterfly-earrings',
        'butterfly-earrings-gift-3' => 'butterfly-earrings',
    ],

];
