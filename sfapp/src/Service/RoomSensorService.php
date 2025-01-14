<?php

namespace App\Service;

use App\Entity\AcquisitionSystem;
use App\Entity\Room;
use App\Entity\Action;
use App\Repository\ThresholdRepository;
use App\Utils\ActionInfoEnum;
use App\Utils\ActionStateEnum;
use App\Utils\RoomStateEnum;
use App\Utils\SensorStateEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @class RoomSensorService
 * @brief Service responsible for handling sensor data and updating Room states.
 *
 * This service performs the following tasks:
 * - Fetches sensor data from an external API
 * - Updates the AcquisitionSystem entity with the fetched data
 * - Determines the state of a Room based on thresholds and heating periods
 * - Creates maintenance tasks when necessary
 */
class RoomSensorService
{
    /**
     * @var ThresholdRepository $thresholdRepository
     * Repository used to retrieve and check temperature, humidity, and CO2 thresholds.
     */
    private ThresholdRepository $thresholdRepository;

    /**
     * @var HttpClientInterface $httpClient
     * HTTP client used to communicate with the external API.
     */
    private HttpClientInterface $httpClient;

    /**
     * @var EntityManagerInterface $entityManager
     * Entity manager for persisting and retrieving data.
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var string $jsonDirectory
     * Path to the JSON directory (used only for historical data).
     */
    private string $jsonDirectory;

    /**
     * @brief RoomSensorService constructor.
     *
     * @param ThresholdRepository    $thresholdRepository Repository for thresholds
     * @param HttpClientInterface    $httpClient          Symfony HTTP client
     * @param EntityManagerInterface $entityManager       Entity manager
     * @param string                 $jsonDirectory       Path to the JSON directory (for history)
     */
    public function __construct(
        ThresholdRepository $thresholdRepository,
        HttpClientInterface $httpClient,
        EntityManagerInterface $entityManager,
        string $jsonDirectory
    ) {
        $this->thresholdRepository = $thresholdRepository;
        $this->httpClient          = $httpClient;
        $this->entityManager       = $entityManager;
        $this->jsonDirectory       = $jsonDirectory;
    }

    /**
     * @brief Returns the entity manager.
     *
     * @return EntityManagerInterface
     */
    private function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /* ======================================================
     *                SENSOR MANAGEMENT (LIVE)
       ====================================================== */

    /**
     * @brief Updates the given AcquisitionSystem entity with the latest sensor data from the external API.
     *
     * @param AcquisitionSystem $acquisitionSystem The AcquisitionSystem entity to be updated
     *
     * @throws \RuntimeException If no data is returned by the API
     * @return void
     */
    public function updateAcquisitionSystemFromApi(AcquisitionSystem $acquisitionSystem): void
    {
        // Fetch sensor data from the external API
        $sensorData = $this->fetchSensorDataFromApi($acquisitionSystem);

        if (empty($sensorData)) {
            throw new \RuntimeException("No sensor data fetched for acquisition system: " . $acquisitionSystem->getName());
        }

        // Update the AcquisitionSystem entity
        $lastCapturedAt = null;
        foreach ($sensorData as $entry) {
            if (isset($entry['nom'], $entry['valeur'])) {
                switch ($entry['nom']) {
                    case 'temp':
                        $acquisitionSystem->setTemperature((float) $entry['valeur']);
                        break;
                    case 'hum':
                        $acquisitionSystem->setHumidity((int) $entry['valeur']);
                        break;
                    case 'co2':
                        $acquisitionSystem->setCo2((int) $entry['valeur']);
                        break;
                }
            }
            // Handle the most recent capture date
            if (isset($entry['dateCapture'])) {
                try {
                    $captureDate = new \DateTime($entry['dateCapture']);
                    if ($lastCapturedAt === null || $captureDate > $lastCapturedAt) {
                        $lastCapturedAt = $captureDate;
                    }
                } catch (\Exception $e) {
                    throw new \RuntimeException(
                        "Invalid date format in entry: " . json_encode($entry) . ". Error: " . $e->getMessage()
                    );
                }
            }
        }
        if ($lastCapturedAt !== null) {
            $acquisitionSystem->setLastCapturedAt($lastCapturedAt);
        }

        $em = $this->getEntityManager();
        $em->persist($acquisitionSystem);
        $em->flush();
    }

    /**
     * @brief Fetches sensor data (temp, hum, and co2) from the external API.
     *
     * @param AcquisitionSystem $acquisitionSystem The AcquisitionSystem entity containing connection info
     *
     * @throws \RuntimeException If the API returns a non-200 status code
     * @return array Associative array containing sensor data
     */
    private function fetchSensorDataFromApi(AcquisitionSystem $acquisitionSystem): array
    {
        $url = 'https://sae34.k8s.iut-larochelle.fr/api/captures/last';
        $sensorTypes = ['temp', 'hum', 'co2'];
        $sensorName = $acquisitionSystem->getName();
        $dbName = $acquisitionSystem->getDbName();
        $combinedData = [];

        foreach ($sensorTypes as $type) {
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'dbname'   => $dbName,
                    'username' => 'm1eq2',
                    'userpass' => 'kabxaq-4qopra-quXvit',
                ],
                'query' => [
                    'nom'   => $type,
                    'nomsa' => $sensorName,
                ],
            ]);

            if (200 !== $response->getStatusCode()) {
                throw new \RuntimeException(
                    "Unable to fetch sensor data for sensor $sensorName ($type)."
                );
            }

            // Merge the new data into a single array
            $combinedData = array_merge($combinedData, $response->toArray());
        }

        return $combinedData;
    }

    /* ======================================================
     *           ROOM STATE UPDATE & MAINTENANCE
       ====================================================== */

    /**
     * @brief Updates the state of the given room based on sensor data and thresholds.
     *        Also handles creation of maintenance tasks if sensor data is invalid.
     *
     * Heating Period: November (11) to April (4)
     * Non-Heating Period: May (5) to October (10)
     *
     * @param Room $room The Room entity to update
     *
     * @return void
     */
    public function updateRoomState(Room $room): void
    {
        $acquisitionSystem = $room->getAcquisitionSystem();
        if (!$acquisitionSystem) {
            return;
        }

        // 1. Update the AcquisitionSystem directly from the API
        $this->updateAcquisitionSystemFromApi($acquisitionSystem);

        // 2. Default room state
        $state = RoomStateEnum::NO_DATA;

        // 3. Retrieve sensor values
        $temperature = $acquisitionSystem->getTemperature();
        $humidity    = $acquisitionSystem->getHumidity();
        $co2         = $acquisitionSystem->getCo2();

        // 4. Determine if we are in heating period
        $currentMonth = (int)(new \DateTime())->format('m');
        $isHeatingPeriod = ($currentMonth >= 11 || $currentMonth <= 4);

        $thresholds = $this->thresholdRepository->getDefaultThresholds();

        // 5. Determine sensor state
        $sensorState = SensorStateEnum::LINKED;
        if (
            ($temperature === null && $humidity === null && $co2 === null)
            || $thresholds->isTemperatureAberrant($temperature)
            || $thresholds->isHumidityAberrant($humidity)
            || $thresholds->isCo2Aberrant($co2)
        ) {
            $sensorState = SensorStateEnum::NOT_WORKING;
        }

        // 6. Evaluate temperature
        if ($temperature !== null && !$thresholds->isTemperatureAberrant($temperature)) {
            if ($isHeatingPeriod) {
                // Heating period
                if (
                    $temperature < $thresholds->getHeatingTempCriticalMin() ||
                    $temperature > $thresholds->getHeatingTempCriticalMax()
                ) {
                    $state = RoomStateEnum::CRITICAL;
                } elseif (
                    $temperature < $thresholds->getHeatingTempWarningMin() ||
                    $temperature > $thresholds->getHeatingTempWarningMax()
                ) {
                    if ($state !== RoomStateEnum::CRITICAL) {
                        $state = RoomStateEnum::AT_RISK;
                    }
                }
            } else {
                // Non-heating period
                if (
                    $temperature < $thresholds->getNonHeatingTempCriticalMin() ||
                    $temperature > $thresholds->getNonHeatingTempCriticalMax()
                ) {
                    $state = RoomStateEnum::CRITICAL;
                } elseif (
                    $temperature < $thresholds->getNonHeatingTempWarningMin() ||
                    $temperature > $thresholds->getNonHeatingTempWarningMax()
                ) {
                    if ($state !== RoomStateEnum::CRITICAL) {
                        $state = RoomStateEnum::AT_RISK;
                    }
                }
            }
        }

        // 7. Evaluate CO2
        if ($co2 !== null && !$thresholds->isCo2Aberrant($co2)) {
            if ($co2 < $thresholds->getCo2CriticalMin() || $co2 > $thresholds->getCo2ErrorMax()) {
                $state = RoomStateEnum::CRITICAL;
            } elseif ($co2 > $thresholds->getCo2WarningMin() && $co2 <= $thresholds->getCo2CriticalMax()) {
                if ($state !== RoomStateEnum::CRITICAL) {
                    $state = RoomStateEnum::AT_RISK;
                }
            }
        }

        // 8. Evaluate humidity
        if ($humidity !== null && !$thresholds->isHumidityAberrant($humidity)) {
            if ($humidity < $thresholds->getHumCriticalMin()) {
                $state = RoomStateEnum::CRITICAL;
            } elseif (
                $humidity < $thresholds->getHumWarningMin()
                || ($humidity > $thresholds->getHumWarningMax() && $humidity <= $thresholds->getHumCriticalMax())
            ) {
                if ($state !== RoomStateEnum::CRITICAL) {
                    $state = RoomStateEnum::AT_RISK;
                }
            } elseif ($humidity > $thresholds->getHumCriticalMax()) {
                $state = RoomStateEnum::CRITICAL;
            }
        }

        // If we have sensor data but haven't triggered any risk, set to STABLE
        if (
            $state === RoomStateEnum::NO_DATA &&
            ($temperature !== null || $humidity !== null || $co2 !== null)
        ) {
            $state = RoomStateEnum::STABLE;
        }

        // 9. If the sensor is NOT_WORKING but we do have data, create a maintenance task
        if ($sensorState === SensorStateEnum::NOT_WORKING && $state !== RoomStateEnum::NO_DATA) {
            $this->createTaskForTechnician($room);
        }

        // 10. Update Room and AcquisitionSystem
        $utcDateTime = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $room->setLastUpdatedAt($utcDateTime);
        $room->setSensorState($sensorState);
        $acquisitionSystem->setState($sensorState);
        $room->setState($state);

        $em = $this->getEntityManager();
        $em->persist($room);
        $em->flush();
    }

    /**
     * @brief Creates a maintenance task for a technician if none exists (i.e., no TO_DO maintenance task).
     *
     * @param Room $room The Room entity for which to create the task
     *
     * @return void
     */
    private function createTaskForTechnician(Room $room): void
    {
        $em = $this->getEntityManager();

        $existingTask = $em->getRepository(Action::class)->findOneBy([
            'room'  => $room,
            'info'  => ActionInfoEnum::MAINTENANCE,
            'state' => ActionStateEnum::TO_DO,
        ]);

        if ($existingTask) {
            // A maintenance task already exists
            return;
        }

        // Otherwise, create a new one
        $action = new Action();
        $action->setRoom($room);
        $action->setInfo(ActionInfoEnum::MAINTENANCE);
        $action->setState(ActionStateEnum::TO_DO);
        $action->setCreatedAt(new \DateTime());

        $em->persist($action);
        $em->flush();
    }

    /* ======================================================
     *                    HISTORICAL DATA
       ====================================================== */

    /**
     * @brief Fetches historical data (week or month) from the external API
     *        and stores it in a local JSON file (in the /history directory).
     *
     * @param AcquisitionSystem $acquisitionSystem The AcquisitionSystem entity containing connection info
     * @param string            $range            The range of data to fetch ("week" or "month")
     *
     * @throws \RuntimeException If any API error occurs
     * @return array The array of fetched data
     */
    public function fetchHistoricalDataFromApi(AcquisitionSystem $acquisitionSystem, string $range): array
    {
        $url = 'https://sae34.k8s.iut-larochelle.fr/api/captures/interval';
        $sensorTypes = ['temp', 'hum', 'co2'];
        $now = new \DateTime();
        $startDate = clone $now;
        $now->modify('+1 day');

        // Determine date range (7 days for "week", 30 days for "month")
        switch ($range) {
            case 'week':
                $startDate->modify('-7 days');
                break;
            case 'month':
                $startDate->modify('-30 days');
                break;
        }

        $data = [];
        $dbName = $acquisitionSystem->getDbName();

        foreach ($sensorTypes as $type) {
            try {
                $response = $this->httpClient->request('GET', $url, [
                    'headers' => [
                        'dbname'   => $dbName,
                        'username' => 'm1eq2',
                        'userpass' => 'kabxaq-4qopra-quXvit',
                    ],
                    'query' => [
                        'nom'   => $type,
                        'date1' => $startDate->format('Y-m-d'),
                        'date2' => $now->format('Y-m-d'),
                    ],
                ]);

                if ($response->getStatusCode() === 200) {
                    $responseData = $response->toArray();
                    $data[$type] = $responseData;
                } else {
                    throw new \RuntimeException("API error: {$response->getStatusCode()}");
                }
            } catch (\Exception $e) {
                throw new \RuntimeException('Error fetching data: ' . $e->getMessage());
            }
        }

        // $historyFile = $this->jsonDirectory . '/history/' . $acquisitionSystem->getRoom()->getName() . '_history.json';
        // file_put_contents($historyFile, json_encode($data, JSON_PRETTY_PRINT));

        return $data;
    }
}
