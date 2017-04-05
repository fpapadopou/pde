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
     * @return array
     */
    public function createFile($directory, $filename)
    {
        try {
            $this->checkDirectoryExists($directory);
            $this->validateFilename($filename);
            $this->canCreateFile($directory, $filename);
            // Add some content in order to avoid empty files being recognised as octet/stream files
            $date = new \DateTime('now');
            $content = "/*\n * File created on " . $date->format('F jS, Y') . " at " . $date->format('g:i a') . "\n */\n";
            $this->safeCreateFile($directory . DIRECTORY_SEPARATOR . $filename, $content);
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }
        return ['success' => true, 'content' => $content];
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
     * Updates the contents of multiple files.
     *
     * @param $directory
     * @param $files
     * @return array
     */
    public function updateMultipleFiles($directory, $files)
    {
        try {
            $this->checkDirectoryExists($directory);
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => $exception->getMessage()];
        }

        foreach ($files as $file) {
            $result = $this->addFileContent($directory . DIRECTORY_SEPARATOR . $file['filename'], $file['content']);
            if ($result['success'] !== true) {
                return ['success' => false, 'error' => 'Multiple file update failed. ' . $result['error'] . ' Try again.'];
            }
        }
        return ['success' => true];
    }

    /**
     * Updates the contents of a specified file. If a file does not exist, it's created here.
     *
     * @param $filepath
     * @param $content
     * @return array
     */
    public function addFileContent($filepath, $content)
    {
        $filename = pathinfo($filepath, PATHINFO_BASENAME);
        $fileIsBase64Encoded = $this->isBase64EncodedString($content);

        // Check provided file content for invalid encoding.
        if ($fileIsBase64Encoded !== true && !mb_check_encoding($content, 'UTF-8')) {
            return ['success' => false, 'error' => "Invalid file contents provided for $filename."];
        }

        // Replace the file's content.
        if (!file_put_contents($filepath, $this->base64Decoder($content), LOCK_EX)) {
            $this->logger->addError('addFileContent error - ' . error_get_last()['message']);
            return ['success' => false, 'error' => "Failed to add content to $filename file. Try again."];
        }

        return ['success' => true];
    }

    /**
     * Detects whether a string is base-64 encoded or not.
     *
     * @param $string
     * @return bool
     */
    private function isBase64EncodedString($string)
    {
        if ( base64_encode(base64_decode($string, true)) === $string){
            // In this case the input was a valid base-64 encoded string.
            return true;
        }
        return false;
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

    /**
     * Creates a zip with the provided files and returns its content, name and length (used in headers).
     *
     * @param $folderName
     * @param $files
     * @return array
     */
    public function createZipFromFiles($folderName, $files)
    {
        if (empty($files)) {
            return ['success' => false];
        }
        // Create the archive and set its name
        $archive = new \ZipArchive();
        $date = new \DateTime('now');
        $date = $date->format('Y_m_d');
        $archiveName = $folderName . '_' . $date . '.zip';
        $archivePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $archiveName;
        $result = $archive->open($archivePath, \ZipArchive::CREATE);
        if ($result !== true) {
            return ['success' => false];
        }
        // Add the files
        foreach ($files as $file) {
            $archive->addFromString($file['filename'], $file['content']);
        }
        $archive->close();
        $content = file_get_contents($archivePath);
        $contentLength = filesize($archivePath);
        // Delete the temporary file
        if (!unlink($archivePath)) {
            $this->logger->addError('createZipFromFiles error - ' . error_get_last()['message']);
            return ['success' => false];
        }

        return ['success' => true, 'name' => $archiveName, 'content' => $content, 'length' => $contentLength];
    }
}
