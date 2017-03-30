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
        $workspacePath = $teamFolder . DIRECTORY_SEPARATOR . $workspace;
        // First, save the files coming from the editor, then proceed with the operation.
        $result = $this->fileHandler->updateMultipleFiles($workspacePath, $files, ['out']);
        if ($result['success'] !== true) {
            $result['error'] = 'Pre-operation save failed. ' . $result['error'];
            return $result;
        }

        // Copy all workspace contents to temp and execute the command there.
        $teamFolderName = pathinfo($teamFolder, PATHINFO_BASENAME);
        $copyResult = $this->directoryHandler->copyDirectoryToTemp($teamFolderName, $workspace, $workspacePath);
        if ($copyResult['success'] !== true) {
            return $copyResult;
        }
        $workingDirectory = $copyResult['temp-directory'];
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
        // Move all updated files back to the workspace and delete the temporary folder that was used during the operation.
        $copyResult = $this->directoryHandler->copyDirectory($workingDirectory, $workspacePath);
        $tempDeleteResult = $this->directoryHandler->deleteDirectory($workingDirectory);
        if ($copyResult['success'] !== true || $tempDeleteResult['success'] !== true) {
            return ['success' => false, 'error' => 'Something went wrong. Try again.'];
        }

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
        $command = escapeshellcmd("$binaryPath $preFlags $operands $postFlags") . ' 2>&1';
        $result = $this->runCommandInDirectory($command, $workspace);

        if ($result['returnValue']) {
            return ['success' => false, 'error' => $result['output']];
        }
        return ['success' => true];
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
        $command = escapeshellcmd($file['file'] . " $inputFile ") . '2>&1';
        $result = $this->runCommandInDirectory($command, $workspace);

        if ($result['returnValue']) {
            return ['success' => false, 'error' => $result['output']];
        }

        return ['success' => true, 'output' => $result['output']];
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
        exec($command, $output, $returnValue);
        chdir($currentDirectory);

        return ['output' => implode("\n", $output), 'returnValue' => $returnValue];
    }
}
