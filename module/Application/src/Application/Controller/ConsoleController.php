<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Console\Request as ConsoleRequest;
use Zend\Http\Client;


class ConsoleController extends AbstractActionController
{
    static $NEWLINE = "\r\n";

    public function synchronizeLiteipAction() {
        date_default_timezone_set('Europe/London');
        // Check command flags
        $request = $this->getRequest();
        $mode = $request->getParam('mode', 'all'); // defaults to 'all'
        $verbose = $request->getParam('verbose') || $request->getParam('v');
        $testMode = $request->getParam('test') || $request->getParam('t');

        switch ($mode) {
            case 'rbs':
                $customerGroup = 19;
                break;
            case 'non-rbs':
                $customerGroup = 10;
                break;
            default:
                throw new \Exception('Illegal mode selected');
                break;
        }
        $this->addOutputMessage("Starting {$mode} synchronization");

        $liteIPService = $this->getLiteIpService();

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
            $this->addOutputMessage('Synchronizing: ' . $project->getProjectDescription(), $verbose);
            $liteIPService->synchronizeDevicesData(false, $project->getProjectID());
        }

        $this->addOutputMessage("synchronization complete");

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
