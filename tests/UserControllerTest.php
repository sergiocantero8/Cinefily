<?php

namespace App\Tests;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{

    public function testLogin(): void
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        $user=$entityManager
            ->getRepository(User::class)
            ->findOneBy(array('email'=>'sergiocantero8@gmail.com'));

        static::assertSame('sergiocantero8@gmail.com', $user->getEmail());


        $client->loginUser($user);
        $client->request('GET', 'http://127.0.0.1:8000/user/profile');
        static::assertResponseIsSuccessful();
        static::assertSelectorTextContains('h4', 'Sergio');

    }


}
