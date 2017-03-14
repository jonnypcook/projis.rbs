<?php

namespace Application\Service;

use Application\Entity\LiteipDevice;
use Application\Entity\LiteipDeviceHistory;
use Application\Entity\LiteipDeviceStatus;
use Application\Entity\LiteipDrawing;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use Application\Entity\LiteipProject;
use Zend\Http\Client;
use ZendService\ReCaptcha\Exception;

class LiteIpService
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;


    public function __construct($config, \Doctrine\ORM\EntityManager $em) {
        $this->setConfig($config);
        $this->setEntityManager($em);
    }

    /**
     * config getter
     * @return array
     */
    public function getConfig() {
        return $this->config;
    }

    /**
     * config setter
     * @param $config
     * @return $this
     */
    public function setConfig($config) {
        $this->config = $config;
        return $this;
    }


    /**
     * entity manager setter
     * @param \Doctrine\ORM\EntityManager $em
     */
    function setEntityManager(\Doctrine\ORM\EntityManager $em) {
        $this->em = $em;
    }

    /**
     * entity manager getter
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager() {
        return $this->em;
    }


    /**
     * @param $uri
     * @return \Zend\Http\Response
     */
    private function curlRequest($uri)
    {
        $config = array(
            'adapter' => 'Zend\Http\Client\Adapter\Curl',
        );
        $client = new Client($uri, $config);
        $client->setMethod('get');

        return $client->send();
    }

    /**
     * get projects data from host
     * @return \Zend\Http\Response
     */
    public function getProjectsData() {
        $config = $this->getConfig();
        $uri = $config['uri']['projects'];
        $response = $this->curlRequest($uri);

        return $response;
    }

    /**
     * get drawings data from host
     * @param $projectId
     * @return \Zend\Http\Response
     */
    public function getDrawingData($projectId) {
        $config = $this->getConfig();
        $uri = sprintf($config['uri']['drawings'], $projectId);
        $response = $this->curlRequest($uri);

        return $response;
    }

    /**
     * get devices data from host
     * @param $drawingId
     * @param int $e3Only
     * @return \Zend\Http\Response
     */
    public function getDeviceData($drawingId, $e3Only = 0) {
        $config = $this->getConfig();
        $uri = sprintf($config['uri']['devices'], $drawingId, $e3Only);

        $response = $this->curlRequest($uri);

        return $response;
    }

    /**
     * @param bool|false $update
     * @param bool|false $customerGroup
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function synchronizeProjectsData($update = false, $customerGroup = false) {
        $response = $this->getProjectsData();
        $em = $this->getEntityManager();

        if ($response->getStatusCode() === 200) {
            $projects = json_decode($response->getBody(), true);
            foreach ($projects as $project) {
                if (!empty($customerGroup) && ($customerGroup != $project['CustomerGroup'])) {
                    continue;
                }

                $liteIpProject = $em->find('Application\Entity\LiteipProject', $project['ProjectID']);
                if (!($liteIpProject instanceof LiteipProject)) {
                    $liteIpProject = new LiteipProject();
                } elseif($update !== true) {
                    continue;
                }

                $hydrator = new DoctrineHydrator($em, 'Application\Entity\LiteipProject');
                $hydrator->hydrate($project, $liteIpProject);
                $em->persist($liteIpProject);
            }
            $em->flush();
        }
    }

    /**
     * @param bool|false $projectId
     * @param bool|false $customerGroup
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function synchronizeDrawingsData($projectId = false, $customerGroup = false) {
        $em = $this->getEntityManager();
        $sql = "SELECT p FROM Application\Entity\LiteipProject p";

        if (!empty($projectId)) {
            $sql .= " WHERE p.ProjectId = {$projectId}";
        }

        if (!empty($customerGroup)) {
            $sql .= " WHERE p.CustomerGroup = {$customerGroup}";
        }
        $query = $em->createQuery( $sql );
        $projects = $query->getResult();

        if (!$projects) {
            return;
        }

        foreach ($projects as $project) {
            $response = $this->getDrawingData($project->getProjectId());
            if ($response->getStatusCode() === 200) {
                $drawings = json_decode($response->getBody(), true);
                foreach ($drawings as $drawing) {
                    if ($em->find('Application\Entity\LiteipDrawing', $drawing['DrawingID'])) {
                        continue;
                    }

                    $liteipDrawing = new LiteipDrawing();
                    $hydrator = new DoctrineHydrator($em, 'Application\Entity\LiteipDrawing');
                    $hydrator->hydrate($drawing + array('project' => $project->getProjectId()), $liteipDrawing);
                    $em->persist($liteipDrawing);
                }
                $em->flush();
            }
        }

    }

    /**
     * @param bool|false $drawingId
     * @param bool|false $projectId
     * @param bool|false $customerGroup
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function synchronizeDevicesData($drawingId = false, $projectId = false, $customerGroup = false, $addToHistory = false) {
        $em = $this->getEntityManager();
        $sql = "SELECT d FROM Application\Entity\LiteipDrawing d";
        if (!empty($drawingId)) {
            $sql .= " WHERE d.DrawingID = {$drawingId}";
        } elseif (!empty($projectId)) {
            $sql .= " WHERE d.project = {$projectId}";
        } elseif (!empty($customerGroup)) {
            $sql .= " JOIN project p WHERE p.CustomerGroup = {$customerGroup}";
        }

        $query = $em->createQuery( $sql );
        $drawings = $query->getResult();

        if (!$drawings) {
            return;
        }


        foreach ($drawings as $drawing) {
            $response = $this->getDeviceData($drawing->getDrawingId(), 0);
            if ($response->getStatusCode() === 200) {
                $devices = json_decode($response->getBody(), true);
                $deviceIds = array();
                $now = new \DateTime();
                foreach ($devices as $device) {
                    $deviceIds[] = $device['DeviceID'];
                    $liteipDevice = $em->find('Application\Entity\LiteipDevice', $device['DeviceID']);
                    if (!($liteipDevice instanceof LiteipDevice)) {
                        $liteipDevice = new LiteipDevice();
                    }

                    $hydrator = new DoctrineHydrator($em, 'Application\Entity\LiteipDevice');
                    $hydratorData = $device + array('drawing' => (int)$device['DrawingID']);

                    if (!preg_match('/^[\d]+[\\/][\d]+[\\/][\d]+[ ][\d]+[:][\d]+[:][\d]+$/', $hydratorData['LastE3StatusDate'])) {
                        unset($hydratorData['LastE3StatusDate']);
                    } else {
                        $hydratorData['LastE3StatusDate'] = \DateTime::createFromFormat('d/m/Y H:i:s', $hydratorData['LastE3StatusDate']);
                    }

                    if ((int)$device['LastE3Status'] >= 0) {
                        $hydratorData['status'] = ((int)$device['LastE3Status'] == 0) ? 600 : (int)$device['LastE3Status'];
                    }
                    $hydrator->hydrate($hydratorData, $liteipDevice);

                    $em->persist($liteipDevice);

                    if ($addToHistory === true && $liteipDevice->isIsE3()) {
                        $this->takeHistorySnapshot($liteipDevice->getDeviceID(), $device['LastE3Status'], $device['LastE3StatusDate'], $now);
                    }
                }
                $em->flush();

                $this->tidyDevices($deviceIds, $drawing->getDrawingId());
            }
        }

    }

    /**
     * save snapshot of data
     * @param $deviceId
     * @param $statusId
     * @param $LastE3StatusDate
     * @param $testedDate
     */
    public function takeHistorySnapshot($deviceId, $statusId, $LastE3StatusDate, $testedDate) {
        $em = $this->getEntityManager();
        $liteipDeviceHistory = new LiteipDeviceHistory();
        $hydrator = new DoctrineHydrator($em, 'Application\Entity\LiteipDeviceHistory');
        $hydratorData = array(
            'TestedDate' => $testedDate,
            'device' => $deviceId
        );
        if (preg_match('/^[\d]+[\\/][\d]+[\\/][\d]+[ ][\d]+[:][\d]+[:][\d]+$/', $LastE3StatusDate)) {
            $hydratorData['LastE3StatusDate'] = \DateTime::createFromFormat('d/m/Y H:i:s', $LastE3StatusDate);
        }

        if ((int)$statusId >= 0) {
            $hydratorData['status'] = ((int)$statusId == 0) ? 600 : (int)$statusId;
        }

        $hydrator->hydrate($hydratorData, $liteipDeviceHistory);
        $em->persist($liteipDeviceHistory);
    }

    /**
     * remove devices that are not in $deviceIDs for drawing $drawingId
     * @param $deviceIds
     * @param $drawingId
     */
    protected function tidyDevices($deviceIds, $drawingId) {
        $em = $this->getEntityManager();

        $queryBuilder = $em->createQueryBuilder();

        $queryBuilder
            ->select('d')
            ->from('Application\Entity\LiteipDevice', 'd')
            ->andWhere('d.drawing = :DrawingID')
            ->andWhere($queryBuilder->expr()->notIn('d.DeviceID', ':DeviceID'))
            ->setParameter('DrawingID', $drawingId)
            ->setParameter('DeviceID', $deviceIds);

        foreach ($queryBuilder->getQuery()->getResult() as $device) {
            $query = $em->createQuery('DELETE FROM Application\\Entity\\LiteipDeviceHistory h WHERE h.device = (:devices)')
                ->setParameter('devices', $device->getDeviceID());
            $query->execute();
            $em->flush();

            $em->remove($device);
        }

        $em->flush();
    }

    /**
     * @param $drawingId
     * @return bool|string
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function findDrawingUrl($drawingId) {
        if ( empty($drawingId) )
        {
            return false;
        }

        $drawing = $this->getEntityManager()->find('Application\Entity\LiteipDrawing', $drawingId );

        if ( !($drawing instanceof \Application\Entity\LiteipDrawing) )
        {
            return false;
        }

        $config = $this->getConfig();
        $uri = $config['dir'] . $drawing->getProject()->getProjectID() . DIRECTORY_SEPARATOR . $drawing->getDrawing();

        return $uri;
    }

    /**
     * @param LiteipDrawing $drawing
     * @param $file
     * @throws \Exception
     */
    public function saveDrawing(LiteipDrawing $drawing, $file) {
        $config = $this->getConfig();
        $em = $this->getEntityManager();

        $path = $config['dir'] . $drawing->getProject()->getProjectID();
        if (!is_dir($path)) {
            if (!mkdir($path)) {
                throw new \Exception('project path could not be created');
            }
        }

        $path .= DIRECTORY_SEPARATOR . $drawing->getDrawing();

        $tempFile = $file['tmp_name'];
        if (!file_exists($tempFile)) {
            throw new \Exception('Could not find source file');
        }

        $imageSize = getimagesize($tempFile);
        if (!$imageSize) {
            throw new \Exception('Could not determine image size');
        }

        $drawing->setWidth($imageSize[0]);
        $drawing->setHeight($imageSize[1]);
        $drawing->setActivated(true);

        if (!move_uploaded_file($tempFile, $path)) {
            throw new \Exception('could not move file');
        }

        $em->persist($drawing);
        $em->flush();
    }

}

