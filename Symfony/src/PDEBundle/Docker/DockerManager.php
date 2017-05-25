<?php

namespace PDEBundle\Docker;

use Docker\Docker;
use Docker\API\Model\ContainerConfig;
use Docker\Manager\ContainerManager;
use Monolog\Logger;
use splitbrain\PHPArchive\FileInfo;
use VBee\SettingBundle\Manager\SettingDoctrineManager;
use splitbrain\PHPArchive\Tar;
use splitbrain\PHPArchive\Archive;
use Clue\React\Tar\Decoder;
use React\Stream\Stream;
use React\Stream\ReadableStreamInterface;
use React\EventLoop\Factory as EventLoopFactory;

/**
 * Class DockerManager
 * Handles file exchange between the host and the container as well as command execution in containers.
 *
 * @package PDEBundle\Docker
 */
class DockerManager
{
    use \PDEBundle\Traits\FileHelperTrait;

    /** @var string $dockerImage */
    private $dockerImage;

    /** @var Logger $logger */
    private $logger;

    public function __construct(SettingDoctrineManager $settingsManager, Logger $logger)
    {
        $this->dockerImage = $settingsManager->get('docker_image_name');
        $this->logger = $logger;
    }

    /**
     * Creates a new container and runs the specified command in it (fully isolated), then returns the processed files
     * to the caller.
     *
     * @param array $files
     * @param array $command
     * @return array
     */
    public function runContainer($files, $command)
    {
        /** @var Docker $docker */
        $docker = new Docker();
        /** @var ContainerManager $containerManager */
        $containerManager = $docker->getContainerManager();

        // Configure the container, before creating it.
        $workingDirectory = '/tmp/workspace';
        $containerConfig = $this->createContainerConfig($workingDirectory, $command);

        // Create an archive with the input files.
        $tarArchive = $this->createArchive($files);
        if ($tarArchive['success'] !== true) {
            return $tarArchive;
        }
        $tarArchive = $tarArchive['archive'];

        // Strip the stderr redirection from the command, in order to return it to the user.
        $executedCommand = $this->normalizeCommand($command);
        $containerId = null;
        try {
            // Create the container, according to the above configuration.
            $createResult = $containerManager->create($containerConfig);
            $containerId = $createResult->getId();
            // Send the files to the container.
            $containerManager->putArchive($containerId, $tarArchive, ['path' => $workingDirectory]);

            // Notify the container manager of the stream we want to attach to.
            $attachStream = $containerManager->attach($containerId, [
                'stream' => true,
                'stdout' => true,
                'stderr' => true
            ]);

            // Start the container.
            $containerManager->start($containerId);
            $output = '';
            // The command's stderr is redirected to the stdout for flex, bison and gcc.
            $attachStream->onStdout(function ($stdout) use (&$output) {
                $output .= $stdout;
            });
            // When running executables (parsers) though, the output comes directly to the stderr.
            $attachStream->onStderr(function ($stderr) use (&$output){
                $output .= $stderr;
            });
            // Wait for the streams to finish and then trigger the listeners.
            $attachStream->wait();

            // Wait for the container process to complete. The exit code is the status code of the response.
            $waitResponse = $containerManager->wait($containerId);
            $returnValue = $waitResponse->getStatusCode();
            if ($returnValue != 0) {
                $containerManager->remove($containerId);
                return ['success' => false, 'command' => $executedCommand, 'error' => htmlspecialchars($output)];
            }
            // Retrieve the files from the workspace, along with any new files created by the command.
            $archiveStream = $containerManager->getArchive($containerId, ['path' => $workingDirectory])
                ->getBody()->getContents();
            $productFiles = $this->extractFromStream($archiveStream);
            // If `aufs` is selected as Docker's driver, removing the container should be pretty fast, so it's handled here.
            $containerManager->remove($containerId);
        } catch (\Exception $exception) {
            $this->handleException($exception);
            if ($containerId !== null) {
                $containerManager->remove($containerId);
            }
            return ['success' => false, 'error' => 'An error occurred. Please try again.'];
        }

        return [
            'success' => true,
            'command' => $executedCommand,
            'output' => htmlspecialchars($output),
            'files' => $productFiles
        ];
    }

    /**
     * Creates a new container configuration.
     *
     * @param string $workingDirectory
     * @param array $command
     * @return ContainerConfig
     */
    private function createContainerConfig($workingDirectory, $command)
    {
        /** @var ContainerConfig $containerConfig */
        $containerConfig = new ContainerConfig();
        $containerConfig->setImage($this->dockerImage);
        $containerConfig->setWorkingDir($workingDirectory);
        $containerConfig->setCmd($command);
        // The stdout is used in order to get the command's output.
        $containerConfig->setAttachStdout(true);
        $containerConfig->setAttachStderr(true);

        return $containerConfig;
    }

    /**
     * Docker containers can exchange files with their host via TAR archives. This method creates an archive
     * with the files that must be processed in the container.
     * Incoming files need to be base-64 decoded (if necessary), and a new FileInfo object is created in order
     * to add the file to the archive as executable.
     *
     * @param $files
     * @return array
     */
    private function createArchive($files)
    {
        // The following creates a TAR archive directly in memory
        $tarArchive = new Tar();
        $tarArchive->setCompression(9, Archive::COMPRESS_AUTO);
        try {
            $tarArchive->create();
            foreach ($files as $file) {
                $fileInfo = new FileInfo($file['filename']);
                $fileInfo->setMode(0777);
                $tarArchive->addData($fileInfo, $this->base64Decoder($file['content']));
            }
        } catch (\Exception $exception) {
            $this->logger->addError('DockerManager::createArchive - ' . $exception->getMessage());
            return ['success' => false, 'error' => 'An error occurred. Please try again'];
        }

        return ['success' => true, 'archive' => $tarArchive->getArchive()];
    }

    /**
     * The files processed within a container are sent to the host as a TAR stream. The contents of the stream
     * are stored in a temporary file and then the archive is extracted on the fly. This way, there is no need
     * for temporary files/directories maintenance.
     * This can be done thanks to the PHP implementation of the REACT library.
     *
     * @param $streamContent
     * @return array
     */
    private function extractFromStream($streamContent)
    {
        // Store the archive content in a temp in-memory file and get a file descriptor to it.
        $fileHandle = fopen("php://temp", 'r+');
        fputs($fileHandle, $streamContent);
        // Rewind so that the content can be read in the next step.
        rewind($fileHandle);

        $loop = EventLoopFactory::create();
        $stream = new Stream($fileHandle, $loop);
        // Create a decoder, which will emit an 'entry' event for every file in the TAR archive.
        $decoder = new Decoder();

        $files = [];
        $intermediateFiles = [];
        // The files array is inherited (by reference) by the callable listeners of the `entry` and `data` events.
        // An `entry` event is emitted for every file found in the archive.
        $decoder->on('entry', function ($header, ReadableStreamInterface $file) use (&$intermediateFiles){
            $filename = pathinfo($header['filename'], PATHINFO_BASENAME);
            // The working directory (hardcoded name) is returned as a file, so just skip it.
            if ($filename == 'workspace') {
                return;
            }
            $intermediateFiles[$filename] = '';
            // When the Readable stream is processed, a `data` event will be emitted when data is found in the file.
            $file->on('data', function ($chunk) use (&$intermediateFiles, $filename) {
                // If a `data` event gets emitted again for the same file, append the extra content.
                $intermediateFiles[$filename] .= $chunk;
            });
        });
        // Log errors.
        $decoder->on('error', function ($error) {
            $this->logger->addError('DockerManager::extractFromStream - ' . $error);
        });

        // The readable stream is piped into the decoder.
        $stream->pipe($decoder);
        // Running the loop results in the TAR archive being processed by the event listeners declared above.
        $loop->run();

        // File contents must be base-64 encoded, so that the json encoding in the response won't mess them up.
        foreach ($intermediateFiles as $filename => $content) {
            $files[] = [
                'filename' => $filename,
                'content' => base64_encode($content),
                'extension' => $this->getFileExtension($filename)
            ];
        }

        return $files;
    }

    /**
     * Removes any elements from the command that are related to the execution in a container and returns it as a string.
     *
     * @param array $command
     * @return mixed
     */
    private function normalizeCommand($command)
    {
        $command = implode(' ', $command);
        $command = str_replace('/bin/sh -c', '', $command);
        return str_replace(' 2>&1', '', $command);
    }

    /**
     * Handles exception messages logging. Exceptions are triggered during the creation and handling of containers.
     *
     * @param \Exception $exception
     */
    private function handleException(\Exception $exception)
    {
        // Exceptions might contain a response object (if HttpException) with extra info about the error that occurred.
        $exceptionContent = '';
        if (is_a($exception, '\Http\Client\Exception\HttpException')) {
            /** @var \Http\Client\Exception\HttpException $exception */
            $exceptionContent = $exception->getResponse()->getBody()->getContents();
        }
        $this->logger->addError(
            'DockerManager::runContainer - ' . $exception->getMessage() . ' ' . $exceptionContent
        );
    }

}
