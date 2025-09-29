<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\WeatherRequest;
use App\Service\Weather\WeatherServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class WeatherController extends AbstractController
{
    #[Route('/weather', name: 'weather_city', methods: ['GET'])]
    public function getWeather(Request $request, WeatherServiceInterface $weatherService, ValidatorInterface $validator): JsonResponse
    {
        $dto = new WeatherRequest(
            $request->query->getString('city'),
            $request->query->getString('country')
        );

        $errors = $validator->validate($dto);
        if (\count($errors) > 0) {
            return $this->json([
                'errors' => (string) $errors,
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

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
