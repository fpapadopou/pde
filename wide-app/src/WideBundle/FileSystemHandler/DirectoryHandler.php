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
     * @return array
     */
    public function createDirectory($parent, $directory)
    {
        try {
            $this->validateDirectoryName($directory);
            $path = $parent . DIRECTORY_SEPARATOR . $directory;
            $this->checkNoSuchDirectory($path);
            $this->canCreateDirectory($parent);
            $parentPermissions = fileperms($parent);
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }
        if (!mkdir($path, $parentPermissions)) {
            $this->logger->addError('createDirectory error - ' . error_get_last()['message']);
            return ['success' => false, 'error' => 'Failed to create directory ' . $directory];
        }

        return ['success' => true];
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
     * Stores the provided files in the specified directory in the system's temp folder.
     *
     * @param $directory
     * @param $files
     * @return array
     */
    public function storeFilesToTemp($directory, $files)
    {
        $targetPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $directory;
        // Create the temporary folder using the provided directory name
        if (!mkdir($targetPath, 0755, true)) {
            return ['success' => 'false', 'error' => 'Failed to store files to temporary directory. Try again.'];
        }
        foreach ($files as $file) {
            $filePath = $targetPath . $file['filename'];
            try {
                $this->safeCreateFile($filePath, $file['content']);
            } catch (\Exception $exception) {
                $this->deleteDirectory($targetPath);
                return ['success' => false, 'error' => 'Failed to store files to temporary directory. Try again.'];
            }

        }

        return ['success' => true, 'temp-path' => $targetPath];
    }

    /**
     * Deletes a folder and its contents recursively. Wrapper function.
     *
     * @param $directory
     * @return array
     */
    public function deleteDirectory($directory)
    {
        try {
            $this->deleteDirectoryContents($directory);
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }
        return ['success' => true];
    }

    /**
     * Does the actual deletion of a directory and its contents.
     *
     * @param $directory
     */
    private function deleteDirectoryContents($directory)
    {
        $this->checkDirectoryExists($directory);

        // Iterate over the contents of the provided directory, ignoring the dot (unix) folders
        $directoryHandle = new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS);
        // If a folder is found in the iterator, descend into the folder and delete its files
        foreach ($directoryHandle as $element) {
            $path = $element->getPathname();
            if (is_dir($path)) {
                $this->deleteDirectoryContents($path);
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
            $this->logger->addError('safeDeleteDirectory error - ' . error_get_last()['message']);
            throw new \ErrorException('Failed to delete directory ' . $path);
        }
    }

    /**
     * Returns a list of the subfolders of a given directory. For each subfolder, the full path is returned.
     *
     * @param $directory
     * @return array
     */
    public function getSubdiretoriesList($directory)
    {
        try {
            $this->checkDirectoryExists($directory);
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }
        $list = [];
        /** @var \FilesystemIterator $iterator */
        $iterator = new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);
        foreach ($iterator as $path) {
            /** @var \SplFileInfo $path */
            if ($path->isFile()) {
                continue;
            }
            $list[] = $path->getBasename();
        }
        return ['success' => true, 'list' => $list];
    }

    /**
     * Reads the files of the provided directory. Subdirectories are ignored. Non text files' content is replaced with
     * a default text.
     *
     * @param $directory
     * @return array
     */
    public function getTextFilesContents($directory)
    {
        try {
            $this->checkDirectoryExists($directory);
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }

        $contents = [
            'name' => pathinfo($directory, PATHINFO_BASENAME),
            'modified' => filemtime($directory . DIRECTORY_SEPARATOR . '.'),
            'files' => []
        ];

        foreach ($this->getFileList($directory) as $file) {
            try {
                $contents['files'][] = [
                    'filename' => $file['filename'],
                    'extension' => $file['extension'],
                    'content' => $this->readTextFile($file['pathname'])
                ];
            } catch (\Exception $exception) {
                return ['success' => false, 'error' => $exception->getMessage()];
            }
        }
        return ['success' => true, 'contents' => $contents];
    }

    /**
     * Returns list of the provided directory's file and metadata for each file.
     *
     * @param $directory
     * @return array
     */
    private function getFileList($directory)
    {
        $files = [];
        $fileIterator = new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);
        foreach ($fileIterator as $iterator) {
            /** @var \SplFileInfo $iterator */
            if ($iterator->isFile()) {
                $files[] = [
                    'filename' => $iterator->getFilename(),
                    'extension' => $this->getFileExtension($iterator->getBasename()),
                    'basename' => $iterator->getBasename(),
                    'pathname' => $iterator->getPathName()
                ];
            }
        }
        return $files;
    }

    /**
     * Returns a list with the contents of all files (binary safe) of the provided directory.
     *
     * @param $directory
     * @return array
     */
    public function getFilesContents($directory)
    {
        try {
            $this->checkDirectoryExists($directory);
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }

        $contents = [
            'name' => pathinfo($directory, PATHINFO_BASENAME),
            'files' => []
        ];

        foreach ($this->getFileList($directory) as $file) {
            try {
                $contents['files'][] = [
                    'filename' => $file['basename'],
                    'extension' => $file['extension'],
                    'content' => $this->readFile($file['pathname'])
                ];
            } catch (\Exception $exception) {
                return ['success' => false, 'error' => $exception->getMessage()];
            }
        }
        return ['success' => true, 'contents' => $contents];
    }

    /**
     * Renames a directory.
     *
     * @param $parent
     * @param $currentName
     * @param $newName
     * @return array
     */
    public function renameDirectory($parent, $currentName, $newName)
    {
        try {
            $currentPath = $parent . DIRECTORY_SEPARATOR . $currentName;
            $this->checkDirectoryExists($currentPath);
            $newPath = $parent . DIRECTORY_SEPARATOR . $newName;
            $this->validateDirectoryName($newName);
            $this->checkNoSuchDirectory($newPath);
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }

        if (!rename($currentPath, $newPath)) {
            $this->logger->addError('renameDirectory error - ' . error_get_last()['message']);
            ['success' => false, 'error' => 'Failed to rename ' . $currentName . ' directory to '  . $newName];
        }
        return ['success' => true];
    }

    /**
     * Throws an exception if no more sub-directories can be created.
     *
     * @param $directory
     * @throws \ErrorException
     */
    private function canCreateDirectory($directory)
    {
        /** @var \FilesystemIterator $iterator */
        $iterator = new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);
        if (iterator_count($iterator) > $this->maxSubDirectories) {
            throw new \ErrorException('Cannot create any more directories here.');
        }
    }
}
