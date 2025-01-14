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
 * Service responsible for handling sensor data and updating Room states.
 *
 * This version no longer writes the "live" data to a JSON file; instead,
 * it directly persists the fetched data into the database.
 */
class RoomSensorService
{
    /** @var ThresholdRepository */
    private ThresholdRepository $thresholdRepository;

    /** @var HttpClientInterface */
    private HttpClientInterface $httpClient;

    /** @var EntityManagerInterface */
    private EntityManagerInterface $entityManager;

    /**
     * @var string Path to the JSON directory (used only for historical data).
     *             The "live" usage has been removed.
     */
    private string $jsonDirectory;

    /**
     * Constructor.
     *
     * @param ThresholdRepository     $thresholdRepository
     * @param HttpClientInterface     $httpClient
     * @param EntityManagerInterface  $entityManager
     * @param string                  $jsonDirectory   Path to the assets/json directory (only used for history now)
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
     * Returns the EntityManager.
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
     * Fetches sensor data from the external API and directly updates
     * the given AcquisitionSystem entity with the latest values.
     *
     * @param AcquisitionSystem $acquisitionSystem
     */
    public function updateAcquisitionSystemFromApi(AcquisitionSystem $acquisitionSystem): void
    {
        //Fetch live data from the  API
        $sensorData = $this->fetchSensorDataFromApi($acquisitionSystem);

        if (empty($sensorData)) {
            throw new \RuntimeException("No sensor data fetched for acquisition system: " . $acquisitionSystem->getName());
        }
        // Update the AcquisitionSystem with the fetched data
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
                    throw new \RuntimeException("Invalid date format in entry: " . json_encode($entry) . ". Error: " . $e->getMessage());
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
     * Fetches sensor data ("temp", "hum", and "co2") from the external API.
     *
     * @param AcquisitionSystem $acquisitionSystem
     * @return array Combined sensor data from the API
     *
     * @throws \RuntimeException if the external API returns a non-200 status code
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

            // Merge new data into a single array
            $combinedData = array_merge($combinedData, $response->toArray());
        }

        return $combinedData;
    }

    /* ======================================================
     *           ROOM STATE UPDATE & MAINTENANCE
       ====================================================== */

    /**
     * Updates the state of the given room based on sensor data and thresholds.
     * Also handles the creation of a maintenance task if sensor data is not working.
     *
     * Heating Period: November (11) to April (4)
     * Non-Heating Period: May (5) to October (10)
     *
     * @param Room $room
     */
    public function updateRoomState(Room $room): void
    {
        $acquisitionSystem = $room->getAcquisitionSystem();
        if (!$acquisitionSystem) {
            return;
        }
        // Update the AcquisitionSystem directly from the API
        $this->updateAcquisitionSystemFromApi($acquisitionSystem);

        // Default state is NO_DATA until proven otherwise
        $state = RoomStateEnum::NO_DATA;

        // Retrieve sensor values
        $temperature = $acquisitionSystem->getTemperature();
        $humidity    = $acquisitionSystem->getHumidity();
        $co2         = $acquisitionSystem->getCo2();

        // Determine if we are in the heating period (month >= 11 or <= 4)
        $currentMonth = (int)(new \DateTime())->format('m');
        $isHeatingPeriod = ($currentMonth >= 11 || $currentMonth <= 4);

        $thresholds = $this->thresholdRepository->getDefaultThresholds();

        // 1. Determine sensor state
        $sensorState = SensorStateEnum::LINKED;
        if (
            ($temperature === null && $humidity === null && $co2 === null)
            || $thresholds->isTemperatureAberrant($temperature)
            || $thresholds->isHumidityAberrant($humidity)
            || $thresholds->isCo2Aberrant($co2)
        ) {
            $sensorState = SensorStateEnum::NOT_WORKING;
        }

        // 2. Evaluate temperature
        if ($temperature !== null && !$thresholds->isTemperatureAberrant($temperature)) {
            if ($isHeatingPeriod) {
                // Check critical vs warning for heating period
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

        // 3. Evaluate CO2
        if ($co2 !== null && !$thresholds->isCo2Aberrant($co2)) {
            if ($co2 < $thresholds->getCo2CriticalMin() || $co2 > $thresholds->getCo2ErrorMax()) {
                $state = RoomStateEnum::CRITICAL;
            } elseif ($co2 > $thresholds->getCo2WarningMin() && $co2 <= $thresholds->getCo2CriticalMax()) {
                if ($state !== RoomStateEnum::CRITICAL) {
                    $state = RoomStateEnum::AT_RISK;
                }
            }
        }

        // 4. Evaluate humidity
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

        // If we actually have sensor data (temp/hum/co2) but haven't triggered any risk,
        // set the state to STABLE
        if (
            $state === RoomStateEnum::NO_DATA &&
            ($temperature !== null || $humidity !== null || $co2 !== null)
        ) {
            $state = RoomStateEnum::STABLE;
        }

        // 5. If sensor is NOT_WORKING but we do have data, create a maintenance task
        if ($sensorState === SensorStateEnum::NOT_WORKING && $state !== RoomStateEnum::NO_DATA) {
            $this->createTaskForTechnician($room);
        }

        // 6. Final update of the Room and AcquisitionSystem
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
     * Creates a maintenance task for a technician if none already exists
     * (i.e., if there's no existing TO_DO maintenance task).
     *
     * @param Room $room
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
            // A maintenance task is already present
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
     * Fetches historical data (week or month) from the external API
     * and stores it in a local JSON file (in the /history directory).
     *
     * @param AcquisitionSystem $acquisitionSystem
     * @param string            $range
     * @return array            The array of data fetched
     *
     * @throws \RuntimeException if any API error occurs
     */
    public function fetchHistoricalDataFromApi(AcquisitionSystem $acquisitionSystem, string $range): array
    {
        $url = 'https://sae34.k8s.iut-larochelle.fr/api/captures/interval';
        $sensorTypes = ['temp', 'hum', 'co2'];
        $now = new \DateTime();
        $startDate = clone $now;
        $now->modify('+1 day');

        // Determine date range (7 days for 'week', 30 days for 'month' or default)
        switch ($range) {
            case 'week':
                $startDate->modify('-7 days');
                break;
            case 'month':
                $startDate->modify('-30 days');
                break;
            default:
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

        // We keep the history functionality as-is
        $historyFile = $this->jsonDirectory . '/history/' . $acquisitionSystem->getRoom()->getName() . '_history.json';
        file_put_contents($historyFile, json_encode($data, JSON_PRETTY_PRINT));

        return $data;
    }
}
