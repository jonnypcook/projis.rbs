<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Application\Entity\Address,    Application\Entity\Projects;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Zend\Paginator\Paginator;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;

class BranchController extends AuthController
{
    protected $clients;

    public function onDispatch(MvcEvent $e) {
        $config = $this->getServiceLocator()->get('Config');

        if (empty($config) || !is_array($config['liteip']) || empty($config['liteip']['client'])) {
            return $this->redirect()->toRoute('home');
        }

        $this->setClients($config['liteip']['client']);

        return parent::onDispatch($e);
    }

    /**
     * @return mixed
     */
    public function getClients()
    {
        return $this->clients;
    }

    /**
     * @param mixed $clients
     */
    public function setClients($clients)
    {
        $this->clients = $clients;
    }



    /**
     * list devices for liteip drawing
     * @return JsonModel
     * @throws \Exception
     */
    public function listAction() {
        $em = $this->getEntityManager();

        if (!$this->request->isXmlHttpRequest()) {
            throw new \Exception('illegal request type');
        }

        $queryBuilder = $em->createQueryBuilder();

        $queryBuilder
            ->select('p')
            ->from('Project\Entity\Project', 'p')
            ->innerJoin('p.lipProject', 'lip')
            ->leftJoin('p.address', 'a')
            ->where($queryBuilder->expr()->in('p.client', ':cid'))
            ->andWhere('p.test != true')
            ->andWhere('p.cancelled != true')
            ->setParameter('cid', $this->getClients());

        /*
        * Filtering
        * NOTE this does not match the built-in DataTables filtering which does it
        * word by word on any field. It's possible to do here, but concerned about efficiency
        * on very large tables, and MySQL's regex functionality is very limited
        */
        $projectName = trim($this->params()->fromQuery('sSearch',''));
        if (!empty($projectName)) {
            $queryBuilder->andWhere('p.name LIKE :name')
                ->setParameter('name', "%{$projectName}%");
        }


        /*
         * Ordering
         */
        $aColumns = array('p.name','a.line1','a.postcode', 'p.weighting');
        $orderByP = $this->params()->fromQuery('iSortCol_0',false);
        $orderBy = array();
        if ($orderByP!==false)
        {
            for ( $i=0 ; $i<intval($this->params()->fromQuery('iSortingCols',0)) ; $i++ )
            {
                $j = $this->params()->fromQuery('iSortCol_'.$i);

                if ( $this->params()->fromQuery('bSortable_'.$j, false) == "true" )
                {
                    $dir = $this->params()->fromQuery('sSortDir_'.$i,'ASC');
                    if (is_array($aColumns[$j])) {
                        foreach ($aColumns[$j] as $ac) {
                            $orderBy[] = $ac." ".$dir;
                        }
                    } else {
                        $orderBy[] = $aColumns[$j]." ".($dir);
                    }
                }
            }

        }

        if (empty($orderBy)) {
            $orderBy[] = 'p.name ASC';
        }

        foreach ($orderBy as $ob) {
            $queryBuilder->add('orderBy', $ob);
        }

        /**/

        // Create the paginator itself
        $paginator = new Paginator(
            new DoctrinePaginator(new ORMPaginator($queryBuilder))
        );

        $length = $this->params()->fromQuery('iDisplayLength', 10);
        $start = $this->params()->fromQuery('iDisplayStart', 1);
        $start = (floor($start / $length)+1);


        $paginator
            ->setCurrentPageNumber($start)
            ->setItemCountPerPage($length);

        $data = array(
            "sEcho" => intval($this->params()->fromQuery('sEcho', false)),
            "iTotalDisplayRecords" => $paginator->getTotalItemCount(),
            "iTotalRecords" => $paginator->getcurrentItemCount(),
            "aaData" => array()
        );/**/


        foreach ($paginator as $page) {
            if ($page->getWeighting()<10) {
                $statusCls = 'danger';
            } elseif ($page->getWeighting()<30) {
                $statusCls = 'warning';
            } elseif ($page->getWeighting()<50) {
                $statusCls = 'info';
            } elseif ($page->getWeighting()<80) {
                $statusCls = 'striped';
            } else {
                $statusCls = 'success';
            }
            $statusHtml = '<span style="position: absolute; padding-top:12px">'. $page->getWeighting() .'%</span><div class="progress progress-'.$statusCls.'"><div style="width: '. $page->getWeighting() .'%;" class="bar"></div></div>';

            $data['aaData'][] = array (
                '<a href="/branch-' . $page->getProjectId() . '/">' . $page->getName() . '</a>',
                empty($page->getAddress()) ? '-' : $page->getAddress()->assemble(', ', array('postcode')),
                empty($page->getAddress()) ? '-' : $page->getAddress()->getPostcode(),
                $statusHtml
            );
        }

        return new JsonModel($data);/**/
    }

    /**
     * get LiteIp Service
     * @return \Application\Service\LiteIpService
     */
    public function getLiteIpService() {
        return $this->getServiceLocator()->get('LiteIpService');
    }

}