<?php

namespace WideBundle\Traits;

/**
 * Class FileHelperTrait
 * @package WideBundle\Traits
 */
trait FileHelperTrait
{
    /**
     * Returns the extension of a file. If a file has multiple extensions (e.g. .inc.php) all the extensions
     * will be returned. Files with no extension are allowed. The file parameter can either be the basename or
     * the full path of the file.
     *
     * @param $file
     * @return string
     */
    function getFileExtension($file)
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
     * Returns the name of the file that matches the requested extension. Only one file must match each time.
     *
     * @param $files
     * @param $extension
     * @return array
     * @throws \ErrorException
     */
    function getFileByExtension($files, $extension)
    {
        $requestedFile = [];
        foreach ($files as $file) {
            if ($this->getFileExtension($file['filename']) == $extension) {
                $requestedFile[] = $file['filename'];
            }
        }
            
        if (count($requestedFile) != 1) {
            throw new \ErrorException("Exactly one .$extension file is needed for this operation.");
        }

        return $requestedFile[0];
    }

    /**
     * Returns the base-64 decoded version of the provided string. If the string was not encoded in the first place,
     * it's returned as is.
     *
     * @param $string
     * @return string
     */
    function base64Decoder($string)
    {
        if ( base64_encode(base64_decode($string, true)) === $string){
            // In this case the input was a valid base-64 encoded string.
            return base64_decode($string);
        }
        // The string was not base-64 encoded, so just return it to the caller.
        return $string;
    }
}
