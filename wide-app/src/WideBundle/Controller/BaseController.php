<?php

namespace WideBundle\Controller;

use WideBundle\Entity\Team;
use WideBundle\FileSystemHandler\FileHandler;
use WideBundle\FileSystemHandler\DirectoryHandler;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class BaseController extends Controller
{
    /**
     * Returns the files of the provided team's workspaces.
     *
     * @param Team $team
     * @return array
     */
    protected function getTeamWorkspaces(Team $team)
    {
        $workspaces = [];
        /** @var DirectoryHandler $directoryHandler */
        $directoryHandler = $this->get('wide.directory.handler');
        $teamFolder = $team->getTeamFolder();
        $directories = $directoryHandler->getSubdiretoriesList($teamFolder);
        if ($directories['success'] !== true) {
            return $directories;
        }

        foreach ($directories['list'] as $workspace) {
            $files = $directoryHandler->getTextFilesContents($teamFolder . DIRECTORY_SEPARATOR . $workspace);
            if ($files['success'] !== true) {
                return $files;
            }
            $workspaces[] = $files['contents'];
        }

        return ['success' => true, 'workspaces' => $workspaces];
    }

    /**
     * Generates a zip with the files of the specified team's workspace.
     *
     * @param Team $team
     * @param $workspace
     * @return array
     * @throws \ErrorException
     */
    protected function getDownloadContent(Team $team, $workspace)
    {
        /** @var DirectoryHandler $directoryHandler */
        $directoryHandler = $this->get('wide.directory.handler');
        /** @var FileHandler $fileHandler */
        $fileHandler = $this->get('wide.file.handler');

        $directory = $team->getTeamFolder() . DIRECTORY_SEPARATOR . $workspace;
        $directoryContents = $directoryHandler->getFilesContents($directory);
        if ($directoryContents['success'] !== true) {
            throw new \ErrorException($directoryContents['error']);
        }
        $zipFile = $fileHandler->createZipFromFiles(
            $directoryContents['contents']['name'],
            $directoryContents['contents']['files']
        );
        if ($zipFile['success'] !== true) {
            throw new \ErrorException('Failed to create zip archive.');
        }

        return $zipFile;
    }
}