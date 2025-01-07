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
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @brief Repository for managing Room entities.
 *
 * The RoomRepository provides methods to query and manipulate Room entities,
 * including updating room states based on sensor data and handling related acquisition systems.
 *
 * @extends ServiceEntityRepository<Room>
 */
class RoomRepository extends ServiceEntityRepository
{
    /**
     * @brief Repository for managing Threshold entities.
     *
     * @var ThresholdRepository
     */
    private ThresholdRepository $thresholdRepository;

    /**
     * @brief HTTP client for making API requests.
     *
     * @var HttpClientInterface
     */
    private HttpClientInterface $httpClient;

    /**
     * @brief Project directory path.
     *
     * @var string
     */
    private string $projectDir;

    /**
     * @brief Directory path for JSON files.
     *
     * @var string
     */
    private string $jsonDirectory;

    /**
     * @brief Constructs the repository with the given dependencies.
     *
     * @param ManagerRegistry       $registry            The manager registry.
     * @param ThresholdRepository   $thresholdRepository Repository to manage Threshold entities.
     * @param HttpClientInterface   $httpClient          HTTP client for API interactions.
     * @param string                $projectDir          The project directory path.
     * @param string                $jsonDirectory       The directory path for JSON files.
     */
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

    /**
     * @brief Finds rooms based on specified criteria.
     *
     * Supports filtering by name (partial match), floor, state, and sensor status.
     *
     * @param array $criteria The criteria for filtering, which can include:
     *                        - 'name': string (partial match)
     *                        - 'floor': string or integer (exact match)
     *                        - 'state': string (exact match)
     *                        - 'sensorStatus': array of strings (IN clause)
     *
     * @return Room[] An array of Room entities matching the criteria.
     */
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



    /**
     * @brief Appends new data to a history JSON file, ensuring no duplicates and limiting to 2016 entries.
     *
     * @param string $filePath The path to the history JSON file.
     * @param array  $newData  The new data to append.
     *
     * @return void
     */
    private function appendToHistory(string $filePath, array $newData): void
    {
        // Load existing history
        if (file_exists($filePath)) {
            $json = file_get_contents($filePath);
            $historyData = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // If decoding fails, reset history
                $historyData = [];
            }
        } else {
            $historyData = [];
        }

        // Ensure $historyData is an indexed array of entries
        if (!is_array($historyData)) {
            $historyData = [];
        }

        // Ensure $newData is an array of objects
        // If it's a single object, transform it into an array
        if (isset($newData['id'])) {
            // $newData appears to be a single object, wrap it in an array
            $newData = [$newData];
        }

        // Extract existing IDs from history
        // Assuming each entry has an 'id' field
        $existingIds = array_column($historyData, 'id');

        // Filter new data to exclude duplicates
        $filteredNewData = array_filter($newData, function($entry) use ($existingIds) {
            return isset($entry['id']) && !in_array($entry['id'], $existingIds, true);
        });

        // Merge filtered new data into history
        $historyData = array_merge($historyData, $filteredNewData);

        // Limit history to 2016 entries
        $maxEntries = 2016;
        if (count($historyData) > $maxEntries) {
            // Remove oldest entries
            $historyData = array_slice($historyData, count($historyData) - $maxEntries);
        }

        // Rewrite the history file with updated data
        file_put_contents($filePath, json_encode($historyData, JSON_PRETTY_PRINT));
    }

    /**
     * @brief Updates JSON data from an API for a given AcquisitionSystem.
     *
     * Fetches data from the external API, updates the live JSON file, and appends to history.
     *
     * @param AcquisitionSystem $acquisitionSystem The AcquisitionSystem entity to update.
     *
     * @return void
     *
     * @throws \RuntimeException If data cannot be retrieved from the API or no data is retrieved.
     */
    public function updateJsonFromApiForAS(AcquisitionSystem $acquisitionSystem): void
    {
        $url = 'https://sae34.k8s.iut-larochelle.fr/api/captures/last';
        $noms = ['temp', 'hum', 'co2'];
        $data = [];
        $sensorName = $acquisitionSystem->getName();

        foreach ($noms as $nom) {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'dbname' => 'sae34bdm1eq2',
                    'username' => 'm1eq2',
                    'userpass' => 'kabxaq-4qopra-quXvit',
                ],
                'query' => [
                    'nom' => $nom,
                    'nomsa' => $sensorName,
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

        // Get location from the first entry of $data
        $localisation = $data[0]['localisation'] ?? 'unknown';

        // Path for the "live" JSON file based on location
        $liveFilePath = $this->jsonDirectory . '/' . $localisation . '.json';

        // Path for the "history" JSON file based on location
        $historyFilePath = $this->jsonDirectory . '/history/' . $localisation . '_history.json';

        // Write the "live" JSON file
        file_put_contents($liveFilePath, json_encode($data, JSON_PRETTY_PRINT));

        // Update the "history" JSON file
        $this->appendToHistory($historyFilePath, $data);
    }

    /**
     * @brief Loads sensor data for a given AcquisitionSystem.
     *
     * Updates JSON data from the API and reads the live JSON file associated with the room.
     *
     * @param AcquisitionSystem $acquisitionSystem The AcquisitionSystem entity to load data for.
     *
     * @return array The sensor data decoded from the JSON file.
     *
     * @throws \RuntimeException If the JSON file is invalid or not found.
     */
    public function loadSensorData(AcquisitionSystem $acquisitionSystem): array
    {
        $this->updateJsonFromApiForAS($acquisitionSystem);

        $room = $acquisitionSystem->getRoom();

        // Path to the JSON file based on the room's name
        $filePath = __DIR__ . '/../../assets/json/' . $room->getName() . '.json';

        if (!file_exists($filePath)) {
            return []; // File not found
        }

        // Read and decode JSON
        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON format in ' . $filePath);
        }

        return $data;
    }

    /**
     * @brief Updates the AcquisitionSystem entity with data from a JSON file.
     *
     * Reads sensor values from a JSON file and updates the AcquisitionSystem's temperature, humidity, and CO2 levels.
     *
     * @param AcquisitionSystem $acquisitionSystem The AcquisitionSystem entity to update.
     *
     * @return void
     *
     * @throws \RuntimeException If data cannot be retrieved or JSON is invalid.
     */
    public function updateAcquisitionSystemFromJson(AcquisitionSystem $acquisitionSystem): void
    {
        // Load data from JSON file
        try {
            $data = $this->loadSensorData($acquisitionSystem);
        } catch (ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|TransportExceptionInterface|ServerExceptionInterface $e) {
            // Handle exceptions silently or log
        }

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
     * @brief Updates the state of a room based on sensor data.
     *
     * Evaluates temperature, humidity, and CO2 levels to determine the room's state.
     * Considers whether it's a heating period or not.
     * Also handles creating maintenance tasks if sensor data is not working.
     *
     * Room State Levels (in order of priority):
     * - CRITICAL (highest priority)
     * - AT_RISK
     * - STABLE
     * - NONE (when no acquisition system)
     *
     * Heating Period: November to April (months 11, 12, 1, 2, 3, 4)
     * Non-Heating Period: May to October (months 5, 6, 7, 8, 9, 10)
     *
     * @param Room $room The Room entity to update.
     *
     * @return void
     */
    public function updateRoomState(Room $room): void
    {
        $acquisitionSystem = $room->getAcquisitionSystem();

        $this->updateAcquisitionSystemFromJson($acquisitionSystem);

        // Check if room has an acquisition system
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

        $state = RoomStateEnum::WAITING; // Default state
        $sensorState = $room->getSensorState();

        if ($temperature == null && $humidity == null && $co2 == null) {
            $state = RoomStateEnum::WAITING;
        }

        // Get thresholds
        $thresholds = $this->thresholdRepository->getDefaultThresholds();

        // Check for aberrant values first
        if ($thresholds->isTemperatureAberrant($temperature) ||
            $thresholds->isHumidityAberrant($humidity) ||
            $thresholds->isCo2Aberrant($co2)) {
            $sensorState = SensorStateEnum::NOT_WORKING;
        }

        else {
            $sensorState = SensorStateEnum::LINKED;
        }

        // Temperature evaluation
        if ($temperature !== null && !$thresholds->isTemperatureAberrant($temperature)) {
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
        if ($co2 !== null && !$thresholds->isCo2Aberrant($co2)) {
            if ($co2 < $thresholds->getCo2CriticalMin() || $co2 > $thresholds->getCo2ErrorMax()) {
                $state = RoomStateEnum::CRITICAL;
            } elseif ($co2 > $thresholds->getCo2WarningMin() && $co2 <= $thresholds->getCo2CriticalMax()) {
                $state = $state !== RoomStateEnum::CRITICAL ? RoomStateEnum::AT_RISK : $state;
            }
        }

        // Humidity evaluation
        if ($humidity !== null && !$thresholds->isHumidityAberrant($humidity)) {
            if ($humidity < $thresholds->getHumCriticalMin()) {
                $state = RoomStateEnum::CRITICAL;
            } elseif ($humidity < $thresholds->getHumWarningMin() || ($humidity > $thresholds->getHumWarningMax() && $humidity <= $thresholds->getHumCriticalMax())) {
                $state = $state !== RoomStateEnum::CRITICAL ? RoomStateEnum::AT_RISK : $state;
            } elseif ($humidity > $thresholds->getHumCriticalMax()) {
                $state = RoomStateEnum::CRITICAL;
            }
        }

        // Create maintenance task if sensor is not working
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

    /**
     * @brief Creates a maintenance task for a technician for a specific room.
     *
     * Checks if a maintenance task already exists for the room; if not, creates a new one.
     *
     * @param Room $room The Room entity to create a maintenance task for.
     *
     * @return void
     */
    private function createTaskForTechnician(Room $room): void
    {
        $entityManager = $this->getEntityManager();

        $existingTask = $entityManager->getRepository(Action::class)->findOneBy([
            'room' => $room,
            'info' => ActionInfoEnum::MAINTENANCE, // Check only maintenance tasks
            'state' => ActionStateEnum::TO_DO, // Check tasks that are not yet completed
        ]);

        if ($existingTask) {
            return; // Task already exists, do nothing
        }

        // Create a new maintenance task if none exists
        $action = new Action();
        $action->setRoom($room);
        $action->setInfo(ActionInfoEnum::MAINTENANCE); // Specific action type
        $action->setState(ActionStateEnum::TO_DO);
        $action->setCreatedAt(new \DateTime());

        $entityManager->persist($action);
        $entityManager->flush();
    }


    /**
     * @brief Counts the number of actions in a specific state.
     *
     * @param string $state The state to count actions for.
     *
     * @return int The number of actions in the specified state.
     */
    public function countByState(string $state): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)') // Select only the count of IDs
            ->where('a.state = :state') // Filter by state
            ->setParameter('state', $state) // Set the state parameter
            ->getQuery()
            ->getSingleScalarResult(); // Get the single scalar result (count)
    }

    /**
     * @brief Finds rooms that do not have an associated AcquisitionSystem.
     *
     * @return Room[] An array of Room entities without an associated AcquisitionSystem.
     */
    public function findRoomsWithoutAS(): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.acquisitionSystem', 'acq') // Change alias from 'as' to 'acq'
            ->where('acq.id IS NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * @brief Finds rooms that have an associated AcquisitionSystem.
     *
     * @return Room[] An array of Room entities with an associated AcquisitionSystem.
     */
    public function findRoomsWithAS(): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.acquisitionSystem', 'acq') // Change alias from 'as' to 'acq'
            ->getQuery()
            ->getResult();
    }

    /**
     * Gets historical data for a room from the JSON files
     * Returns data for temperature, humidity, and CO2 levels
     */
    public function getHistoricalData(Room $room): array
    {
        $historicalDataPath = __DIR__ . '/../../assets/json/historical/' . $room->getName() . '_history.json';
        
        if (!file_exists($historicalDataPath)) {
            // If no historical data exists, return empty arrays
            return [
                'temperature' => [],
                'humidity' => [],
                'co2' => []
            ];
        }

        $jsonData = file_get_contents($historicalDataPath);
        $data = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON format in historical data file');
        }

        return $data;
    }
}
