<?php

namespace WideBundle\FileSystemHandler;

use Monolog\Logger;

/**
 * Class FileHandler
 * Low level file operations. Restrictions related to the app context apply.
 *
 * @package WideBundle\FileSystemHandler
 */
class FileHandler extends BaseHandler
{
    /**
     * FileHandler constructor.
     *
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        parent::__construct($logger);
    }

    /**
     * Creates a file in the specified directory.
     *
     * @param $directory
     * @param $filename
     * @return array
     */
    public function createFile($directory, $filename)
    {
        try {
            $this->checkDirectoryExists($directory);
            $this->validateFilename($filename);
            $this->canCreateFile($directory, $filename);
            $this->safeCreateFile($directory . DIRECTORY_SEPARATOR . $filename);
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }
        return ['success' => true];
    }

    /**
     * Deletes the specified file.
     *
     * @param $filepath
     * @return array
     */
    public function deleteFile($filepath)
    {
        try {
            $this->checkFileExists($filepath);
            $this->safeDeleteFile($filepath);
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }
        return ['success' => true];
    }

    /**
     * Renames a file.
     *
     * @param $directory
     * @param $currentFilename
     * @param $newFilename
     * @return array
     */
    public function renameFile($directory, $currentFilename, $newFilename)
    {

        try {
            $this->validateFilename($newFilename);
            $currentPath = $directory . DIRECTORY_SEPARATOR . $currentFilename;
            $this->checkFileExists($currentPath);
            $newPath = $directory . DIRECTORY_SEPARATOR . $newFilename;
            $this->checkNoSuchFile($newPath);
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }
        if (!$this->sameExtension([$currentFilename, $newFilename])) {
            return ['success' => false, 'error' => 'File cannot be renamed. Extensions don\'t match.'];
        }

        if (!rename($currentPath, $newPath)) {
            $this->logger->addError('renameFile error - ' . error_get_last()['message']);
            return ['success' => false, 'error' => "Failed to rename file $currentFilename to $newFilename"];
        }

        return ['success' => true];
    }

    /**
     * Checks if an arbitrary number of files have the same extension. Multiple extensions allowed for each file.
     *
     * @param array $files
     * @return bool
     */
    private function sameExtension(array $files)
    {
        if (empty($files) || count($files) < 2) {
            return false;
        }

        $extension = $this->getFileExtension($files[0]);
        unset($files[0]);
        foreach ($files as $filename) {
            if ($this->getFileExtension($filename) != $extension) {
                return false;
            }
        }

        return true;
    }

    /**
     * Updates the contents of multiple files. The files must already exist. Some of the files can be skipped.
     *
     * @param $directory
     * @param $files
     * @param $ignoredExtensions
     * @return array
     */
    public function updateMultipleFiles($directory, $files, $ignoredExtensions = [])
    {
        try {
            $this->checkDirectoryExists($directory);
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }

        foreach ($files as $file) {
            if (in_array($this->getFileExtension($file), $ignoredExtensions)) {
                continue;
            }
            $result = $this->addFileContent($directory . DIRECTORY_SEPARATOR . $file['name'], $file['content']);
            if ($result['success'] !== true) {
                return ['success' => false, 'error' => 'Multiple file update failed. ' . $result['error'] . ' Try again.'];
            }
        }
        return ['success' => true];
    }

    /**
     * Updates the contents of a specified file.
     *
     * @param $filepath
     * @param $content
     * @return array
     */
    public function addFileContent($filepath, $content)
    {
        try {
            $this->checkFileExists($filepath);
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }

        $filename = pathinfo($filepath, PATHINFO_BASENAME);
        $finfo = finfo_open(FILEINFO_MIME);
        $mimetype = finfo_file($finfo, $filepath);
        finfo_close($finfo);
        if (substr($mimetype, 0, 4) != 'text') {
            return ['success' => false, 'error' => "Cannot add contents to $filename - non text file."];
        }

        if (!mb_check_encoding($content, 'UTF-8')) {
            return ['success' => false, 'error' => "Invalid file contents provided for $filename."];
        }

        if (!file_put_contents($filepath, $content, LOCK_EX)) {
            $this->logger->addError('addFileContent error - ' . error_get_last()['message']);
            return ['success' => false, 'error' => "Failed to add content to $filename file."];
        }

        return ['success' => true];
    }

    /**
     * Returns a file path that is requested by extension.
     *
     * @param $directory
     * @param $extension
     * @return array
     */
    public function getFileByExtension($directory, $extension)
    {
        try {
            $this->checkDirectoryExists($directory);
            $file = glob($directory . DIRECTORY_SEPARATOR . '*.' . $extension);
            if ($file === false) {
                return ['success' => false, 'error' => 'An error occurred, try again.'];
            }
            if (count($file) != 1) {
                return ['success' => false, 'error' => "One .$extension file needed for this operation."];
            }
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }

        return ['success' => true, 'file' => $file[0]];
    }

}
