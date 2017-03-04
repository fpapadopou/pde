<?php

namespace WideBundle\FileSystemUtilities;

use Monolog\Logger;

/**
 * Class StorageManager
 * Handles interactions with the file system.
 * @package WideBundle\FileSystemUtilities
 */
class StorageManager
{
    /** @var string $storageRoot */
    private $storageRoot;

    /** @var Logger $logger */
    private $logger;

    /**
     * StorageManager constructor.
     * @param $storageRoot
     * @param Logger $logger
     * @throws \Exception
     */
    public function __construct($storageRoot, Logger $logger)
    {
        $this->logger = $logger;
        // Create the directory if it does not already exist
        if (!is_dir($storageRoot) && !mkdir($storageRoot, 0755)) {
            throw new \Exception('Failed to create application file system.');
        }

        $this->storageRoot = $storageRoot;
        return $this;

    }

    /**
     * Creates a user's main directory. All workspaces are stored in this folder.
     * @param $directory
     * @return bool
     */
    public function createUserDirectory($directory)
    {
        return mkdir($this->storageRoot . '/' . $directory, 0755);
    }

}