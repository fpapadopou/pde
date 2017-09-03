<?php

namespace PDEBundle\Tests\Controller;

use Doctrine\ORM\EntityManager;
use PDEBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class BaseTestController extends WebTestCase
{
    /** @var Client $client */
    protected $client = null;

    protected function setUp()
    {
        $this->client = static::createClient();
        $this->client->followRedirects();
    }

    protected function logInByUsername($username)
    {
        $this->client->request('GET', '/');

        /** @var EntityManager $entityManager */
        $entityManager = $this->client->getKernel()->getContainer()->get('doctrine.orm.entity_manager');
        /** @var User $user */
        $user = $entityManager->getRepository('PDEBundle:User')->findOneBy(['username' => $username]);

        $session = $this->client->getContainer()->get('session');

        $firewallContext = 'main';

        $token = new UsernamePasswordToken(
            $user,
            null,
            $firewallContext,
            $user->getRoles()
        );

        $session->set('_security_'.$firewallContext, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }
}