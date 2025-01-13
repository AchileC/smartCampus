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
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RoomSensorService
{
    private ThresholdRepository $thresholdRepository;
    private HttpClientInterface $httpClient;
    private EntityManagerInterface $entityManager;

    /** @var string Path to the JSON directory in config/services.yaml (assets/json/...) */
    private string $jsonDirectory;


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

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }



    /* ======================================================
     *                 PARTIE GESTION DES CAPTEURS
       ====================================================== */

    /**
     * @brief Fetches "live" sensor data from the external API for the given AcquisitionSystem,
     *        then writes data to both the live JSON file and the history JSON file.
     *
     * @param AcquisitionSystem $acquisitionSystem
     *
     * @throws \RuntimeException if unable to retrieve data or no data
     */
    public function fetchAndStoreLiveDataForAS(AcquisitionSystem $acquisitionSystem): void
    {
        // 1. Récupération depuis l’API
        $sensorData = $this->fetchSensorDataFromApi($acquisitionSystem);

        // 3. Construction des paths (live et history)
        $localisation = $sensorData[0]['localisation'] ?? 'unknown';
        $liveFilePath = $this->jsonDirectory . '/live/' . $localisation . '.json';

        // 4. Écrire le "live"
        $this->writeLiveDataToFile($liveFilePath, $sensorData);
    }

    /**
     * @brief Loads sensor data from the local "live" JSON file for the given AcquisitionSystem.
     *
     * @param AcquisitionSystem $acquisitionSystem
     * @return array Decoded sensor data (empty array if file not found)
     *
     * @throws \RuntimeException If the JSON file is invalid
     */
    public function loadSensorData(AcquisitionSystem $acquisitionSystem): array
    {
        $room = $acquisitionSystem->getRoom();
        $filePath = $this->jsonDirectory . '/live/' . $room->getName() . '.json';

        if (!file_exists($filePath)) {
            return []; // Fichier non trouvé => on renvoie un tableau vide
        }

        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON format in ' . $filePath);
        }

        return $data;
    }

    /**
     * @brief Updates the AcquisitionSystem’s temperature, humidity, and CO2 from the JSON file.
     *
     * @param AcquisitionSystem $acquisitionSystem
     */
    public function updateAcquisitionSystemFromJson(AcquisitionSystem $acquisitionSystem): void
    {
        // 1. Récupération live depuis l’API et stockage local
        $this->fetchAndStoreLiveDataForAS($acquisitionSystem);

        // 2. Lecture du JSON "live"
        try {
            $data = $this->loadSensorData($acquisitionSystem);
        } catch (
        ClientExceptionInterface|
        DecodingExceptionInterface|
        RedirectionExceptionInterface|
        TransportExceptionInterface|
        ServerExceptionInterface $e
        ) {
            $data = [];
        }

        if (empty($data)) {
            return;
        }

        // 3. Mettre à jour l’AcquisitionSystem avec les données
        $lastCapturedAt = null;

        foreach ($data as $entry) {
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

            // Gestion de la date de capture la plus récente
            if (isset($entry['dateCapture'])) {
                try {
                    $captureDate = new \DateTime($entry['dateCapture']);
                    if ($lastCapturedAt === null || $captureDate > $lastCapturedAt) {
                        $lastCapturedAt = $captureDate;
                    }
                } catch (\Exception $e) {
                    // Possibilité de logguer les erreurs de conversion
                }
            }
        }

        if ($lastCapturedAt !== null) {
            $acquisitionSystem->setLastCapturedAt($lastCapturedAt);
        }

        // 4. Sauvegarder en BDD
        $em = $this->getEntityManager();
        $em->persist($acquisitionSystem);
        $em->flush();
    }

    /**
     * @brief Fetches sensor data from the external API for "temp", "hum", and "co2".
     *
     * @param AcquisitionSystem $acquisitionSystem
     * @return array Combined sensor data from the API
     */
    private function fetchSensorDataFromApi(AcquisitionSystem $acquisitionSystem): array
    {
        $url = 'https://sae34.k8s.iut-larochelle.fr/api/captures/last';
        $sensorTypes = ['temp', 'hum', 'co2'];
        $sensorName = $acquisitionSystem->getName();
        $dbName = $acquisitionSystem->getDbName(); // Récupérer dbName dynamique
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
                throw new \RuntimeException("Impossible de récupérer les données du capteur $sensorName ($type).");
            }
            $combinedData = array_merge($combinedData, $response->toArray());
        }

        return $combinedData;
    }

    /**
     * @brief Writes the "live" sensor data to a specified JSON file (overwrite).
     *
     * @param string $filePath Path to the live JSON file
     * @param array  $data     Data to write
     */
    private function writeLiveDataToFile(string $filePath, array $data): void
    {
        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    /* ======================================================
     *         PARTIE MISE À JOUR DE L'ÉTAT DES ROOMS
       ====================================================== */

    /**
     * @brief Updates the state of the given room based on sensor data and thresholds.
     *        Also handles the creation of maintenance tasks if sensor data is not working.
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
            // Pas d’AcquisitionSystem => On peut décider de faire autre chose
            return;
        }

        // Met à jour l’AcquisitionSystem depuis le JSON
        $this->updateAcquisitionSystemFromJson($acquisitionSystem);

        // Par défaut, on considère qu’on n’a pas de data
        $state = RoomStateEnum::NO_DATA;

        // Récupérer les données capteur
        $temperature = $acquisitionSystem->getTemperature();
        $humidity    = $acquisitionSystem->getHumidity();
        $co2         = $acquisitionSystem->getCo2();

        // Vérifier si on est en période de chauffage
        $currentMonth = (int)(new \DateTime())->format('m');
        // Si le mois est >= 11 OU <= 4 => période de chauffage
        $isHeatingPeriod = ($currentMonth >= 11 || $currentMonth <= 4);

        $thresholds = $this->thresholdRepository->getDefaultThresholds();

        // 1. Déterminer l’état des capteurs
        $sensorState = SensorStateEnum::LINKED; // Valeur par défaut
        if (
            $temperature === null && $humidity === null && $co2 === null
            || $thresholds->isTemperatureAberrant($temperature)
            || $thresholds->isHumidityAberrant($humidity)
            || $thresholds->isCo2Aberrant($co2)
        ) {
            $sensorState = SensorStateEnum::NOT_WORKING;
        }

        // 2. Évaluer la température
        if ($temperature !== null && !$thresholds->isTemperatureAberrant($temperature)) {
            if ($isHeatingPeriod) {
                if (
                    $temperature < $thresholds->getHeatingTempCriticalMin() ||
                    $temperature > $thresholds->getHeatingTempCriticalMax()
                ) {
                    $state = RoomStateEnum::CRITICAL;
                } elseif (
                    $temperature < $thresholds->getHeatingTempWarningMin() ||
                    $temperature > $thresholds->getHeatingTempWarningMax()
                ) {
                    // Ne passer en AT_RISK que si pas déjà CRITICAL
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

        // 3. Évaluer le CO2
        if ($co2 !== null && !$thresholds->isCo2Aberrant($co2)) {
            if ($co2 < $thresholds->getCo2CriticalMin() || $co2 > $thresholds->getCo2ErrorMax()) {
                $state = RoomStateEnum::CRITICAL;
            } elseif ($co2 > $thresholds->getCo2WarningMin() && $co2 <= $thresholds->getCo2CriticalMax()) {
                if ($state !== RoomStateEnum::CRITICAL) {
                    $state = RoomStateEnum::AT_RISK;
                }
            }
        }

        // 4. Évaluer l’humidité
        if ($humidity !== null && !$thresholds->isHumidityAberrant($humidity)) {
            if ($humidity < $thresholds->getHumCriticalMin()) {
                $state = RoomStateEnum::CRITICAL;
            } elseif (
                $humidity < $thresholds->getHumWarningMin() ||
                ($humidity > $thresholds->getHumWarningMax() && $humidity <= $thresholds->getHumCriticalMax())
            ) {
                if ($state !== RoomStateEnum::CRITICAL) {
                    $state = RoomStateEnum::AT_RISK;
                }
            } elseif ($humidity > $thresholds->getHumCriticalMax()) {
                $state = RoomStateEnum::CRITICAL;
            }
        }

        if (
            $state === RoomStateEnum::NO_DATA &&
            ($temperature !== null || $humidity !== null || $co2 !== null)
        ) {
            $state = RoomStateEnum::STABLE;
        }

        // 5. Créer une tâche de maintenance si capteur KO et qu’on n’est pas dans NO_DATA
        if ($sensorState === SensorStateEnum::NOT_WORKING && $state !== RoomStateEnum::NO_DATA) {
            $this->createTaskForTechnician($room);
        }

        // 6. Mise à jour finale du Room et de l’AcquisitionSystem
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
     * @brief Creates a maintenance task for a technician if none exists for the given room.
     *
     * @param Room $room
     */
    private function createTaskForTechnician(Room $room): void
    {
        $em = $this->getEntityManager();

        // On vérifie si une Action de maintenance est déjà en TO_DO
        $existingTask = $em->getRepository(Action::class)->findOneBy([
            'room'  => $room,
            'info'  => ActionInfoEnum::MAINTENANCE,
            'state' => ActionStateEnum::TO_DO,
        ]);

        if ($existingTask) {
            return; // Une tâche existe déjà
        }

        // Sinon, on la crée
        $action = new Action();
        $action->setRoom($room);
        $action->setInfo(ActionInfoEnum::MAINTENANCE);
        $action->setState(ActionStateEnum::TO_DO);
        $action->setCreatedAt(new \DateTime());

        $em->persist($action);
        $em->flush();
    }

    /* ======================================================
     *      PARTIE HISTORIQUE
       ====================================================== */

    /**
     * @brief Fetch historical data from the external API for a specific range (week, month).
     *        Then store it in a local JSON file.
     *
     * @param AcquisitionSystem $acquisitionSystem
     * @param string            $range
     * @return array            The array of data fetched
     *
     * @throws \RuntimeException if any API error
     */
    public function fetchHistoricalDataFromApi(AcquisitionSystem $acquisitionSystem, string $range): array
    {
        $url = 'https://sae34.k8s.iut-larochelle.fr/api/captures/interval';
        $sensorTypes = ['temp', 'hum', 'co2'];
        $now = new \DateTime();
        $startDate = clone $now;

        // Déterminer la plage de dates
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
        $dbName = $acquisitionSystem->getDbName(); // Récupérer dbName dynamique
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

        $historyFile = $this->jsonDirectory . '/history/' . $acquisitionSystem->getRoom()->getName() . '_history.json';
        file_put_contents($historyFile, json_encode($data, JSON_PRETTY_PRINT));

        return $data;
    }

}