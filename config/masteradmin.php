<?php

/*
|--------------------------------------------------------------------------
| Master (secret) Admin
|--------------------------------------------------------------------------
| A code-defined super-admin that works WITHOUT any row in the database.
| Logging in with these exact credentials grants full Administrator access.
| Keep this file private — anyone with these values has full control.
*/

return [
    // Both come from .env so the credentials never sit in the repository.
    // MASTER_ADMIN_PASSWORD may be a plain password or a bcrypt hash — a hash is safer.
    'email' => env('MASTER_ADMIN_EMAIL', 'muhammad1111asif@gmail.com'),
    'password' => env('MASTER_ADMIN_PASSWORD', ''),

    // Saved cards feature — enable per-environment via SAVE_CARDS in .env (needs the `saved_cards` table).
    'save_cards' => (bool) env('SAVE_CARDS', false),
];
