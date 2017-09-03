<?php

namespace PDEBundle\Tests\Controller;

class SecurePageControllerTest extends BaseTestController
{
    /**
     * Non authenticated users have no access to these pages.
     */
    public function testNonAuthenticatedUser()
    {
        $this->client->request('GET', '/account');
        $this->assertContains('Redirecting to /', $this->client->getResponse()->getContent());

        $this->client->request('GET', '/editor');
        $this->assertContains('Redirecting to /', $this->client->getResponse()->getContent());

        $this->client->request('GET', '/admin-panel');
        $this->assertContains('Redirecting to /', $this->client->getResponse()->getContent());
    }

    /**
     * Authenticated users have access to all pages except for the admin panel.
     */
    public function testAuthenticatedUser()
    {
        $this->logInByUsername('user');
        $this->client->request('GET', '/account');
        $this->assertContains('Account and team management', $this->client->getResponse()->getContent());

        $this->client->request('GET', '/editor');
        $this->client->followRedirect();
        // User has no team, gets redirected to account page.
        $this->assertContains('Account and team management', $this->client->getResponse()->getContent());

        $this->client->request('GET', '/admin-panel');
        $this->client->followRedirect();
        $this->assertContains(
            'You are not allowed to access this page.',
            $this->client->getResponse()->getContent()
        );
    }
}