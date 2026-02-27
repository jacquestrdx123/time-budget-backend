<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;

class DomainService
{
    private const EXCLUDED_DOMAINS = ['gmail.com', 'googlemail.com'];

    public static function extractDomain(string $email): string
    {
        $domain = Str::lower(Str::after($email, '@'));
        return preg_replace('/[^a-z0-9.-]/', '', $domain) ?: $domain;
    }

    public static function isExcludedDomain(string $domain): bool
    {
        return in_array(Str::lower($domain), self::EXCLUDED_DOMAINS, true);
    }

    /**
     * Find a tenant that has at least one user with the given email domain.
     */
    public static function findTenantByDomain(string $domain): ?Tenant
    {
        $domain = Str::lower($domain);
        if ($domain === '') {
            return null;
        }

        $user = User::query()
            ->get()
            ->first(fn (User $u) => Str::lower(Str::after($u->email, '@')) === $domain);

        return $user?->tenant;
    }
}
