<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Abandoned-cart reminder emails
    |--------------------------------------------------------------------------
    |
    | Carts are persisted to the `carts` table on every change (see
    | App\Services\CartService). The cart:send-abandoned-reminders command
    | sweeps for idle carts and emails a reminder. Sending is OFF by default:
    | it must be explicitly enabled once the founder has settled the
    | marketing-consent / CAN-SPAM policy (see TIM-28 / TIM-15).
    |
    */

    'reminders' => [
        // Master switch. While false the sweep command runs but sends nothing,
        // so the schedule can be deployed safely ahead of the policy decision.
        'enabled' => (bool) env('CART_REMINDERS_ENABLED', false),

        // Whether to email GUEST (non-account) abandoners. Separate from the
        // master switch because guest emailing carries a distinct consent
        // burden — logged-in customers agreed to be contacted at signup.
        // Requires founder sign-off before flipping to true.
        'email_guests' => (bool) env('CART_REMINDERS_EMAIL_GUESTS', false),

        // Staged reminders. Each stage fires once a cart has been idle for at
        // least `after_hours` and the previous stage has been sent. `stage`
        // must be strictly increasing.
        'stages' => [
            ['stage' => 1, 'after_hours' => 4],
            ['stage' => 2, 'after_hours' => 24],
        ],

        // Carts idle longer than this are considered dead and are skipped
        // (avoids emailing someone about a cart from weeks ago).
        'max_age_hours' => (int) env('CART_REMINDERS_MAX_AGE_HOURS', 168), // 7 days

        // Sender name shown in the reminder; address falls back to mail.from.
        'from_name' => env('CART_REMINDERS_FROM_NAME', 'Timber Trace Crafts'),
    ],

];
