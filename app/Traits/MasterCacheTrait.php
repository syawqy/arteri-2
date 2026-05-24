<?php

namespace App\Traits;

use App\Services\CacheManager;

/**
 * MasterCacheTrait
 * 
 * Provides caching functionality for master data models.
 * Reduces database queries by caching frequently accessed data.
 */
trait MasterCacheTrait
{
    /**
     * Cache manager instance
     * @var CacheManager|null
     */
    protected static ?CacheManager $cacheManager = null;

    /**
     * Get cache manager instance
     *
     * @return CacheManager
     */
    protected function getCacheManager(): CacheManager
    {
        if (self::$cacheManager === null) {
            self::$cacheManager = new CacheManager();
        }
        return self::$cacheManager;
    }

    /**
     * Get all records with caching
     *
     * @return array
     */
    public function getAllCached(): array
    {
        $cacheKey = $this->getAllCacheKey();
        
        return $this->getCacheManager()->getOrSet(
            $cacheKey,
            fn() => $this->findAll(),
            CacheManager::DEFAULT_TTL
        );
    }

    /**
     * Get cache key for all records
     *
     * @return string
     */
    protected function getAllCacheKey(): string
    {
        return CacheManager::getAllKey($this->cachePrefix);
    }

    /**
     * Invalidate cache for this model
     *
     * @return bool
     */
    public function invalidateCache(): bool
    {
        $cacheKey = $this->getAllCacheKey();
        return $this->getCacheManager()->delete($cacheKey);
    }

    /**
     * Invalidate all master data cache
     *
     * @return bool
     */
    public function invalidateAllMasterCache(): bool
    {
        return $this->getCacheManager()->invalidateMasterCache();
    }

    /**
     * Find record by ID with optional caching
     *
     * @param int|string $id
     * @param bool $useCache
     * @return array|null
     */
    public function findCached($id, bool $useCache = true): ?array
    {
        if (!$useCache) {
            return $this->find($id);
        }

        $cacheKey = $this->getItemCacheKey($id);
        
        return $this->getCacheManager()->getOrSet(
            $cacheKey,
            fn() => $this->find($id),
            CacheManager::DEFAULT_TTL
        );
    }

    /**
     * Get cache key for single item
     *
     * @param int|string $id
     * @return string
     */
    protected function getItemCacheKey($id): string
    {
        return CacheManager::getItemKey($this->cachePrefix, $id);
    }

    /**
     * Get dropdown options (formatted for select)
     * 
     * Uses cached data for better performance.
     *
     * @param string $valueField Field name for option value
     * @param string $labelField Field name for option label
     * @return array
     */
    public function getDropdownOptions(string $valueField = 'id', string $labelField = 'nama'): array
    {
        $data = $this->getAllCached();
        
        $options = [];
        foreach ($data as $row) {
            $options[$row[$valueField]] = $row[$labelField] ?? '';
        }
        
        return $options;
    }

    /**
     * Search with caching
     *
     * @param string $keyword
     * @return array
     */
    public function searchCached(string $keyword = ''): array
    {
        // For search, we don't use full caching as search is typically dynamic
        // But we can cache the result for a short period
        $cacheKey = 'search_' . $this->cachePrefix . '_' . md5($keyword);
        
        return $this->getCacheManager()->getOrSet(
            $cacheKey,
            fn() => $this->search($keyword),
            300 // 5 minutes for search results
        );
    }

    /**
     * Insert record and invalidate cache
     *
     * @param array|object|null $row
     * @param bool $returnID
     * @return array|int|string|null|bool
     */
    public function insert($row = null, bool $returnID = true)
    {
        $result = parent::insert($row, $returnID);
        $this->invalidateCache();
        return $result;
    }

    /**
     * Update record and invalidate cache
     *
     * @param int|string|array|null $id
     * @param array|object|null $row
     * @return bool
     */
    public function update($id = null, $row = null): bool
    {
        $result = parent::update($id, $row);

        if ($result) {
            // Invalidate specific item cache
            $cacheKey = $this->getItemCacheKey($id);
            $this->getCacheManager()->delete($cacheKey);
            // Invalidate all cache as well (data may appear in lists)
            $this->invalidateCache();
        }

        return $result;
    }

    /**
     * Delete record and invalidate cache
     *
     * @param int|string|array|null $id
     * @param bool $purge
     * @return BaseResult|bool
     */
    public function delete($id = null, bool $purge = false)
    {
        $result = parent::delete($id, $purge);

        if ($result) {
            // Invalidate specific item cache
            $cacheKey = $this->getItemCacheKey($id);
            $this->getCacheManager()->delete($cacheKey);
            // Invalidate all cache as well
            $this->invalidateCache();
        }

        return $result;
    }
}