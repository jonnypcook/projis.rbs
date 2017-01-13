<?php
namespace Trial\Form;

use Zend\Form\Form;


class StartDateForm extends Form 
{
    public function __construct()
    {
        $name = preg_replace('/^[\s\S]*[\\\]([a-z0-9_]+)$/i','$1',__CLASS__);
        // we want to ignore the name passed
        parent::__construct($name);
        
        
        $this->setAttribute('method', 'post');
        
        $this->add(array(
            'name' => 'installed', 
            'attributes' => array(
                'type'  => 'text',
                'data-original-title' => 'Date of commencement of trial',
                'data-placement' => 'left',
                'data-trigger' => 'hover',
                'data-mask' => '99/99/9999',
                'class' => 'tooltips ',
                //'readonly' => 'true',
            ),
            'options' => array(
                'format' => 'd/m/Y'
            ),
        ));

        
    }
    
}