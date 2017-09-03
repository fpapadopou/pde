<?php

namespace PDEBundle\Tests\Controller;

class AdminActionControllerTest extends BaseTestController
{
    /**
     * All unauthenticated users trying to access admin resources get redirected to the index page.
     */
    public function testAdminPagesBlocked()
    {
        $routes = [
            ['path' => '/admin/editor/15', 'method' => 'GET'],
            ['path' => '/admin/workspaces/15', 'method' => 'GET'],
            ['path' => '/admin/download/15', 'method' => 'GET'],
            ['path' => '/admin/dummy', 'method' => 'GET'],
            ['path' => '/admin/create-file', 'method' => 'POST'],
            ['path' => '/admin/search', 'method' => 'GET'],
            ['path' => '/admin/log', 'method' => 'GET'],
            ['path' => '/admin/delteam', 'method' => 'DELETE'],
            ['path' => '/admin/backup/15', 'method' => 'GET']
        ];

        foreach ($routes as $route) {
            $this->client->request($route['method'], $route['path']);
            $this->assertContains('Redirecting to /', $this->client->getResponse()->getContent());
        }
    }

    /**
     * All non admin users are redirected to the index page when trying to access admin pages.
     */
    public function testNonAdminUser()
    {
        $this->logInByUsername('user');
        $routes = [
            ['path' => '/admin/editor/15', 'method' => 'GET'],
            ['path' => '/admin/workspaces/15', 'method' => 'GET'],
            ['path' => '/admin/download/15', 'method' => 'GET'],
            ['path' => '/admin/dummy', 'method' => 'GET'],
            ['path' => '/admin/create-file', 'method' => 'POST'],
            ['path' => '/admin/search', 'method' => 'GET'],
            ['path' => '/admin/log', 'method' => 'GET'],
            ['path' => '/admin/delteam', 'method' => 'DELETE'],
            ['path' => '/admin/backup/15', 'method' => 'GET']
        ];

        foreach ($routes as $route) {
            $this->client->request($route['method'], $route['path']);
            $this->assertContains('Redirecting to /', $this->client->getResponse()->getContent());
        }
    }

    /**
     * All non admin users are redirected to the index page when trying to access admin pages.
     */
    public function testAdminUserAccess()
    {
        $this->logInByUsername('admin');

        $this->client->request('GET', '/admin/editor/15');
        $this->assertContains('Team does not exist.', $this->client->getResponse()->getContent());

        $this->client->request('GET', '/admin/workspaces/15');
        $this->assertContains('Cannot find the specified team.', $this->client->getResponse()->getContent());

        $this->client->request('GET', '/admin/download/15');
        $this->assertContains('Team does not exist.', $this->client->getResponse()->getContent());

        $this->client->request('GET', '/admin/dummy');
        $this->assertContains('This function is not implemented.', $this->client->getResponse()->getContent());

        $this->client->request('POST', '/admin/create-file');
        $this->assertContains('"success":false', $this->client->getResponse()->getContent());

        $this->client->request('GET', '/admin/search');
        $this->assertContains('Team Search', $this->client->getResponse()->getContent());

        $this->client->request('GET', '/admin/log');
        $this->assertContains('Not implemented yet.', $this->client->getResponse()->getContent());

        $this->client->request('DELETE', '/admin/delteam');
        $this->assertContains('No such team found', $this->client->getResponse()->getContent());

        $this->client->request('GET', '/admin/backup/15');
        $this->assertContains('Team does not exist.', $this->client->getResponse()->getContent());
    }
}