<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Application\Entity\User;
use Application\Service\GoogleService;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Console\Request as ConsoleRequest;
use Zend\Http\Client;


class ConsoleController extends AbstractActionController
{
    static $NEWLINE = "\r\n";

    /**
     * full data synchronization for rbs liteip
     * @throws \Exception
     */
    public function synchronizeLiteipAction() {
        date_default_timezone_set('Europe/London');
        // Check command flags
        $request = $this->getRequest();
        $mode = $request->getParam('mode', 'all'); // defaults to 'all'
        $verbose = $request->getParam('verbose') || $request->getParam('v');
        $testMode = $request->getParam('test') || $request->getParam('t');

        $customerGroup = $this->getCustomerGroup($mode);
        $this->addOutputMessage("Starting {$mode} synchronization");

        $this->synchronizeLiteIP($customerGroup, $verbose);
        $this->addOutputMessage("synchronization complete");

        return;
    }

    /**
     * @param $customerGroup
     * @param $verbose
     */
    public function synchronizeLiteIP($customerGroup, $verbose) {
        // Check command flags
        $liteIPService = $this->getLiteIpService();

        $liteIPService->synchronizeProjectsData(true, $customerGroup);
        $liteIPService->synchronizeDrawingsData(false, $customerGroup);

        $em = $this->getEntityManager();

        // get projects data for grouping
        $qb = $em->createQueryBuilder();
        $qb->select('p')
            ->from('Application\Entity\LiteipProject', 'p')
            ->where('p.CustomerGroup=?1')
            ->andWhere('p.TestSite=false')
            ->setParameter(1, $customerGroup);
        $projects = $qb->getQuery()->getResult();

        foreach ($projects as $project) {
            if ($verbose) {
                $this->addOutputMessage('Synchronizing: ' . (empty($project->getProjectDescription()) ? 'undefined' : $project->getProjectDescription()) . ' (' . $project->getProjectID() . ' - ' . $project->getPostCode() . ')', $verbose);
            }
            $liteIPService->synchronizeDevicesData(false, $project->getProjectID());
        }
    }

    /**
     * @param $mode
     * @return int
     * @throws \Exception
     */
    public function getCustomerGroup($mode) {
        switch (strtolower($mode)) {
            case 'rbs':
                return 19;
                break;
            default:
                throw new \Exception('Illegal mode selected');
                break;
        }
    }

    /**
     * emergency
     * @throws RuntimeException
     * @throws \Exception
     */
    public function emergencyAction()
    {
        date_default_timezone_set('Europe/London');
        $em = $this->getEntityManager();
        $request = $this->getRequest();

        // Make sure that we are running in a console and the user has not tricked our
        // application into running this action from a public web server.
        if (!$request instanceof ConsoleRequest) {
            throw new RuntimeException('You can only use this action from a console!');
        }

        // Check command flags
        $mode = $request->getParam('mode', 'all'); // defaults to 'all'
        $verbose = $request->getParam('verbose') || $request->getParam('v');
        $synchronize = $request->getParam('synchronize') || $request->getParam('s');
        $testMode = $request->getParam('test') || $request->getParam('t');

        // configure emergency report
        $customerGroup = $this->getCustomerGroup($mode);
        $subject = 'EMERGENCY REPORT ALERT: RBS';
        $to = $testMode ? array('jonny.p.cook@8point3led.co.uk') : array('rbsemergency@8point3led.co.uk');

        // synchronize project device data
        if ($synchronize) {
            $this->addOutputMessage('Starting synchronization of projects ...', $verbose);
            $this->synchronizeLiteIP($customerGroup, $verbose);
            $this->addOutputMessage('Synchronization of projects completed', $verbose);
        }

        $this->addOutputMessage('Generating report', $verbose);

        $data = array(
            'projects' => array(),
            'count' => array('project' => 0,'drawing' => 0,'device' => 0,'passed' => 0,'error' => 0,'warning' => 0)
        );

        $now = new \DateTime('now');
        $qb = $em->createQueryBuilder();
        $qb->select('d')
            ->from('Application\Entity\LiteipDevice', 'd')
            ->innerJoin('d.drawing', 'dr')
            ->innerJoin('dr.project', 'p')
            ->andWhere('p.CustomerGroup= :cGroup')
            ->andWhere('p.TestSite=false')
            ->setParameter('cGroup', $customerGroup)
            ->andWhere('d.IsE3=true')
            ->addOrderBy('dr.project', 'ASC')
            ->addOrderBy('dr.DrawingID', 'ASC');


        $devices = $qb->getQuery()->getResult();
        foreach ($devices as $device) {
            $project = $device->getDrawing()->getProject();
            $projectName = $project->getProjectDescription();
            $drawingName = $device->getDrawing()->getDrawing(true);

            if (empty($data['projects'][$projectName])) {
                $data['projects'][$projectName] = array(
                    'drawings' => array(),
                    'postcode' => $project->getPostCode(true),
                    'count' => array ('drawing' => 0,'device' => 0,'passed' => 0,'error' => 0,'warning' => 0)
                );

                $data['count']['project']++;
            }

            if (empty($data['projects'][$projectName]['drawings'][$drawingName])) {
                $data['projects'][$projectName]['drawings'][$drawingName] = array(
                    'errors' => array(),
                    'warnings' => array(),
                );

                $data['count']['drawing']++;
                $data['projects'][$projectName]['count']['drawing']++;
            }

            $data['projects'][$projectName]['count']['device']++;
            $data['count']['device']++;

            $timestamp = empty($device->getLastE3StatusDate()) ? 0 : $device->getLastE3StatusDate()->getTimestamp();
            $diff = $now->getTimestamp() - $timestamp;

            if ($device->getStatus() && $device->getStatus()->isFault()) {
                $data['count']['error']++;
                $data['projects'][$projectName]['count']['error']++;

                $data['projects'][$projectName]['drawings'][$drawingName]['errors'][] = array(
                    $device->getDeviceID(),
                    $device->getDeviceSN(),
                    $device->getStatus()->getDescription(),
                    empty($device->getLastE3StatusDate()) ? 'Unknown' : $device->getLastE3StatusDate()->format('d/m/Y H:i:s'),
                    floor($diff / (60 * 60 * 24))
                );
            } else {
                $data['count']['passed']++;
                $data['projects'][$projectName]['count']['passed']++;
            }

            if($device->isIsE3() && (floor($diff / (60 * 60 * 24)) > 4)) { // if not tested for 24 hours
                $data['count']['warning']++;
                $data['projects'][$projectName]['count']['warning']++;

                $data['projects'][$projectName]['drawings'][$drawingName]['warnings'][] = array(
                    $device->getDeviceID(),
                    $device->getDeviceSN(),
                    floor($diff / (60 * 60 * 24)),
                    empty($device->getLastE3StatusDate()) ? '' : $device->getLastE3StatusDate()->format('d/m/Y H:i:s'),
                    floor($diff / (60 * 60 * 24))
                );
            }
        }

        // build email
        $this->addOutputMessage('Sending report', $verbose);
        if (($data['count']['error'] > 0) || ($data['count']['warning'] > 0)) {
            $html = '<style>tfoot {font-weight: bold} th{text-align: left;} th,td{padding: 2px} th.right, td.right {text-align: right; } .success {color: #090} .error {color: #d00} .warning {color: orange} </style>' .
                '<br>%s<br>%s<br>%s' .
                '<p>Please do not reply to this message. Replies to this message are routed to an unmonitored mailbox.</p>';

            $tblErrors = '';
            $tblWarnings = '';
            $tblSummary = array('success' => '', 'warning' => '', 'error' => '');
            foreach ($data['projects'] as $projectName => $projectData) {

                if ($projectData['count']['error'] > 0) {
                    $class = 'error';
                } elseif ($projectData['count']['warning'] > 0) {
                    $class = 'warning';
                } else {
                    $class = 'success';
                }
                $tblSummary[$class] .=  '<tr><td class="' . $class . '">' . $projectName . '</td>' .
                    '<td>' . $projectData['postcode'] . '</td>' .
                    '<td class="right">' . $projectData['count']['device'] . '</td>' .
                    '<td class="right">' . $projectData['count']['passed'] . '</td>' .
                    '<td class="right">' . $projectData['count']['error'] . '</td>' .
                    '<td class="right">' . $projectData['count']['warning'] . '</td></tr>';

                foreach ($projectData['drawings'] as $drawingName => $drawingData) {
                    foreach ($drawingData['errors'] as $deviceData) {
                        $tblErrors .= '<tr><td>' . $projectName . '</td>' .
                            '<td>' . $projectData['postcode'] . '</td>' .
                            '<td>' . $drawingName . '</td>' .
                            '<td>' . $deviceData[1] . '</td>' .
                            '<td>' . $deviceData[2] . '</td>' .
                            '<td>' . $deviceData[3] . '</td></tr>';
                    }

                    foreach ($drawingData['warnings'] as $deviceData) {
                        $tblWarnings .= '<tr><td>' . $projectName . '</td>' .
                            '<td>' . $projectData['postcode'] . '</td>' .
                            '<td>' . $drawingName . '</td>' .
                            '<td>' . $deviceData[1] . '</td>' .
                            '<td>' . $deviceData[2] . ' days untested</td>' .
                            '<td>' . $deviceData[3] . '</td></tr>';
                    }

                }
            }

            $tblErrors = '<h3>Error Report</h3><table><thead><tr><th>Site</th><th>Postcode</th><th>Drawing</th><th>Device SN</th><th>Status</th><th>Last Tested</th></tr></thead><tbody>' . $tblErrors . '</tbody></table>';
            $tblWarnings = '<h3>Warnings Report</h3><table><thead><tr><th>Site</th><th>Postcode</th><th>Drawing</th><th>Device SN</th><th>Status</th><th>Last Tested</th></tr></thead><tbody>' . $tblWarnings . '</tbody></table>';
            $tblSummary = '<h3>Site Summary</h3><table><thead><tr><th>Site</th><th>Postcode</th><th>Devices</th><th>Passed</th><th>Errors</th><th>Warnings</th></tr></thead><tbody>' .
                implode('', $tblSummary) . '</tbody>' .
                '<tfoot><td>TOTAL</td><td></td><td class="right">' . $data['count']['device'] . '</td><td class="right">' . $data['count']['passed'] . '</td><td class="right">' . $data['count']['error'] . '</td><td class="right">' . $data['count']['warning'] . '</td></tfoot></table>';

            // send email
            $this->getGoogleService()->sendGmail($subject, sprintf($html, $tblSummary, $tblErrors, $tblWarnings), $to);
            $this->addOutputMessage('Email sent', true);
        }

        $this->addOutputMessage($data['count']['error'] . ' errors found');
        $this->addOutputMessage($data['count']['warning'] . ' warnings found');

        return;
    }


    /**
     * get LiteIp Service
     * @return \Application\Service\LiteIpService
     */
    public function getLiteIpService() {
        return $this->getServiceLocator()->get('LiteIpService');
    }

    /**
     * @return \Application\Service\GoogleService
     */
    public function getGoogleService()
    {
        $config = $this->getServiceLocator()->get('Config');
        $user = new User();
        $user->setEmail('crm@8point3led.co.uk');
        $user->setForename('Emergency Alerts');
        $user->setGoogleEnabled(false);

        return new GoogleService($config['openAuth2']['google'], $user, $this->getEntityManager());
    }

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;


    /**
     * @return array|object|Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        if (null === $this->em) {
            $this->em = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        }
        return $this->em;
    }

    /**
     * @param $message
     * @param bool|true $verbose
     */
    public function addOutputMessage($message, $verbose = true)
    {
        if ($verbose === false) {
            return;
        }

        echo date('Y-m-d H:i:s ') . $message . self::$NEWLINE;

    }

}
