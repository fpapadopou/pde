<?php

namespace WideBundle\Utility;

use WideBundle\FileSystemHandler\DirectoryHandler;
use WideBundle\FileSystemHandler\FileHandler;

/**
 * Class UtilityHandler
 * @package WideBundle\Utility
 */
class UtilityHandler
{
    /** @var FileHandler $fileHandler */
    private $fileHandler;

    /** @var DirectoryHandler $directoryHandler */
    private $directoryHandler;

    /** @var string $bison */
    private $bison;

    /** @var string $flex */
    private $flex;

    /** @var string $gcc */
    private $gcc;

    /**
     * UtilityHandler constructor.
     * Sets parameters and validates bison/flex/gcc resources.
     *
     * @param FileHandler $fileHandler
     * @param DirectoryHandler $directoryHandler
     * @param array $resourceMap
     * @throws \InvalidArgumentException
     */
    public function __construct(FileHandler $fileHandler, DirectoryHandler $directoryHandler, array $resourceMap)
    {
        if (!is_array($resourceMap) || !empty(array_diff(array_keys($resourceMap), ['bison', 'flex', 'gcc']))) {
            throw  new \InvalidArgumentException('Resource map is invalid. Check system parameters');
        }

        foreach ($resourceMap as $resource => $path) {
            if (!is_executable($path)) {
                throw new \InvalidArgumentException(
                    "Resource $resource error. Check system parameters - " . error_get_last()['message']
                );
            }
            $this->$resource = $path;
        }

        $this->fileHandler = $fileHandler;
        $this->directoryHandler = $directoryHandler;
    }

    /**
     * Handles all bison/flex/gcc/simulation operations.
     *
     * @param $teamFolder
     * @param $workspace
     * @param $files
     * @param $utility
     * @param $input
     * @return array
     */
    public function useUtility($teamFolder, $workspace, $files, $utility, $input = '')
    {
        // The sub path containing the team and workspace names
        $workspaceSubPath = pathinfo($teamFolder, PATHINFO_BASENAME) . DIRECTORY_SEPARATOR . $workspace;
        // Store files in temp directory
        $storeResult = $this->directoryHandler->storeFilesToTemp($workspaceSubPath, $files);
        if ($storeResult['success'] !== true) {
            return $storeResult;
        }
        $workingDirectory = $storeResult['temp-path'];

        switch ($utility) {
            case 'bison':
                $commandResult = $this->execute($this->bison, $workingDirectory, ['y'], '-d');
                break;
            case 'flex':
                $commandResult = $this->execute($this->flex, $workingDirectory, ['l']);
                break;
            case 'gcc':
                $commandResult = $this->execute($this->gcc, $workingDirectory, ['tab.c', 'yy.c'], '', '-lfl');
                break;
            case 'simulation':
                $commandResult = $this->simulate($workingDirectory, $input);
                break;
            default:
                return['success' => false, 'error' => 'Invalid operation requested.'];
        }

        // Read all the files from the working directory and send them to the frontend
        $productFiles = $this->directoryHandler->getFilesContents($workingDirectory);
        $tempDeleteResult = $this->directoryHandler->deleteDirectory($workingDirectory);
        if ($productFiles['success'] !== true || $tempDeleteResult['success'] !== true) {
            return ['success' => false, 'error' => 'An error occurred. Try again or contact an admin.'];
        }
        $productFiles = $productFiles['contents']['files'];
        // Base-64 encode files' contents in order to avoid binaries messing up the JsonResponse.
        foreach ($productFiles as &$file) {
            $file['content'] = base64_encode($file['content']);
        }
        $commandResult['files'] = $productFiles;
        return $commandResult;
    }

    /**
     * Runs any of the bison/flex/gcc binaries with the specified file(s). Finds all operands by their extension, builds
     * the shell command and executes it.
     *
     * @param $binaryPath
     * @param $workspace
     * @param $operandExtensions
     * @param $preFlags
     * @param $postFlags
     * @return array
     */
    private function execute($binaryPath, $workspace, $operandExtensions, $preFlags = '', $postFlags = '')
    {
        $operands = '';
        foreach ($operandExtensions as $extension) {
            $result = $this->fileHandler->getFileByExtension($workspace, $extension);
            if ($result['success'] !== true) {
                return [$result];
            }
            $operands .= $result['file'] . ' ';
        }
        $command = "$binaryPath $preFlags $operands $postFlags";
        $result = $this->runCommandInDirectory($command, $workspace);

        if ($result['returnValue']) {
            return ['success' => false, 'command' => $command, 'error' => $result['output']];
        }
        return ['success' => true, 'command' => $command];
    }

    /**
     * Runs the interpreter (.out file produced by flex and bison) with the specified input.
     *
     * @param $workspace
     * @param $input
     * @return array
     */
    private function simulate($workspace, $input)
    {
        $file = $this->fileHandler->getFileByExtension($workspace, 'out');
        if ($file['success'] !== true) {
            return $file;
        }
        $inputFile = $workspace . DIRECTORY_SEPARATOR . $input;
        if (!file_exists($inputFile)) {
            return ['success' => false, 'error' => 'Input file not found.'];
        }
        $command = $file['file'] . " $inputFile ";
        $result = $this->runCommandInDirectory($command, $workspace);

        if ($result['returnValue']) {
            return ['success' => false, 'command' => $command, 'error' => $result['output']];
        }

        return ['success' => true, 'command' => $command, 'output' => $result['output']];
    }

    /**
     * Changes PHP's current working directory to the specified folder and executes a command there. After the
     * operation is finished, the current working directory is restored.
     *
     * @param $command
     * @param $directory
     * @return array
     */
    private function runCommandInDirectory($command, $directory)
    {
        $output = [];
        $currentDirectory = getcwd();
        chdir($directory);
        $escapedCommand = escapeshellcmd($command) . ' 2>&1';
        exec($escapedCommand, $output, $returnValue);
        chdir($currentDirectory);

        return ['output' => implode("\n", $output), 'returnValue' => $returnValue];
    }
}
