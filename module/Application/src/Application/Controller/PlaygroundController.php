<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\View\Model\JsonModel;


class PlaygroundController extends AuthController
{
    public function indexAction()
    {
        $this->setCaption('Playground');
        
        /*$this->getView()
                ->setVariable('count', count($projects))
                ->setVariable('term', $term)
                ->setVariable('projects', $projects);/**/

        return $this->getView();
    }

    public function reloadLiteipAction() {
        if (!$this->isGranted('admin.playground')) {
            throw new \Exception('Illegal access');
        }

        $projects = $this->params()->fromQuery('projects', false);
        $drawings = $this->params()->fromQuery('drawings', false);
        $devices = $this->params()->fromQuery('devices', false);

        $liteIPService = $this->getLiteIpService();

        if ($projects !== false) {
            echo 'Projects synchronized<br>';
            $liteIPService->synchronizeProjectsData();
        }

        if ($drawings !== false) {
            echo 'Drawings synchronized<br>';
            $liteIPService->synchronizeDrawingsData();
        }

        if ($devices !== false) {
            echo 'Devices synchronized<br>';
            $liteIPService->synchronizeDevicesData();
        }

        die();
    }

    /**
     * get LiteIp Service
     * @return \Application\Service\LiteIpService
     */
    public function getLiteIpService() {
        return $this->getServiceLocator()->get('LiteIpService');
    }


    public function routemappingAction() {
        $this->setCaption('Google Route Mapping Demo');
        
        /*$this->getView()
                ->setVariable('count', count($projects))
                ->setVariable('term', $term)
                ->setVariable('projects', $projects);/**/

        return $this->getView();
    }
    
    public function colourdimmingAction() {
        $this->setCaption('Colour Dimming Demo');
        
        return $this->getView();
    }
    
    public function hmrcAction() {
        $this->setCaption('HM Prisons Demo');
        
        return $this->getView();
    }
    
     
}
