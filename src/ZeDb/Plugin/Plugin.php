<?php

namespace ZeDb\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

use ZeDb\Plugin\PluginService;

class Plugin extends AbstractPlugin {

    public function get($module, $entity_name=null) {
        /*
         * Caso tenha se passado apenas o primeiro parâmetro
         * entende-se que este é o nome da classe
         * e então busca-se o nome do modulo onde este metodo foi chamado;
         */
        if(func_num_args()===1){
            $entity_name=$module;
            $module = $this->getEntityClass($module);
        }
        
        $service = $this->getController()->getServicelocator()->get(PluginService::class)->getEntity($module, $entity_name);
        return $service;
    }
    
    private function getEntityClass($class){
        $controller      = $this->getController();
        $namespace = get_class($controller);
        $string_apagar = substr($namespace, strpos($namespace, '\\Controller'), strlen($namespace));
        $moduleName = str_replace($string_apagar, '', $namespace);
        return $moduleName;
    }
    
    
}
