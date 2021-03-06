<?php

namespace PDEBundle\Controller;

use PDEBundle\Entity\User;
use PDEBundle\Entity\Team;
use PDEBundle\FileSystemHandler\FileHandler;
use PDEBundle\FileSystemHandler\DirectoryHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class WorkspaceActionController
 * Handles all operations within and on a workspace.
 *
 * @package PDEBundle\Controller
 * @Route("/workspace")
 */
class WorkspaceActionController extends BaseController implements SecureResourceInterface, TeamResourceInterface, EditableResourceInterface
{
    /**
     * Creates a new workspace in the directory of the current user's team.
     *
     * @Route("/", name="create_wspsace")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createWorkspaceAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var Team $team */
        $team = $user->getTeam();

        /** @var DirectoryHandler $directoryHandler */
        $directoryHandler = $this->get('pde.directory.handler');

        return new JsonResponse(
            $directoryHandler->createDirectory(
                $team->getTeamFolder(),
                $request->get('workspace')
            )
        );
    }

    /**
     * Clones the specified workspace.
     *
     * @Route("/clone", name="clone_wspsace")
     * @Method("POST")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cloneWorkspaceAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var Team $team */
        $team = $user->getTeam();

        /** @var DirectoryHandler $directoryHandler */
        $directoryHandler = $this->get('pde.directory.handler');

        return new JsonResponse(
            $directoryHandler->cloneDirectory(
                $team->getTeamFolder(),
                $request->get('workspace')
            )
        );
    }

    /**
     * Renames a workspace in the user's team space.
     *
     * @Route("/", name="rename_wspsace")
     * @Method("PUT")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function renameWorkspaceAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var Team $team */
        $team = $user->getTeam();

        /** @var DirectoryHandler $directoryHandler */
        $directoryHandler = $this->get('pde.directory.handler');

        return new JsonResponse(
            $directoryHandler->renameDirectory(
                $team->getTeamFolder(),
                $request->get('current_name'),
                $request->get('new_name')
            )
        );
    }

    /**
     * Deletes a workspace from the current user's team directory.
     *
     * @Route("/", name="delete_wspsace")
     * @Method("DELETE")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteWorkspaceAction(Request $request)
    {
        $user = $this->getUser();
        /** @var Team $team */
        $team = $user->getTeam();

        /** @var DirectoryHandler $directoryHandler */
        $directoryHandler = $this->get('pde.directory.handler');

        return new JsonResponse(
            $directoryHandler->deleteDirectory(
                $team->getTeamFolder() . DIRECTORY_SEPARATOR . $request->get('workspace')
            )
        );
    }

    /**
     *
     * @Route("/save", name="save_wspsace")
     * @Method("PUT")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveWorkspaceAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var Team $team */
        $team = $user->getTeam();

        /** @var FileHandler $fileHandler */
        $fileHandler = $this->get('pde.file.handler');

        return new JsonResponse(
            $fileHandler->updateMultipleFiles(
                $team->getTeamFolder() . DIRECTORY_SEPARATOR . $request->get('workspace'),
                $request->get('files')
            )
        );
    }

    /**
     * @Route("/download", name="download_wspsace")
     * @Method("GET")
     *
     * @param Request $request
     * @return Response
     */
    public function downloadWorkspaceAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var Team $team */
        $team = $user->getTeam();

        try {
            $zipFile = $this->getDownloadContent($team, $request->get('workspace'));
        } catch (\Exception $exception) {
            return new Response($exception->getMessage(), 500);
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-disposition', 'attachment; filename=' . $zipFile['name']);
        $response->headers->set('Content-Length', $zipFile['length']);
        $response->sendHeaders();
        $response->setContent($zipFile['content']);
        return $response;
    }

    /**
     * Creates a new file in the specified workspace.
     *
     * @Route("/file", name="create_file")
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createFileAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var Team $team */
        $team = $user->getTeam();
        /** @var FileHandler $fileHandler */
        $fileHandler = $this->get('pde.file.handler');

        return new JsonResponse(
            $fileHandler->createFile(
                $team->getTeamFolder() . DIRECTORY_SEPARATOR . $request->get('workspace'),
                $request->get('filename')
            )
        );
    }

    /**
     * Handles file upload to the specified workspace.
     *
     * @Route("/upload", name="upload_file")
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadFileAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var Team $team */
        $team = $user->getTeam();
        /** @var FileHandler $fileHandler */
        $fileHandler = $this->get('pde.file.handler');
        $workspace = $request->get('workspace');
        $filename = $request->get('filename');
        $content = $request->get('content');

        $targetDirectory = $team->getTeamFolder() . DIRECTORY_SEPARATOR . $workspace;
        $createResult = $fileHandler->createFile($targetDirectory, $filename, $content);
        if ($createResult['success'] !== true) {
            return new JsonResponse($createResult);
        }

        return new JsonResponse(['success' => true, 'filename' => $filename, 'content' => $content]);
    }

    /**
     * Renames a file in the specified workspace.
     *
     * @Route("/file", name="rename_file")
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function renameFileAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var Team $team */
        $team = $user->getTeam();
        /** @var FileHandler $fileHandler */
        $fileHandler = $this->get('pde.file.handler');

        return new JsonResponse(
            $fileHandler->renameFile(
                $team->getTeamFolder() . DIRECTORY_SEPARATOR . $request->get('workspace'),
                $request->get('current_name'),
                $request->get('new_name')
            )
        );
    }

    /**
     * Deletes a file in the specified workspace.
     *
     * @Route("/file", name="delete_file")
     * @Method({"DELETE"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteFileAction(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var Team $team */
        $team = $user->getTeam();
        /** @var FileHandler $fileHandler */
        $fileHandler = $this->get('pde.file.handler');

        $path = $team->getTeamFolder() . DIRECTORY_SEPARATOR;
        $path .= $request->get('workspace') . DIRECTORY_SEPARATOR . $request->get('filename');
        return new JsonResponse($fileHandler->deleteFile($path));
    }
}