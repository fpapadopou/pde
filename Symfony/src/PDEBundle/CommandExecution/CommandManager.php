<?php

namespace PDEBundle\CommandExecution;

use PDEBundle\Docker\DockerManager;

/**
 * Class CommandManager
 * @package PDEBundle\CommandExecution
 */
class CommandManager
{
    use \PDEBundle\Traits\FileHelperTrait;

    /** @var DockerManager $dockerManager */
    private $dockerManager;

    /**
     * UtilityHandler constructor.
     *
     * @param DockerManager $dockerManager
     */
    public function __construct(DockerManager $dockerManager)
    {
        $this->dockerManager = $dockerManager;
    }

    /**
     * Handles all bison/flex/gcc/simulation operations.
     *
     * @param $files
     * @param $options
     * @param $utility
     * @param $input
     * @return array
     */
    public function runCommand($files, $options, $utility, $input = '')
    {
        try {
            switch ($utility) {
                case 'bison':
                    $operands = $this->findOperands($files, ['y']);
                    $command = $this->buildCommand('bison', $operands, $options);
                    break;
                case 'flex':
                    $operands = $this->findOperands($files, ['l']);
                    $command = $this->buildCommand('flex', $operands, $options);
                    break;
                case 'gcc':
                    $operands = $this->findOperands($files, ['tab.c', 'yy.c']);
                    $command = $this->buildCommand('gcc', $operands, '', '-lfl -o executable.out');
                    break;
                case 'simulation':
                    $executable = $this->findOperands($files, ['out']);
                    $command = $this->buildCommand($executable[0], [$input]);
                    break;
                default:
                    return['success' => false, 'error' => 'Invalid operation requested.'];
            }
        } catch (\Exception $exception) {
            return ['success'=> false, 'error' => $exception->getMessage()];
        }

        return $this->dockerManager->runContainer($files, $command);
    }

    /**
     * Finds the files that have been specified by their extensions. Under normal circumstances,
     * only one file should be returned for each extension.
     *
     * @param $files
     * @param $operandExtensions
     * @return array
     */
    private function findOperands($files, $operandExtensions)
    {
        $operands = [];
        foreach ($operandExtensions as $extension) {
            $operands[] = $this->getFileByExtension($files, $extension);
        }

        return $operands;
    }

    /**
     * Creates a command, using the provided parameters. The final command is created by the container manager from
     * the parts found in the `command` string array.
     *
     * @param $binary
     * @param $operands
     * @param string $preFlags
     * @param string $postFlags
     * @return array
     */
    private function buildCommand($binary, $operands, $preFlags = '', $postFlags = '')
    {
        if (!in_array($binary, ['bison', 'flex', 'gcc'])) {
            // Command for executables (parsers). The binary is called directly with any arguments.
            $command = ['./' . $binary, implode(' ', $operands)];
            return $command;
        }
        // Command for either flex, or bison or gcc. These are executed via shell (/bin/sh) followed by any arguments.
        $command = ['/bin/sh', '-c'];
        $command[2] = "$binary ";
        $command[2] .= "$preFlags ";
        $command[2] .= implode(' ', $operands);
        $command[2] .= " $postFlags ";
        $command[2] .= '2>&1'; // redirect stdout to stderr

        return $command;
    }

}
