<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\WeatherHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WeatherHistory>
 */
final class WeatherHistoryRepository extends ServiceEntityRepository implements WeatherHistoryRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeatherHistory::class);
    }

    public function record(string $city, string $countryCode, float $temperature): void
    {
        $history = new WeatherHistory($city, $countryCode, $temperature);

        $this->getEntityManager()->persist($history);
        $this->getEntityManager()->flush();
    }

    /**
     * @return array<array{temp: float, recordedAt: \DateTimeImmutable}>
     */
    public function findLastForCity(string $city, string $countryCode, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('wh')
            ->andWhere('wh.city = :city')
            ->andWhere('wh.countryCode = :cc')
            ->setParameter('city', $city)
            ->setParameter('cc', \strtoupper($countryCode))
            ->orderBy('wh.recordedAt', 'DESC')
            ->setMaxResults($limit);

        $rows = $qb->getQuery()->getResult();

        return \array_map(static function (WeatherHistory $h): array {
            return [
                'temp' => $h->getTemperature(),
                'recordedAt' => $h->getRecordedAt(),
            ];
        }, $rows);
    }

    public function findForToday(string $city, string $countryCode): ?WeatherHistory
    {
        $today = (new \DateTimeImmutable('today'))->setTime(0, 0);

        return $this->createQueryBuilder('wh')
            ->andWhere('wh.city = :city')
            ->andWhere('wh.countryCode = :cc')
            ->andWhere('wh.recordedAt >= :today')
            ->setParameter('city', $city)
            ->setParameter('cc', \strtoupper($countryCode))
            ->setParameter('today', $today)
            ->orderBy('wh.recordedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
