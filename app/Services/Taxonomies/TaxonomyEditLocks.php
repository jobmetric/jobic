<?php

declare(strict_types=1);

namespace App\Services\Taxonomies;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/** Coordinate owner-scoped edit leases while isolating each editor document's release token. */
class TaxonomyEditLocks
{
    private const TTL_MINUTES = 15;

    /** Reuse an owner's primary lease for transient operations and borrowed embedded editors.
     *
     * @param string $type
     * @param int $id
     * @param User $user
     *
     * @return array{acquired: bool, lock: array<string, mixed>|null}
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function acquire(string $type, int $id, User $user): array
    {
        return $this->acquireLease($type, $id, $user, false);
    }

    /** Acquire a distinct token so leaving another document cannot release this editor.
     *
     * @param string $type
     * @param int $id
     * @param User $user
     *
     * @return array{acquired: bool, lock: array<string, mixed>|null}
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    public function acquireDocument(string $type, int $id, User $user): array
    {
        return $this->acquireLease($type, $id, $user, true);
    }

    /** Serialize owner checks and register only the requested holder token.
     *
     * @param string $type
     * @param int $id
     * @param User $user
     * @param bool $document
     *
     * @return array{acquired: bool, lock: array<string, mixed>|null}
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    private function acquireLease(string $type, int $id, User $user, bool $document): array
    {
        return $this->synchronized($type, $id, function (string $key) use ($user, $document): array {
            $lock = $this->active(Cache::get($key));
            if ($lock && (int) $lock['user_id'] !== (int) $user->id) {
                return ['acquired' => false, 'lock' => $this->expose($lock)];
            }
            if (!$lock) {
                $lock = ['token' => (string) Str::uuid(), 'active' => true, 'user_id' => (int) $user->id,
                    'user_name' => (string) $user->name, 'locked_at' => now()->toIso8601String(), 'holders' => []];
                $token = $lock['token'];
            } else {
                $token = $document ? (string) Str::uuid() : $lock['token'];
            }
            $lock['holders'][$token] = now()->addMinutes(self::TTL_MINUTES)->timestamp;
            Cache::put($key, $lock, now()->addMinutes(self::TTL_MINUTES));
            return ['acquired' => true, 'lock' => $this->expose($lock, $token)];
        });
    }

    /** Return owner metadata and the primary token without disclosing sibling document tokens.
     *
     * @param string $type
     * @param int $id
     *
     * @return array<string, mixed>|null
     */
    public function inspect(string $type, int $id): ?array
    {
        $lock = $this->active(Cache::get($this->key($type, $id)));
        return $lock ? $this->expose($lock) : null;
    }

    /** Check ownership of one live document token.
     *
     * @param string $type
     * @param int $id
     * @param int $userId
     * @param string $token
     *
     * @return bool
     */
    public function belongsTo(string $type, int $id, int $userId, string $token): bool
    {
        return $this->matches(Cache::get($this->key($type, $id)), $userId, $token);
    }

    /** Renew only the holder that sent the authenticated heartbeat.
     *
     * @param string $type
     * @param int $id
     * @param int $userId
     * @param string $token
     *
     * @return bool
     */
    public function refresh(string $type, int $id, int $userId, string $token): bool
    {
        return $this->synchronized($type, $id, function (string $key) use ($userId, $token): bool {
            $lock = $this->active(Cache::get($key));
            if (!$this->matches($lock, $userId, $token)) return false;
            $lock['holders'][$token] = now()->addMinutes(self::TTL_MINUTES)->timestamp;
            Cache::put($key, $lock, now()->addMinutes(self::TTL_MINUTES));
            return true;
        });
    }

    /** Release one document without invalidating a sibling editor owned by the same user.
     *
     * @param string $type
     * @param int $id
     * @param int $userId
     * @param string $token
     *
     * @return bool
     */
    public function release(string $type, int $id, int $userId, string $token): bool
    {
        return $this->synchronized($type, $id, function (string $key) use ($userId, $token): bool {
            $lock = $this->active(Cache::get($key));
            if (!$this->matches($lock, $userId, $token)) return false;
            unset($lock['holders'][$token]);
            $lock['active'] = $lock['holders'] !== [];
            if ($lock['active']) $lock['token'] = array_key_first($lock['holders']);
            Cache::put($key, $lock, now()->addMinutes(self::TTL_MINUTES));
            return true;
        });
    }

    /** Invalidate every holder after an explicitly authorized administrative unlock.
     *
     * @param string $type
     * @param int $id
     *
     * @return array<string, mixed>|null
     */
    public function forceRelease(string $type, int $id): ?array
    {
        return $this->synchronized($type, $id, function (string $key): ?array {
            $lock = $this->active(Cache::get($key));
            if (!$lock) return null;
            $lock['active'] = false; $lock['holders'] = [];
            Cache::put($key, $lock, now()->addMinutes(self::TTL_MINUTES));
            return $this->expose($lock);
        });
    }

    /** Verify the active owner token while serializing against release and takeover.
     *
     * @param string $type
     * @param int $id
     * @param int $userId
     * @param string $token
     *
     * @return bool
     */
    public function canSave(string $type, int $id, int $userId, string $token): bool
    {
        return $this->synchronized($type, $id, fn (string $key): bool => $this->matches(Cache::get($key), $userId, $token));
    }

    /** Prune expired holders and adapt existing cached leases without changing their valid token.
     *
     * @param mixed $lock
     *
     * @return array<string, mixed>|null
     */
    private function active(mixed $lock): ?array
    {
        if (!is_array($lock) || !($lock['active'] ?? true) || !is_string($lock['token'] ?? null)) return null;
        $holders = $lock['holders'] ?? [$lock['token'] => now()->addMinutes(self::TTL_MINUTES)->timestamp];
        $lock['holders'] = array_filter($holders, fn ($expires): bool => (int) $expires > now()->timestamp);
        if ($lock['holders'] === []) return null;
        if (!isset($lock['holders'][$lock['token']])) $lock['token'] = array_key_first($lock['holders']);
        return $lock;
    }

    /** Match a live holder and its owner without granting an owner-wide token bypass.
     *
     * @param mixed $lock
     * @param int $userId
     * @param string $token
     *
     * @return bool
     */
    private function matches(mixed $lock, int $userId, string $token): bool
    {
        $lock = $this->active($lock);
        return $lock && (int) $lock['user_id'] === $userId && isset($lock['holders'][$token]);
    }

    /** Omit other documents' credentials from all returned metadata.
     *
     * @param array<string, mixed> $lock
     * @param string|null $token
     *
     * @return array<string, mixed>
     */
    private function expose(array $lock, ?string $token = null): array
    {
        unset($lock['holders']);
        if ($token !== null) $lock['token'] = $token;
        return $lock;
    }

    /** Build a resource-isolated cache key.
     *
     * @param string $type
     * @param int $id
     *
     * @return string
     */
    private function key(string $type, int $id): string
    {
        return 'jobic:taxonomy-edit-lock:' . hash('sha256', $type . ':' . $id);
    }

    /** Execute a lease operation under its cache mutex.
     *
     * @param string $type
     * @param int $id
     * @param callable $callback
     *
     * @return mixed
     * @throws \Illuminate\Contracts\Cache\LockTimeoutException
     */
    private function synchronized(string $type, int $id, callable $callback): mixed
    {
        $key = $this->key($type, $id);
        return Cache::lock($key . ':mutex', 5)->block(3, fn () => $callback($key));
    }
}
