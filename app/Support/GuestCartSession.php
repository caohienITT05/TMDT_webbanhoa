<?php

namespace App\Support;

use Illuminate\Support\Facades\Session;

class GuestCartSession
{
    private const SESSION_KEY = 'guest_cart_session_id';

    public static function id(): string
    {
        $cartSessionId = Session::get(self::SESSION_KEY);

        if (! is_string($cartSessionId) || $cartSessionId === '') {
            $cartSessionId = Session::getId();
            Session::put(self::SESSION_KEY, $cartSessionId);
        }

        return $cartSessionId;
    }

    public static function forget(): void
    {
        Session::forget(self::SESSION_KEY);
    }
}