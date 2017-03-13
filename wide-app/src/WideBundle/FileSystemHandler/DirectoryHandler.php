<?php

namespace WideBundle\FileSystemHandler;

/**
 * Class DirectoryHandler
 * Low level directory operations. Restrictions related to the app context apply.
 *
 * @package WideBundle\FileSystemHandler
 */
class DirectoryHandler extends BaseHandler
{
    /**
     * Creates a directory, setting the same permissions with the directory's parent.
     *
     * @param $parent
     * @param $directory
     * @throws \ErrorException
     */
    public function createDirectory($parent, $directory)
    {
        $path = $parent . DIRECTORY_SEPARATOR . $directory;
        $this->checkNoSuchDirectory($path);
        $parentPermissions = fileperms($parent);
        if (!mkdir($path, $parentPermissions)) {
            throw new \ErrorException('Failed to create directory ' . $path);
        }
    }

    /**
     * Validates the basename of a directory
     *
     * @param $directory
     * @throws \InvalidArgumentException
     */
    private function validateDirectoryName($directory)
    {
        preg_match('/^\w+$/', $directory, $output);
        // If the directory name is valid, the regular expression should return only one match
        if (empty($output)) {
            throw new \InvalidArgumentException(
                'Directory names must consist of [a-z], [A-Z], [0-9] and \'_\' characters.'
            );
        }
    }

    /**
     * Deletes a folder and its contents recursively.
     *
     * @param $directory
     * @throws \InvalidArgumentException|\ErrorException
     */
    public function deleteDirectory($directory)
    {
        $this->checkDirectoryExists($directory);

        // Iterate over the contents of the provided directory, ignoring the dot (unix) folders
        $directoryHandle = new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS);
        // If a folder is found in the iterator, descend into the folder and delete its files
        foreach ($directoryHandle as $element) {
            $path = $element->getPathname();
            if (is_dir($path)) {
                $this->deleteDirectory($path);
                continue;
            }
            $this->safeDeleteFile($path);
        }
        $this->safeDeleteDirectory($directory);
    }

    /**
     * Deletes a directory. Throws an exception if the operation fails.
     *
     * @param $path
     * @throws \ErrorException
     */
    private function safeDeleteDirectory($path) {
        if (!rmdir($path)) {
            throw new \ErrorException('Failed to delete directory ' . $path);
        }
    }

    /**
     * Returns a list of the subfolders of a given directory.
     *
     * @param $directory
     * @return array
     */
    public function getSubdiretoriesList($directory)
    {
        $this->checkDirectoryExists($directory);

        $contents = [];
        /** @var \FilesystemIterator $iterator */
        $iterator = new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);
        foreach ($iterator as $path) {
            /** @var \SplFileInfo $path */
            if ($path->isFile()) {
                continue;
            }
            $contents[] = $path->getPathname();
        }
        return $contents;
    }

    /**
     * Reads the files of the provided directory. Subdirectories are ignored.
     *
     * @param $directory
     * @return array
     */
    public function getDirectoryContents($directory)
    {
        $fileIterator = new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);
        $contents = [
            'name' => pathinfo($directory, PATHINFO_BASENAME),
            'modified' => filemtime($directory . DIRECTORY_SEPARATOR . '.'),
            'files' => []
        ];

        $finfo = finfo_open(FILEINFO_MIME);
        foreach ($fileIterator as $iterator) {
            if ($iterator->isDir()) {
                continue;
            }
            $contents['files'][] = array_merge(
                [
                    'filename' => $iterator->getFilename(),
                    'extension' => $iterator->getExtension(),
                    'modified' => filemtime($iterator)
                ],
                $this->readFileData($finfo, $iterator->getPathName())
            );
        }
        finfo_close($finfo);
        return $contents;
    }

    /**
     * Renames a directory.
     *
     * @param string $parent
     * @param string $currentName
     * @param string $newName
     * @throws \ErrorException
     */
    public function renameDirectory($parent, $currentName, $newName)
    {
        $currentPath = $parent . DIRECTORY_SEPARATOR . $currentName;
        $this->checkDirectoryExists($currentPath);
        $newPath = $parent . DIRECTORY_SEPARATOR . $newName;
        $this->checkNoSuchDirectory($newPath);

        if (!rename($currentPath, $newPath)) {
            throw new \ErrorException('Failed to rename ' . $currentPath . ' directory to '  . $newPath);
        }
    }

}
