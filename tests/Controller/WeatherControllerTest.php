<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\WeatherHistory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class WeatherControllerTest extends WebTestCase
{
    public function testReturnsBadRequestWhenValidationFails(): void
    {
        $client = static::createClient();

        $client->request('GET', '/weather', [
            'city' => '',
            'country' => '',
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertStringContainsString('errors', (string) $client->getResponse()->getContent());
    }

    public function testReturnsWeatherDataWhenValidationSucceeds(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $weather = new WeatherHistory('Sofia', 'BG', 23.2);
        $entityManager->persist($weather);
        $entityManager->flush();

        $client->request('GET', '/weather', [
            'city' => 'Sofia',
            'country' => 'BG',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $data = \json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Sofia', $data['city']);
        $this->assertSame('BG', $data['country']);
        $this->assertArrayHasKey('temperature', $data);
    }
}
