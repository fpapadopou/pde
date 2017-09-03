<?php

namespace PDEBundle\Tests\Controller;

class DefaultControllerTest extends BaseTestController
{
    /**
     * Generic navigation tests.
     */
    public function testPages()
    {
        $this->client->request('GET', '/');
        $this->assertContains('Login', $this->client->getResponse()->getContent());

        $this->client->request('GET', '/about');
        $this->assertContains('About Parser Development Environment', $this->client->getResponse()->getContent());

        $this->client->request('GET', '/help');
        $this->assertContains('Application manual', $this->client->getResponse()->getContent());

    }

}
