<?php

namespace App\Tests;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use function is_array;

class EventControllerTest extends WebTestCase
{
    public function testEventDetails(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', 'http://127.0.0.1:8000/event/details?id=15');

        static::assertResponseIsSuccessful();
        static::assertCount(1, $crawler->filter('.media'));

    }

    public function testAddCommentToEvent(): void
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        $user=$entityManager
            ->getRepository(User::class)
            ->findOneBy(array('email'=>'sergiocantero8@gmail.com'));

        static::assertSame('sergiocantero8@gmail.com', $user->getEmail());


        $client->loginUser($user);
        $client->request('GET', 'http://127.0.0.1:8000/event/details?id=10');


        $crawler=$client->submitForm('Enviar', [
            'form[comment]' => 'Comentario para test',
        ]);

        static::assertResponseIsSuccessful();

    }

    public function testAddEvent(): void
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine')->getManager();
        $user=$entityManager
            ->getRepository(User::class)
            ->findOneBy(array('email'=>'sergiocantero8@gmail.com'));

        static::assertSame('sergiocantero8@gmail.com', $user->getEmail());


        $client->loginUser($user);
        $crawler=$client->request('GET', 'http://127.0.0.1:8000/admin/event/add');

        $buttonCrawlerNode = $crawler->selectButton('Añadir');

        $form = $buttonCrawlerNode->form();

        $form['add_event[title]'] = 'Titulo película';
        $form['add_event[type]']->select('película');
        $form['add_event[description]']='Sinopsis';
        $form['add_event[status]']->tick();

        $client->submit($form);

        static::assertResponseIsSuccessful();

    }


}
