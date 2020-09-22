<?php

namespace Laratrust\Checkers\Role;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class LaratrustRoleDefaultChecker extends LaratrustRoleChecker
{
    /**
     * @inheritDoc
     */
    public function currentRoleHasPermission($permission, $requireAll = false)
    {
        if (is_array($permission)) {
            if (empty($permission)) {
                return true;
            }

            foreach ($permission as $permissionName) {
                $hasPermission = $this->currentRoleHasPermission($permissionName);

                if ($hasPermission && !$requireAll) {
                    return true;
                } elseif (!$hasPermission && $requireAll) {
                    return false;
                }
            }

            // If we've made it this far and $requireAll is FALSE, then NONE of the permissions were found.
            // If we've made it this far and $requireAll is TRUE, then ALL of the permissions were found.
            // Return the value of $requireAll.
            return $requireAll;
        }

        foreach ($this->currentRoleCachedPermissions() as $perm) {
            if (Str::is($permission, $perm['name'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function currentRoleFlushCache()
    {
        Cache::forget($this->getPermissionsCacheKey());
    }

    /**
     * Tries to return all the cached permissions of the role.
     * If it can't bring the permissions from the cache,
     * it brings them back from the DB.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function currentRoleCachedPermissions()
    {
        $cacheKey = $this->getPermissionsCacheKey();

        if (!Config::get('laratrust.cache.enabled')) {
            return $this->role->permissions()->get();
        }

        return Cache::remember($cacheKey, Config::get('laratrust.cache.expiration_time', 60), function () {
            return $this->role->permissions()->get()->toArray();
        });
    }
}
