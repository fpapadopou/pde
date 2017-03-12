<?php

namespace WideBundle\FileSystemHandler;

/**
 * Class BaseHandler
 * Provides methods for safe file creation and deletion and filename/directory validation.
 * All handler methods throw exceptions upon failure in order to avoid complicating higher level code.
 *
 * @package WideBundle\FileSystemHandler
 */
class BaseHandler
{
    /**
     * Makes sure the provided directory exists.
     *
     * @param $directory
     * @throws \InvalidArgumentException
     */
    protected function checkDirectoryExists($directory)
    {
        if (!is_dir($directory)) {
            throw new \InvalidArgumentException('Invalid folder path - ' . $directory);
        }
    }

    /**
     * Checks whether the specified directory exists.
     *
     * @param $directory
     * @throws \InvalidArgumentException
     */
    protected function checkNoSuchDirectory($directory)
    {
        if (is_dir($directory)) {
            throw new \InvalidArgumentException('Directory already exists - ' . $directory);
        }
    }

    /**
     * Checks whether the specified path corresponds to an existing file.
     *
     * @param $filepath
     */
    protected function checkFileExists($filepath)
    {
        if (!file_exists($filepath) || is_dir($filepath)) {
            throw new \InvalidArgumentException('No such file.');
        }
    }

    /**
     * Checks whether the specified path does not exist.
     *
     * @param $filepath
     */
    protected function checkNosSuchFile($filepath)
    {
        if (file_exists($filepath)) {
            throw new \InvalidArgumentException('File already exists - ' . $filepath);
        }
    }

    /**
     * Makes sure the specified file name complies with some basic rules. All file names must consist of
     * lowercase and upppercase letters, numbers and underscores.
     *
     * @param string $basename
     * @throw \InvalidArgumentException
     */
    protected function validateFilename($basename)
    {
        $extension = $this->getFileExtension($basename);

        if (!in_array($extension, ['input', 'y', 'l', 'tab.c', 'tab.h', 'yy.c', 'out'])) {
            throw new \InvalidArgumentException("Extension '$extension' is not allowed.");
        }
        $filename = $this->getFilename($basename);
        preg_match('/^\w+$/', $filename, $output);
        // If the filename is valid, only one the regular expression should return only one match
        if (empty($output)) {
            throw new \InvalidArgumentException('File names must consist of [a-z], [A-Z], [0-9] and \'_\' characters.');
        }
    }

    /**
     * Returns the extension of a file. If a file has multiple extensions (e.g. .inc.php) all the extensions
     * will be returned. Files with no extension are allowed. The file parameter can either be the basename or
     * the full path of the file.
     *
     * @param $file
     * @return string
     */
    protected function getFileExtension($file)
    {
        $basename = $file;
        if (strpos($file, DIRECTORY_SEPARATOR) !== false) {
            $basename = pathinfo($file, PATHINFO_BASENAME);
        }

        $dotPosition = strpos($basename, '.');
        if ($dotPosition === false) {
            return '';
        }

        return substr($basename, $dotPosition + 1);
    }

    /**
     * Detects the actual name of a file. In the context of this application a file can have multiple extensions.
     *
     * @param $basename
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function getFilename($basename)
    {
        if ($basename == '') {
            throw new \InvalidArgumentException('Invalid file name.');
        }

        $dotPosition = strpos($basename, '.');
        if ($dotPosition === false) {
            return $basename;
        }

        if ($dotPosition == 0) {
            throw new \InvalidArgumentException('A file name cannot begin with a \'.\'');
        }

        return substr($basename, 0, $dotPosition - 1);
    }

    /**
     * Creates a file. Throws an exception if the operation fails.
     *
     * @param $filepath
     * @param string $content
     * @throws \ErrorException
     */
    protected function safeCreateFile($filepath, $content = '')
    {
        if (file_exists($filepath)) {
            throw new \ErrorException('The file already exists.');
        }

        if (file_put_contents($filepath, $content, LOCK_EX) === false) {
            throw new \ErrorException('Failed to create file.');
        }
    }

    /**
     * Deletes a file. Throws an exception if the operation fails.
     *
     * @param $filepath
     * @return bool
     * @throws \ErrorException
     */
    protected function safeDeleteFile($filepath) {
        if (!unlink($filepath)) {
            throw new \ErrorException('Failed to delete file ' . $filepath);
        }
        return true;
    }

    /**
     * Retrieves the contents and last modification time for a given file.
     *
     * @param $finfoResource
     * @param $filePath
     * @return array
     */
    protected function readFileData($finfoResource, $filePath)
    {
        $mimetype = finfo_file($finfoResource, $filePath);
        $content = 'Binary file.';
        if (substr($mimetype, 0, 4) == 'text') {
            $content = $this->safeReadFileContent($filePath);
        }

        return ['mimetype' => $mimetype, 'content' => $content];
    }

    /**
     * Reads a file contents. Throws exception if the file cannot be read.
     *
     * @param $filePath
     * @return string
     * @throws \ErrorException
     */
    protected function safeReadFileContent($filePath)
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \ErrorException('Failed to read file ' . pathinfo($filePath, PATHINFO_BASENAME));
        }
        return $content;
    }

}
