<?php

declare(strict_types=1);

namespace App\Services\Taxonomies;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/** Coordinate temporary edit leases for taxonomy records across admin sessions. */
class TaxonomyEditLocks
{
    private const TTL_MINUTES = 15;

    /** @return array{acquired: bool, lock: array<string, mixed>|null} */
    public function acquire(string $type, int $id, User $user): array
    {
        return $this->synchronized($type, $id, function (string $key) use ($user): array {
            $existing = Cache::get($key);
            if (is_array($existing) && ($existing['active'] ?? true)) {
                if ((int) ($existing['user_id'] ?? 0) !== (int) $user->id) {
                    return ['acquired' => false, 'lock' => $existing];
                }

                Cache::put($key, $existing, now()->addMinutes(self::TTL_MINUTES));

                return ['acquired' => true, 'lock' => $existing];
            }

            $lock = [
                'token' => (string) Str::uuid(),
                'active' => true,
                'user_id' => (int) $user->id,
                'user_name' => (string) $user->name,
                'locked_at' => now()->toIso8601String(),
            ];
            Cache::put($key, $lock, now()->addMinutes(self::TTL_MINUTES));

            return ['acquired' => true, 'lock' => $lock];
        });
    }

    /** Return lock details safe to expose to the authorized taxonomy list. */
    public function inspect(string $type, int $id): ?array
    {
        $lock = Cache::get($this->key($type, $id));

        return is_array($lock) && ($lock['active'] ?? true) ? $lock : null;
    }

    public function belongsTo(string $type, int $id, int $userId, string $token): bool
    {
        return $this->matches($this->inspect($type, $id), $userId, $token);
    }

    public function refresh(string $type, int $id, int $userId, string $token): bool
    {
        return $this->synchronized($type, $id, function (string $key) use ($userId, $token): bool {
            $lock = Cache::get($key);
            if (!$this->matches($lock, $userId, $token)) {
                return false;
            }

            Cache::put($key, $lock, now()->addMinutes(self::TTL_MINUTES));

            return true;
        });
    }

    public function release(string $type, int $id, int $userId, string $token): bool
    {
        return $this->synchronized($type, $id, function (string $key) use ($userId, $token): bool {
            $lock = Cache::get($key);
            if (!$this->matches($lock, $userId, $token)) {
                return false;
            }

            $lock['active'] = false;
            Cache::put($key, $lock, now()->addMinutes(self::TTL_MINUTES));

            return true;
        });
    }

    public function forceRelease(string $type, int $id): ?array
    {
        return $this->synchronized($type, $id, function (string $key): ?array {
            $lock = Cache::get($key);
            if (is_array($lock)) {
                $lock['active'] = false;
                Cache::put($key, $lock, now()->addMinutes(self::TTL_MINUTES));
            }

            return is_array($lock) ? $lock : null;
        });
    }

    private function matches(mixed $lock, int $userId, string $token): bool
    {
        return is_array($lock)
            && ($lock['active'] ?? true)
            && (int) ($lock['user_id'] ?? 0) === $userId
            && is_string($lock['token'] ?? null)
            && hash_equals($lock['token'], $token);
    }

    /** Verify that a save carries the currently active lease. */
    public function canSave(string $type, int $id, int $userId, string $token): bool
    {
        return $this->synchronized($type, $id, function (string $key) use ($userId, $token): bool {
            $lock = Cache::get($key);

            return $this->matches($lock, $userId, $token);
        });
    }

    private function key(string $type, int $id): string
    {
        return 'jobic:taxonomy-edit-lock:' . hash('sha256', $type . ':' . $id);
    }

    private function synchronized(string $type, int $id, callable $callback): mixed
    {
        $key = $this->key($type, $id);

        return Cache::lock($key . ':mutex', 5)->block(3, fn () => $callback($key));
    }
}
