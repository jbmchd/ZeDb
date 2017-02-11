<?php

namespace ZeDb;

use ZeDb\EntityBase;
use Zend\Stdlib\Hydrator\ClassMethods;

class Entity extends EntityBase {
    
    private $data = [];
    
    public function exchangeArray($data){
        $this->data = $data;
        (new ClassMethods)->hydrate($data, $this);
    }
    
    public function tableFill(Array $data){
        (new \Zend\Stdlib\Hydrator\ClassMethods())->hydrate($data, $this);
        return $this;
    }
    
    public function toArray() {
        
        $array = [];
        $array = (new ClassMethods)->extract($this);
        if(sizeof($array) >= sizeof($this->data)){ return $array; }
        
        $get_methods = $this->attrToGetMethods();
        if(sizeof($get_methods)){
            foreach ($get_methods as $attr => $method) {
                $valor = $this->$method();
                if($valor !== '[Method not found]'){
                    $array[$attr] = $valor;
                }
            }
        }
        return $array;
    }
    
    public function __call($method, $arguments) {
        return '[Method not found]';
    }

    public function populate(array $rowData){
        foreach ($rowData as $key => $value) {
            
            $date = \DateTime::createFromFormat('d/m/Y', $value);
            if($date){ $rowData[$key] = $date->format('Y-m-d'); } 
            else { $date = \DateTime::createFromFormat('d/m/Y H:i:s', $value); 
            if($date){ $rowData[$key] = $date->format('Y-m-d H:i:s'); } 
            else { $date = \DateTime::createFromFormat('d/m/Y H:i', $value);
            if($date){ $rowData[$key] = $date->format('Y-m-d H:i'); }}}
            
        }
        
        return parent::populate($rowData);
    }
    
    private function attrToGetMethods($data=null){
        if(sizeof($this->data)){ $data = $this->data; }
        $gettersName = [];
        foreach ($data as $property => $value) {
            $gettersName[$property] = 'get' . implode('', explode(' ', ucwords(str_replace('_', ' ',$property))));
        }
        return $gettersName;
    }

    
}
