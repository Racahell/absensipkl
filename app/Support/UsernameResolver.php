<?php

namespace App\Support;

use App\Models\User;

class UsernameResolver
{
    public function generateUnique(
        ?string $preferred,
        ?string $nis,
        ?string $nuptk,
        ?string $email,
        ?int $ignoreUserId = null
    ): string {
        $base = trim((string) ($preferred ?: ($nis ?: ($nuptk ?: $email))));
        if ($base === '') {
            $base = 'user';
        }

        $base = strtolower(preg_replace('/[^a-z0-9._-]/i', '', $base) ?: 'user');
        if ($base === '') {
            $base = 'user';
        }

        $candidate = $base;
        $i = 1;
        while (User::query()
            ->where('username', $candidate)
            ->when($ignoreUserId !== null, fn ($q) => $q->where('id', '!=', $ignoreUserId))
            ->exists()) {
            $i++;
            $candidate = $base.$i;
        }

        return $candidate;
    }
}

