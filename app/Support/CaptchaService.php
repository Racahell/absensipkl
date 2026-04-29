<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CaptchaService
{
    public static function resolveMode(): string
    {
        $configured = config('captcha.mode', 'auto');
        $hasRecaptchaConfig = (bool) config('services.recaptcha.site_key') && (bool) config('services.recaptcha.secret_key');

        if ($configured === 'online') {
            return $hasRecaptchaConfig ? 'online' : 'offline';
        }

        if ($configured === 'offline') {
            return 'offline';
        }

        return $hasRecaptchaConfig ? 'online' : 'offline';
    }

    public static function buildOfflineChallenge(Request $request): string
    {
        $a = random_int((int) config('captcha.offline_min', 1), (int) config('captcha.offline_max', 20));
        $b = random_int((int) config('captcha.offline_min', 1), (int) config('captcha.offline_max', 20));

        $request->session()->put('offline_captcha_answer', $a + $b);

        return $a.' + '.$b.' = ?';
    }

    public static function validate(Request $request): bool
    {
        $mode = self::resolveMode();

        if ($mode === 'online') {
            $token = (string) $request->input('g-recaptcha-response');
            if ($token === '') {
                return false;
            }

            try {
                $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => config('services.recaptcha.secret_key'),
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);

                return (bool) data_get($response->json(), 'success', false);
            } catch (\Throwable) {
                return false;
            }
        }

        $answer = (int) $request->session()->get('offline_captcha_answer', -1);
        $input = (int) $request->input('offline_captcha_answer', -9999);

        return $answer !== -1 && $answer === $input;
    }
}
