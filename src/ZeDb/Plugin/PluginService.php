<?php

namespace ZeDb\Plugin;

class PluginService {

    private $em;
    private $modulo;
    
    public function __construct(\ZeDb\DatabaseManager $em) {
        $this->em = $em;
    }

    public function getEntity($modulo, $entidade) {
        
        $this->modulo = $modulo;
        $namespace = str_replace('/', '\\', $this->obtemNamespace($entidade));
        
        if ( ! class_exists($namespace) ){
            throw new \Exception("Erro ao carregar a classe $namespace, verique se a classe existe ou se os parametros foram passados corretamente!");
        }
        
        $this->configuraZeDb($namespace);
        $instancia = $this->em->get($namespace);
 
        return $instancia;
    }

    private function configuraZeDb($namespace){
        $config = $this->em->getServiceLocator()->get('Configuration');
        $config = isset($config['zendexperts_zedb']) && (is_array($config['zendexperts_zedb']) || $config['zendexperts_zedb'] instanceof ArrayAccess)
        ? array_merge($config['zendexperts_zedb'], $this->geraArrayConfig($namespace))
        : array();
        
        $this->em->setConfig($config);
    }

    private function obtemNamespace($entidade){
        
        $iterator = $this->getIterator();
        
        $recursiveIterator = new \RecursiveIteratorIterator($iterator);
        $service = FALSE;
        foreach ( $recursiveIterator as $entry ) {
            if($entry->getFilename() == $entidade.'.php'){
                
                 $service = "$this->modulo\\Entity\\$entidade";
                
                if(PHP_OS != 'WINNT'){
                    $service = str_replace('\\', '/', $service);
                }
                break;
            }
        }
        
        return $service;
    }
    
    private function getIterator(){
        
        $path = getcwd()."\\module\\$this->modulo\\src";         
        if(PHP_OS != 'WINNT'){ $path = str_replace('\\', '/', $path); } 
        
        try {
            $iterator = new \RecursiveDirectoryIterator($path);
        } catch (\Exception $exc) {

            $path = getcwd()."\\vendor"; 
            if(PHP_OS != 'WINNT'){ $path = str_replace('\\', '/', $path); }
            
            try {
                $iterator = new \RecursiveDirectoryIterator($path);
            } catch (\Exception $exc) {
                echo '<pre>';
                print_r($exc->getTraceAsString());
                die();
            }

        }

        return $iterator;
        
    }
    
    private function geraArrayConfig($namespace){
        $model_class = str_replace('Entity', 'Model', $namespace);
        $tabela = explode('\\', $namespace);
        
        $tabela = array_pop($tabela);
        $table_name = strtolower($tabela[0]);
        
        for($i=1; $i<strlen($tabela);$i++){
            $char = $tabela[$i];
            $table_name .= (preg_match('/[A-Z]/', $char)) ? '_'.strtolower($char) : $char ;
        }
        $table_name = str_replace('._', '.', $table_name);
        
        return [
                'models' => [
                    $model_class => [
                        'tableName' => $table_name,
                        'entityClass' => $namespace,
                    ]
                ]
            ];
    }
    
}

?>