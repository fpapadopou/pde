<?php

namespace WideBundle\FileSystemHandler;

use Monolog\Logger;
use VBee\SettingBundle\Manager\SettingDoctrineManager;

/**
 * Class BaseHandler
 * Provides methods for safe file creation and deletion and filename/directory validation.
 *
 * @package WideBundle\FileSystemHandler
 */
class BaseHandler
{
    /** @var Logger $logger */
    protected $logger;

    /** @var int $maxDirectoryFiles */
    protected $maxDirectoryFiles;

    /** @var int $maxSubDirectories */
    protected $maxSubDirectories;

    /**
     * BaseHandler constructor.
     *
     * @param Logger $logger
     * @param SettingDoctrineManager $settingsManager
     */
    public function __construct(Logger $logger, SettingDoctrineManager $settingsManager)
    {
        $this->logger = $logger;
        $this->maxDirectoryFiles = $settingsManager->get('max_workspace_files');
        $this->maxSubDirectories = $settingsManager->get('max_team_workspaces');
    }

    /**
     * Makes sure the provided directory exists.
     *
     * @param $directory
     * @throws \InvalidArgumentException
     */
    protected function checkDirectoryExists($directory)
    {
        if (!is_dir($directory)) {
            throw new \InvalidArgumentException('Directory does not exist.');
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
            throw new \InvalidArgumentException('Directory already exists.');
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
            $filename = pathinfo($filepath, PATHINFO_BASENAME);
            throw new \InvalidArgumentException("File $filename does not exist.");
        }
    }

    /**
     * Checks whether the specified path does not exist.
     *
     * @param $filepath
     */
    protected function checkNoSuchFile($filepath)
    {
        if (file_exists($filepath)) {
            $filename = pathinfo($filepath, PATHINFO_BASENAME);
            throw new \InvalidArgumentException("File $filename already exists.");
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

        $allowedExtensions = ['input', 'y', 'l', 'tab.c', 'tab.h', 'yy.c', 'out'];
        if (!in_array($extension, $allowedExtensions)) {
            throw new \InvalidArgumentException(
                "Extension '$extension' is not allowed. Use one of '" . implode('\', \'', $allowedExtensions) . '\''
            );
        }
        $filename = $this->getFilename($basename);
        preg_match('/^\w+$/', $filename, $output);
        // If the filename is valid, the regular expression should return only one match
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

        if (file_put_contents($filepath, $this->base64Decoder($content), LOCK_EX) === false) {
            $this->logger->addError('safeCreateFile error - ' . error_get_last()['message']);
            throw new \ErrorException('Failed to create file ' . pathinfo($filepath, PATHINFO_BASENAME));
        }
    }

    /**
     * Returns the base-64 decoded version of the provided string. If the string was not encoded in the first place,
     * it's returned as is.
     *
     * @param $string
     * @return string
     */
    protected function base64Decoder($string)
    {
        if ( base64_encode(base64_decode($string, true)) === $string){
            // In this case the input was a valid base-64 encoded string.
            return base64_decode($string);
        }
        // The string was not base-64 encoded, so just return it to the caller.
        return $string;
    }

    /**
     * Deletes a file. Throws an exception if the operation fails.
     *
     * @param $filepath
     * @throws \ErrorException
     */
    protected function safeDeleteFile($filepath) {
        if (!unlink($filepath)) {
            $this->logger->addError('safeDeleteFile error - ' . error_get_last()['message']);
            throw new \ErrorException('Failed to delete file ' . pathinfo($filepath, PATHINFO_BASENAME));
        }
    }

    /**
     * Reads text files, rejects binary and resource files.
     *
     * @param $filepath
     * @return string
     */
    protected function readTextFile($filepath)
    {
        $fileInfoResource = finfo_open(FILEINFO_MIME);
        $mimeType = finfo_file($fileInfoResource, $filepath);
        finfo_close($fileInfoResource);

        if (substr($mimeType, 0, 4) != 'text') {
            return 'Binary file.';
        }
        return $this->binarySafeReadFile($filepath);
    }

    /**
     * Reads binary files.
     *
     * @param $filepath
     * @return string
     */
    protected function readFile($filepath)
    {
        return $this->binarySafeReadFile($filepath);
    }

    /**
     * Binary safe read file operation. Can open binary files in both *nix and Windows systems.
     *
     * @param $filepath
     * @return string
     * @throws \ErrorException
     */
    protected function binarySafeReadFile($filepath)
    {
        $this->checkFileExists($filepath);
        // `b` is for binary files in Windows, which distinguish binary from other files
        /** @var resource $handle */
        $handle = fopen($filepath, 'rb');
        if ($handle === false) {
            $this->logger->addError('binarySafeReadFile error - ' . error_get_last()['message']);
            throw new \ErrorException('Failed to open file - ' . pathinfo($filepath, PATHINFO_BASENAME));
        }
        $content = fread($handle, filesize($filepath));
        if ($content === false) {
            $this->logger->addError('binarySafeReadFile error - ' . error_get_last()['message']);
            throw new \ErrorException('Failed to read file - ' . pathinfo($filepath, PATHINFO_BASENAME));
        }
        if (!fclose($handle)) {
            $this->logger->addError('binarySafeReadFile error - ' . error_get_last()['message']);
        }
        return $content;
    }

    /**
     * Throws an exception if file creation limits are reached. A workspace can't have more than 10 files in total,
     * .out files can't be manually created only one of each of the allowed extensions can exist, except for .input
     * files.
     *
     * @param $directory
     * @param $filename
     * @throws \InvalidArgumentException|\ErrorException
     */
    protected function canCreateFile($directory, $filename)
    {
        $extension = $this->getFileExtension($filename);
        if (!in_array($extension, ['y', 'l', 'input'])) {
            throw new \InvalidArgumentException('Cannot manually create .' . $extension . ' files.');
        }
        $totalFileCountRegex = $directory . DIRECTORY_SEPARATOR . '*.*';
        $files = glob($totalFileCountRegex);
        if ($files !== false && count($files) > $this->maxDirectoryFiles) {
            throw new \ErrorException('Cannot create any more files in this directory.');
        }
        $extensionRegex = $directory . DIRECTORY_SEPARATOR . '*.' . $extension;
        $files = glob($extensionRegex);
        if ($extension != 'input' && $files !== false && count($files) > 1) {
            throw new \ErrorException("Cannot create any more .$extension files here.");
        }
    }
}
