<?php

namespace DarkOak\Contracts\Repository;

interface SettingsRepositoryInterface extends RepositoryInterface
{
    /**
     * Store a new persistent setting in the database.
     *
     * @throws \DarkOak\Exceptions\Model\DataValidationException
     * @throws \DarkOak\Exceptions\Repository\RecordNotFoundException
     */
    public function set(string $key, mixed $value = null);

    /**
     * Retrieve a persistent setting from the database.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Remove a key from the database cache.
     */
    public function forget(string $key);
}
