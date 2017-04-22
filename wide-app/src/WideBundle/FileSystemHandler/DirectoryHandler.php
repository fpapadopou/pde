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
            $this->safeCreateDirectory($path, $parentPermissions);
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }

        return ['success' => true];
    }

    /**
     * Clones a the specified directory and its contents. The destination directory name is generated within the
     * function and contains the source directory name.
     *
     * @param $parent
     * @param $directory
     * @return array
     */
    public function cloneDirectory($parent, $directory)
    {
        try {
            $this->canCreateDirectory($parent);
            $path = $parent . DIRECTORY_SEPARATOR . $directory;
            $this->checkDirectoryExists($path);
            $parentPermissions = fileperms($parent);
            $destination = $parent . DIRECTORY_SEPARATOR . $directory . '_clone_' . date('H_i_s');
            $this->safeCreateDirectory($destination, $parentPermissions);
            $files = $this->getFileList($path);
            foreach ($files as $file) {
                $this->safeFileCopy($file['pathname'], $destination . DIRECTORY_SEPARATOR . $file['basename']);
            }
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }
        return ['success' => true];
    }

    /**
     * Creates the directory with the specified path - throws an exception if the operation fails.
     *
     * @param $path
     * @param $permissions
     * @throws \ErrorException
     */
    private function safeCreateDirectory($path, $permissions)
    {
        if (!mkdir($path, $permissions)) {
            $directoryName = pathinfo($path, PATHINFO_BASENAME);
            $this->logger->addError('secureCreateDirectory error - ' . error_get_last()['message']);
            throw new \ErrorException('Failed to create directory ' . $directoryName);
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
     * The contents of the files can be returned in base-64 encoding.
     *
     * @param string $directory
     * @param bool $encode
     * @return array
     */
    public function getFilesContents($directory, $encode = false)
    {
        try {
            $this->checkDirectoryExists($directory);
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }

        $folderModifiedTime = filemtime($directory . DIRECTORY_SEPARATOR . '.');
        $contents = [
            'name' => pathinfo($directory, PATHINFO_BASENAME),
            'files' => []
        ];

        foreach ($this->getFileList($directory) as $file) {
            try {
                $fileContent = $this->readFile($file['pathname']);
                if ($encode === true) {
                    $fileContent = base64_encode($fileContent);
                }
                $contents['files'][] = [
                    'filename' => $file['basename'],
                    'extension' => $file['extension'],
                    'content' => $fileContent
                ];
            } catch (\Exception $exception) {
                return ['success' => false, 'error' => $exception->getMessage()];
            }
            $fileModifiedTime = filemtime($file['pathname']);
            if ($fileModifiedTime > $folderModifiedTime) {
                $folderModifiedTime = $fileModifiedTime;
            }
        }
        $contents['modified'] = $folderModifiedTime;
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
