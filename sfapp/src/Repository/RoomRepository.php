<?php
//RoomRepository.php
namespace App\Repository;

use App\Entity\AcquisitionSystem;
use App\Entity\Room;
use App\Entity\Action;
use App\Utils\ActionInfoEnum;
use App\Utils\ActionStateEnum;
use App\Utils\RoomStateEnum;
use App\Utils\SensorStateEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Room>
 */
class RoomRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        ThresholdRepository $thresholdRepository,
        HttpClientInterface $httpClient,
        string $projectDir,
        string $jsonDirectory
    ) {
        parent::__construct($registry, Room::class);
        $this->thresholdRepository = $thresholdRepository;
        $this->httpClient = $httpClient;
        $this->projectDir = $projectDir;
        $this->jsonDirectory = $jsonDirectory;
    }

    public function findByCriteria(array $criteria): array
    {
        $queryBuilder = $this->createQueryBuilder('r');

        if (isset($criteria['name']) && !empty($criteria['name'])) {
            $queryBuilder->andWhere('r.name LIKE :name')
                ->setParameter('name', '%' . $criteria['name'] . '%');
        }

        if (isset($criteria['floor'])) {
            $queryBuilder->andWhere('r.floor = :floor')
                ->setParameter('floor', $criteria['floor']);
        }

        if (isset($criteria['state'])) {
            $queryBuilder->andWhere('r.state = :state')
                ->setParameter('state', $criteria['state']);
        }

        if (isset($criteria['sensorStatus']) && is_array($criteria['sensorStatus'])) {
            $queryBuilder->andWhere('r.sensorState IN (:sensorStatus)')
                ->setParameter('sensorStatus', $criteria['sensorStatus']);
        }

        return $queryBuilder->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }



    private function appendToHistory(string $filePath, array $newData): void
    {
        // Charger l'historique existant
        if (file_exists($filePath)) {
            $json = file_get_contents($filePath);
            $historyData = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // En cas de problème de décodage, on réinitialise
                $historyData = [];
            }
        } else {
            $historyData = [];
        }

        // S'assurer que $historyData est un tableau indexé d'entrées
        if (!is_array($historyData)) {
            $historyData = [];
        }

        // S'assurer que $newData est un tableau d'objets
        // Si c'est un seul objet, le transformer en tableau
        if (isset($newData['id'])) {
            // $newData semble être un objet unique, on le met dans un tableau
            $newData = [$newData];
        }

        // Extraire les IDs déjà présents dans l'historique
        // On suppose que chaque entrée a un champ 'id'
        $existingIds = array_column($historyData, 'id');

        // Filtrer les nouvelles données pour ne garder que celles dont l'ID n'est pas déjà dans l'historique
        $filteredNewData = array_filter($newData, function($entry) use ($existingIds) {
            return isset($entry['id']) && !in_array($entry['id'], $existingIds, true);
        });

        // Fusionner les données filtrées dans l'historique
        $historyData = array_merge($historyData, $filteredNewData);

        // Limiter l'historique à 2016 entrées maximum
        $maxEntries = 2016;
        if (count($historyData) > $maxEntries) {
            // On retire les entrées du début (les plus anciennes)
            $historyData = array_slice($historyData, count($historyData) - $maxEntries);
        }

        // Réécrire le fichier avec l'historique mis à jour
        file_put_contents($filePath, json_encode($historyData, JSON_PRETTY_PRINT));
    }

    public function updateJsonFromApiForRoom(Room $room): void
    {
        $url = 'https://sae34.k8s.iut-larochelle.fr/api/captures/last';
        $sensorNames = ['temp', 'hum', 'co2'];
        $data = [];

        foreach ($sensorNames as $sensorName) {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'dbname' => 'sae34bdm1eq2',
                    'username' => 'm1eq2',
                    'userpass' => 'kabxaq-4qopra-quXvit',
                ],
                'query' => [
                    'nom' => $sensorName,
                ]
            ]);

            if (200 !== $response->getStatusCode()) {
                throw new \RuntimeException("Impossible de récupérer les données du capteur $sensorName.");
            }

            $responseData = $response->toArray();
            $data = array_merge($data, $responseData);
        }

        if (empty($data)) {
            throw new \RuntimeException('Aucune donnée récupérée depuis l’API.');
        }

        // Récupérer la localisation depuis la première entrée du tableau $data$
        $localisation = $data[0]['localisation'] ?? 'unknown';

        // Chemin du fichier "live" en se basant sur la localisation
        $liveFilePath = $this->jsonDirectory . '/' . $localisation . '.json';

        // Chemin du fichier "history" en se basant sur la localisation
        $historyFilePath = $this->jsonDirectory . '/history/' . $localisation . '_history.json';

        // Écriture du fichier "live"
        file_put_contents($liveFilePath, json_encode($data, JSON_PRETTY_PRINT));

        // Mise à jour du fichier "history"
        $this->appendToHistory($historyFilePath, $data);
    }


    public function loadSensorData(Room $room): array
    {
        $this->updateRoomState($room);

        // Chemin du fichier JSON basé sur le nom de la salle
        $filePath = __DIR__ . '/../../assets/json/' . $room->getName() . '.json';

        if (!file_exists($filePath)) {
            return []; // Fichier non trouvé
        }

        // Lecture et décodage du JSON
        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON format in ' . $filePath);
        }

        return $data;
    }

    /**
     * Updates the acquisition system data from a JSON file
     *
     * The JSON file should be located in assets/json/ directory
     * and named after the room (e.g., "room1.json")
     *
     * Expected JSON format:
     * [
     *   {"nom": "temp", "valeur": "23.5"},
     *   {"nom": "hum", "valeur": "45"},
     *   {"nom": "co2", "valeur": "800"}
     * ]
     */
    public function updateAcquisitionSystemFromJson(Room $room): void
    {
        // Load data from JSON file
        $this->updateJsonFromApiForRoom($room);
        $data = $this->loadSensorData($room);
        $acquisitionSystem = $room->getAcquisitionSystem();

        if (empty($data)) {
            return;
        }

        // Update sensor values from JSON data
        foreach ($data as $entry) {
            if (isset($entry['nom']) && isset($entry['valeur'])) {
                if ($entry['nom'] === 'temp') {
                    $acquisitionSystem->setTemperature((float) $entry['valeur']);
                } elseif ($entry['nom'] === 'hum') {
                    $acquisitionSystem->setHumidity((int) $entry['valeur']);
                } elseif ($entry['nom'] === 'co2') {
                    $acquisitionSystem->setCo2((int) $entry['valeur']);
                }
            }
        }

        // Persist changes to database
        $entityManager = $this->getEntityManager();
        $entityManager->persist($acquisitionSystem);
        $entityManager->flush();
    }

    /**
     * Updates the room's state based on sensor data (temperature, humidity, CO2)
     *
     * Room State Levels (in order of priority):
     * - CRITICAL (highest priority)
     * - AT_RISK
     * - STABLE
     * - NONE (when no acquisition system)
     *
     * Heating Period: November to April (months 11, 12, 1, 2, 3, 4)
     * Non-Heating Period: May to October (months 5, 6, 7, 8, 9, 10)
     */
    public function updateRoomState(Room $room): void
    {
        // Check if room has an acquisition system
        $acquisitionSystem = $room->getAcquisitionSystem();

        if (!$acquisitionSystem) {
            $room->setState(RoomStateEnum::NONE);
            return;
        }

        // Get current sensor data
        $temperature = $acquisitionSystem->getTemperature();
        $humidity = $acquisitionSystem->getHumidity();
        $co2 = $acquisitionSystem->getCo2();

        // Determine if we're in heating period (November to April)
        $currentMonth = (int)(new \DateTime())->format('m');
        $isHeatingPeriod = $currentMonth >= 11 || $currentMonth <= 4;

        $state = RoomStateEnum::STABLE; // Default state
        $sensorState = $room->getSensorState();

        if ($temperature == null && $humidity == null && $co2 == null) {
            $state = RoomStateEnum::WAITING;
        }

        // Get thresholds
        $thresholds = $this->thresholdRepository->getDefaultThresholds();

        // Temperature evaluation
        if ($temperature !== null) {
            if ($isHeatingPeriod) {
                if ($temperature < $thresholds->getHeatingTempCriticalMin() || $temperature > $thresholds->getHeatingTempCriticalMax()) {
                    $state = RoomStateEnum::CRITICAL;
                } elseif ($temperature < $thresholds->getHeatingTempWarningMin() || $temperature > $thresholds->getHeatingTempWarningMax()) {
                    $state = $state !== RoomStateEnum::CRITICAL ? RoomStateEnum::AT_RISK : $state;
                }
            } else {
                if ($temperature < $thresholds->getNonHeatingTempCriticalMin() || $temperature > $thresholds->getNonHeatingTempCriticalMax()) {
                    $state = RoomStateEnum::CRITICAL;
                } elseif ($temperature < $thresholds->getNonHeatingTempWarningMin() || $temperature > $thresholds->getNonHeatingTempWarningMax()) {
                    $state = $state !== RoomStateEnum::CRITICAL ? RoomStateEnum::AT_RISK : $state;
                }
            }
        }

        // CO2 evaluation
        if ($co2 !== null) {
            if ($co2 < $thresholds->getCo2CriticalMin() || $co2 > $thresholds->getCo2ErrorMax()) {
                $state = RoomStateEnum::CRITICAL;
            } elseif ($co2 > $thresholds->getCo2WarningMin() && $co2 <= $thresholds->getCo2CriticalMax()) {
                $state = $state !== RoomStateEnum::CRITICAL ? RoomStateEnum::AT_RISK : $state;
            }
        }

        // Humidity evaluation
        if ($humidity !== null) {
            if ($humidity < $thresholds->getHumCriticalMin()) {
                $state = RoomStateEnum::CRITICAL;
            } elseif ($humidity < $thresholds->getHumWarningMin() || ($humidity > $thresholds->getHumWarningMax() && $humidity <= $thresholds->getHumCriticalMax())) {
                $state = $state !== RoomStateEnum::CRITICAL ? RoomStateEnum::AT_RISK : $state;
            } elseif ($humidity > $thresholds->getHumCriticalMax()) {
                $state = RoomStateEnum::CRITICAL;
            }
        }

        if ($sensorState === SensorStateEnum::NOT_WORKING) {
            $this->createTaskForTechnician($room);
        }

        // Update room state and persist changes
        $room->setSensorState($sensorState);
        $acquisitionSystem->setState($sensorState);
        $room->setState($state);
        $this->getEntityManager()->persist($room);
        $this->getEntityManager()->flush();
    }

    private function createTaskForTechnician(Room $room): void
    {
        $entityManager = $this->getEntityManager();

        $existingTask = $entityManager->getRepository(Action::class)->findOneBy([
            'room' => $room,
            'info' => ActionInfoEnum::MAINTENANCE, // Vérifie uniquement les tâches de maintenance
            'state' => ActionStateEnum::TO_DO, // Vérifie les tâches qui ne sont pas encore terminées
        ]);

        if ($existingTask) {
            return; // Une tâche existe déjà, donc on ne fait rien
        }

        // Crée une nouvelle tâche si aucune n'existe
        $action = new Action();
        $action->setRoom($room);
        $action->setInfo(ActionInfoEnum::MAINTENANCE); // Type d'action spécifique
        $action->setState(ActionStateEnum::TO_DO);
        $action->setCreatedAt(new \DateTime());

        $entityManager->persist($action);
        $entityManager->flush();
    }


    public function countByState(string $state): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)') // Sélectionne uniquement le nombre d'ID
            ->where('a.state = :state') // Ajoute un filtre par état
            ->setParameter('state', $state) // Définit la valeur du paramètre
            ->getQuery()
            ->getSingleScalarResult(); // Récupère le résultat unique (le nombre)
    }



    /**
     * Récupère les salles sans système d'acquisition lié.
     *
     * @return Room[]
     */
    public function findRoomsWithoutAS(): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.acquisitionSystem', 'acq') // Changement de l'alias de 'as' à 'acq'
            ->where('acq.id IS NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les salles avec un système d'acquisition lié.
     *
     * @return Room[]
     */
    public function findRoomsWithAS(): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.acquisitionSystem', 'acq') // Changement de l'alias de 'as' à 'acq'
            ->getQuery()
            ->getResult();
    }
}
