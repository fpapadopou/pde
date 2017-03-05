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
     * Creates the main folder where a team's workspaces are stored. Returns the folder's fullpath.
     *
     * @param $seedString
     * @return string
     * @throws \Exception
     */
    public function createTeamSpace($seedString)
    {
        $folderName = $this->storageRoot . '/' . md5($seedString) . rand(10000, 99999);
        if (!mkdir($folderName)) {
            throw  new \Exception('Failed to create your main directory.');
        }

        return $folderName;
    }

}