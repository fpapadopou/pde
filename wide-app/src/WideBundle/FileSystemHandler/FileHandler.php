<?php

namespace WideBundle\FileSystemHandler;

/**
 * Class FileHandler
 * Low level file operations. Restrictions related to the app context apply.
 *
 * @package WideBundle\FileSystemHandler
 */
class FileHandler extends BaseHandler
{
    /**
     * Creates a file in the specified directory.
     *
     * @param $directory
     * @param $filename
     */
    public function createFile($directory, $filename)
    {
        $this->checkDirectoryExists($directory);
        $this->validateFilename($filename);
        $this->safeCreateFile($directory . DIRECTORY_SEPARATOR . $filename);
    }

    /**
     * Deletes the specified file.
     *
     * @param $filepath
     */
    public function deleteFile($filepath)
    {
        $this->checkFileExists($filepath);
        $this->safeDeleteFile($filepath);
    }

    /**
     * Renames a file.
     *
     * @param $directory
     * @param $currentFilename
     * @param $newFilename
     * @throws \ErrorException
     */
    public function renameFile($directory, $currentFilename, $newFilename)
    {

        $this->validateFilename($newFilename);
        $currentPath = $directory . DIRECTORY_SEPARATOR . $currentFilename;
        $this->checkFileExists($currentPath);

        if (!$this->sameExtension([$currentFilename, $newFilename])) {
            throw new \ErrorException('File cannot be renamed. Extensions don\'t match.');
        }

        $newPath = $directory . DIRECTORY_SEPARATOR . $newFilename;
        $this->checkNoSuchFile($newPath);
        if (!rename($currentPath, $newPath)) {
            throw new \ErrorException("Failed to rename file $currentPath to $newFilename");
        }
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
     * Updates the contents of a specified file.
     *
     * @param $filepath
     * @param $content
     * @throws \ErrorException
     */
    public function addFileContent($filepath, $content)
    {
        $this->checkFileExists($filepath);
        if ($this->getFileExtension($filepath) == 'out') {
            throw new \InvalidArgumentException('Cannot add contents to \'.out\' files.');
        }

        if (!mb_check_encoding($content, 'UTF-8')) {
            throw new \InvalidArgumentException('Invalid file contents.');
        }

        $this->checkFileExists($filepath);
        if (!file_put_contents($filepath, $content)) {
            throw new \ErrorException('Failed to add content to ' . $filepath . ' file.');
        }

    }

}
