<?php
namespace Tools\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Entity\User,    Application\Entity\Address,    Application\Entity\Projects;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;

use Application\Controller\AuthController;

class ToolsController extends AuthController
{
    
    public function indexAction()
    {
        $this->setCaption('Tools');

        //$this->getView()->setVariable('form', $form);
        return $this->getView();
    }
    
     public function rpCalculatorAction()
    {
        $this->setCaption('Remote Phosphor Calculator (Advanced)');

        //$this->getView()->setVariable('form', $form);
        return $this->getView();
    }

    /**
     * get LiteIp Service
     * @return \Application\Service\LiteIpService
     */
    public function getLiteIpService() {
        return $this->getServiceLocator()->get('LiteIpService');
    }

    public function iotAction() {
        $this->setCaption('IoT Tools');
    }

    public function iotSynchronizeAction() {
        try {

            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }

            $customerGroup = 19;

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
                $liteIPService->synchronizeDevicesData(false, $project->getProjectID());
            }

            $data = array('err'=>false);
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }

        return new JsonModel(empty($data)?array('err'=>true):$data);
    }

    public function rpQuickCalculateAction() {
        try {
            
            if (!($this->getRequest()->isXmlHttpRequest())) {
                throw new \Exception('illegal request');
            }
            
            $post = $this->getRequest()->getPost();
            
            // test values
            $productId = $this->params()->fromPost('productId', false);
            $length = $this->params()->fromPost('length', false);
            $maxunitlen = $this->params()->fromPost('maxunitlen', false);
            $mode = 1;
            
            if (empty($productId) || !preg_match('/^[\d]+$/', $productId)) {
                throw new \Exception('illegal product parameter');
            }
            
            if (empty($length) || !preg_match('/^[\d]+(.[\d]+)?$/', $length)) {
                throw new \Exception('illegal product parameter');
            }
            
            if (empty($maxunitlen) || !preg_match('/^[\d]+(.[\d]+)?$/', $maxunitlen)) {
                throw new \Exception('illegal maximum unit length parameter');
            }
            
            // find product cost per unit
            $product = $this->getEntityManager()->find('Product\Entity\Product', $productId);
            if (!($product instanceof \Product\Entity\Product)) {
                throw new \Exception('illegal product selection');
            }
            
            if ($product->getType()->getTypeId() != 3) { // architectural
                throw new \Exception('illegal product type');
            }
            
            $data = $this->getServiceLocator()->get('Model')->findOptimumArchitectural($product, $length, $mode, array('alts'=>true, 'maxunitlen'=>$maxunitlen));
            
            $data = array('err'=>false, 'info'=>$data);
        } catch (\Exception $ex) {
            $data = array('err'=>true, 'info'=>array('ex'=>$ex->getMessage()));
        }
        
        return new JsonModel(empty($data)?array('err'=>true):$data);/**/
    }
    
    public function barcodeAction() {
        \Zend\Barcode\Barcode::render(
            'code39',
            'image',
            array(
                'text' => 'ZEND-FRAMEWORK',
                'font' => 3
            ),
            array(
                'imageType'=>'png',
            )
        ); /**/
        
        
        
    }
    
         
}
