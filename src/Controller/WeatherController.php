<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class WeatherController extends AbstractController
{
    #[Route('/weather', name: 'weather_city', methods: ['GET'])]
    public function getWeather(Request $request, WeatherService $weatherService): JsonResponse
    {
        $city = $request->query->getString('city');
        $country = $request->query->getString('country');

        $temp = $weatherService->getTemperature($city, $country);

        return $this->json([
            'city' => $city,
            'country' => $country,
            'temperature' => $temp,
        ]);
    }
}
