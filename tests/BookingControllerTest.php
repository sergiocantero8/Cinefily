<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookingControllerTest extends WebTestCase
{
    public function testSessionBooking(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', 'http://127.0.0.1:8000/booking?session=14');

        static::assertResponseIsSuccessful();
        static::assertCount(3, $crawler->filter('.redBox'));

    }
}
