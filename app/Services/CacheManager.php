<?php

namespace App\Services;

use CodeIgniter\Cache\CacheFactory;
use Config\Services;

/**
 * CacheManager Service
 * 
 * Provides centralized caching management for master data.
 * Uses 1-hour TTL (3600 seconds) for cache items.
 */
class CacheManager
{
    /**
     * Default TTL for cache items (1 hour)
     */
    public const DEFAULT_TTL = 3600;

    /**
     * Cache key prefixes for different data types
     */
    public const PREFIX_MASTER_KODE = 'master_kode_';
    public const PREFIX_MASTER_LOKASI = 'master_lokasi_';
    public const PREFIX_MASTER_MEDIA = 'master_media_';
    public const PREFIX_MASTER_PENCIPTA = 'master_pencipta_';
    public const PREFIX_MASTER_PENGOLAH = 'master_pengolah_';
    public const PREFIX_ALL = 'master_all_';

    /**
     * @var \CodeIgniter\Cache\CacheInterface
     */
    private $cache;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cache = service('cache');
    }

    /**
     * Get cached data or execute callback and cache the result
     *
     * @param string $key Cache key
     * @param callable $callback Function to execute if cache miss
     * @param int|null $ttl Time to live in seconds (default: 3600)
     * @return mixed
     */
    public function getOrSet(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $ttl = $ttl ?? self::DEFAULT_TTL;
        
        $cached = $this->cache->get($key);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $data = $callback();
        $this->cache->save($key, $data, $ttl);
        
        return $data;
    }

    /**
     * Get cached data
     *
     * @param string $key Cache key
     * @return mixed|null
     */
    public function get(string $key): mixed
    {
        return $this->cache->get($key);
    }

    /**
     * Save data to cache
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int|null $ttl Time to live in seconds
     * @return bool
     */
    public function set(string $key, $data, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? self::DEFAULT_TTL;
        return $this->cache->save($key, $data, $ttl);
    }

    /**
     * Delete cached data
     *
     * @param string $key Cache key
     * @return bool
     */
    public function delete(string $key): bool
    {
        return $this->cache->delete($key);
    }

    /**
     * Invalidate all master data cache
     *
     * @return bool
     */
    public function invalidateMasterCache(): bool
    {
        $prefixes = [
            self::PREFIX_MASTER_KODE,
            self::PREFIX_MASTER_LOKASI,
            self::PREFIX_MASTER_MEDIA,
            self::PREFIX_MASTER_PENCIPTA,
            self::PREFIX_MASTER_PENGOLAH,
            self::PREFIX_ALL,
        ];

        foreach ($prefixes as $prefix) {
            $this->deleteMatching($prefix);
        }

        return true;
    }

    /**
     * Delete cache entries matching a prefix
     *
     * @param string $prefix Cache key prefix
     * @return bool
     */
    public function deleteMatching(string $prefix): bool
    {
        // Note: This works with file handler by deleting matching cache files
        // For other handlers, you may need to maintain an index
        $cacheDir = WRITEPATH . 'cache/';
        
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . $prefix . '*');
            if ($files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
        }
        
        return true;
    }

    /**
     * Get cache key for all items of a type
     *
     * @param string $type Cache type
     * @return string
     */
    public static function getAllKey(string $type): string
    {
        return self::PREFIX_ALL . $type;
    }

    /**
     * Get cache key for single item
     *
     * @param string $type Cache type
     * @param int $id Item ID
     * @return string
     */
    public static function getItemKey(string $type, int $id): string
    {
        return $type . $id;
    }

    /**
     * Check if cache is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->cache->isSupported();
    }

    /**
     * Clean all cache
     *
     * @return bool
     */
    public function clean(): bool
    {
        return $this->cache->clean();
    }

    /**
     * Get cache info
     *
     * @return array
     */
    public function getCacheInfo(): array
    {
        return $this->cache->getMetadata() ?? [];
    }
}