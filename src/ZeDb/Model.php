<?php
/**
 * This file is part of ZeDb
 *
 * (c) 2012 ZendExperts <team@zendexperts.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ZeDb;
use Zend\Db\Adapter\Adapter,
    Zend\Db\ResultSet\ResultSet,
    ZeDb\TableGateway,
    ZeDb\Module as ZeDb;
/**
 * Model Class
 * Loads mapper entities from the database and stores them in a local container for later use.
 * Saves entities into the local container before flushing them to the database.
 * Also contains methods for easy access to the most common queries used on the database.
 *
 * @package ZeDb
 * @author Cosmin Harangus <cosmin@zendexperts.com>
 */
class Model extends TableGateway implements ModelInterface
{
    /**
     * @var string
     */
    protected $entityClass = '\ZeDb\Entity';
    protected $tableName = null;
    protected $primaryKey = 'id';
    /**
     * @var array
     */
    protected $_entities = array();
    /**
     * @var array
     */
    protected $_localEntities = array();
    
    //ADD JOABE
    private $connection;
    private $entity;
    
    /**
     * @param array $options
     * @param \Zend\Db\Adapter\Adapter $adapter
     */
    public function __construct($primary_key = null, $options = null, Adapter $adapter=null){
        if($adapter==null){$adapter = $this->getAdaptador();}
        
        $this->tableName=$this->montaNomeTabela($this->tableName);
        $this->connection = $adapter->getDriver()->getConnection();
        
        if($primary_key){
            $this->primaryKey = $primary_key;
        }
        
        if (!$options){
            $options=array();
        }
        //set the table name from config if specified or take it from the child class
        if (array_key_exists('tableName', $options)){
            $tableName = $options['tableName'];
        }else
            $tableName = $this->tableName;

        //set the entity class from the config or from the child class if none defined
        if (array_key_exists('entityClass', $options)){
            $entityClass = trim($options['entityClass'],'\\');
        }else
            $entityClass = trim($this->entityClass,'\\');

        //init the result set to return instances of the entity class
        $this->entityClass = $entityClass;
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAYOBJECT, new $entityClass);
        //init the parent class
        parent::__construct($tableName, $adapter, null, $resultSet);
    }

    /**
     * Return the entity class handled by the model
     * @return string
     */
    public function getEntityClass(){
        return $this->entityClass;
    }

    public function setOptions(array $options)
    {
        //set the table name from config if specified or take it from the child class
        if (array_key_exists('tableName', $options)) {
            $tableName = $options['tableName'];
        } else $tableName = $this->tableName;

        //set the entity class from the config or from the child class if none defined
        if (array_key_exists('entityClass', $options)) {
            $entityClass = trim($options['entityClass'], '\\');
        } else $entityClass = trim($this->entityClass, '\\');

        $this->entityClass = $entityClass;
        $this->tableName = $tableName;
    }

    /**
     * Set the primary key field
     * @param $primaryKey
     * @return Model
     */
    public function setPrimaryKey($primaryKey){
        $this->primaryKey = $primaryKey;
        return $this;
    }

    /**
     * Get the name of the primary key field
     * @return string
     */
    public function getPrimaryKey(){
        return $this->primaryKey;
    }

    /**
     * Get the registry instance that contains all the modules
     * @return \ZeDb\DatabaseManager
     */
    public function getDatabaseManager(){
        return ZeDb::getDatabaseManager();
    }

    /**
     * Persists an entity into the model
     * @param mixed $entities
     * @return Model
     */
    public function persist($entities){
        if ($entities instanceof EntityInterface){
            if ($entities[$this->primaryKey]){
                $this->_entities[$entities[$this->primaryKey]] = $entities;
            }
            $this->_localEntities[] = $entities;
        }elseif (is_array($entities)){
            foreach($entities as $entity){
                if ($entity[$this->primaryKey]){
                    $this->_entities[$entity[$this->primaryKey]] = $entity;
                }
                $this->_localEntities[] = $entity;
            }
        }
        return $this;
    }

    /**
     * Saves the persisted entities into the database
     * @return Model
     */
    public function flush(){
        $unset = array();
        foreach($this->_localEntities as $key => $entity){
            $entity = $this->save($entity);
            $this->_entities[$entity[$this->primaryKey]] = $entity;
            $unset[] = $key;
        }
        foreach($unset as $key){
            unset($this->_localEntities[$key]);
        }
        return $this;
    }

    /**
     * Save an entity directly in the database
     * @param EntityInterface $entity
     * @return EntityInterface
     */
    
    public function save($entity){
        
        if( ! $entity instanceof EntityInterface ){
            $entity = $this->create($entity);
        } 
        
        return $this->_save($entity);
        
    }


    private function _save(EntityInterface $entity){
        $data = $entity->toArray();
        if ($data[$this->primaryKey]){
            $this->update($data, array($this->primaryKey => $data[$this->primaryKey]));
        }else{
            unset($data[$this->primaryKey]);
            $this->insert($data);
            $id = $this->getLastInsertValue();
            $data[$this->primaryKey] = $id;
            $entity->populate($data);
        }
        return $entity;
    }

    /**
     * Create entity from array
     * @param array|null $data
     * @return mixed
     */
    public function create($data = null){
        $entityClass = $this->entityClass;
        $entity = new $entityClass();
        if ($data) {
            $entity->populate($data);
        }
        return $entity;
    }

    /**
     * Get an entity by Id
     * @param int $id
     * @return EntityInterface | null
     */
    public function get($id){
        //Load from repository if found
        if(array_key_exists($id,$this->_entities)){
            return $this->_entities[$id];
        }

        //Load from the database otherwise
        $entity = $this->getById($id);
        if (!$entity) {
            return null;
        }

        //Save in the repository for later use
        $this->_entities[$entity[$this->primaryKey]] = $entity;
        return $entity;
    }

    /**
     * Handles all function calls to the model.
     * Defines magic functions for retrieving records by columns with order and limit.
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function __call($name, $args){
        if (substr($name, 0, 3) == 'get'){
            $entities = parent::__call($name, $args);
            
            $ret=['error'=>false, 'message'=>sizeof($entities).' registro(s) retornado(s)', 'table'=>$entities, 'primeiroRegistro'=>[]];
            
            if (!$entities){
                $ret['table']=[];
            }elseif (is_array($entities)){
                foreach($entities as $entity) {
                    $this->_entities[$entity[$this->primaryKey]] = $entity;
                }
                $ret['primeiroRegistro']=$entities[0];
            }else if ($entities instanceof EntityInterface){
                $this->_entities[$entities[$this->primaryKey]] = $entities;
            }
            
            return $ret;
        }
        return parent::__call($name, $args);
    }

    
    // ADICIONADO POR JOABE
    
    private function getAdaptador(){
        $params = $this->getDatabaseManager()->get('config')['zendexperts_zedb']['adapter'];
        $adapter = new \Zend\Db\Adapter\Adapter($params);
        return $adapter;
    }
    
    public function beginTransaction(){
        $this->connection->beginTransaction();
    }
    
    public function commit(){
        $this->connection->commit();
        $this->disconnect();
    }
    
    public function rollback(){
        $this->connection->rollback();
        $this->disconnect();
    }
    
    public function disconnect(){
        $this->connection->disconnect();
    }
    
    public function isConnected(){
        $this->connection->isConnected();
    }
    
    protected function executeSql($sql){
        try {
            
            //prepara executa a sql
            $statement = $this->getAdapter()->createStatement();
            $statement->prepare($sql);
            $result = $statement->execute();
            $ret=['error'=>false, 'message'=>$result->getAffectedRows().' registro(s) retornado(s)', 'table'=>[], 'primeiroRegistro'=>[]];
            
            //formata retornos diferentes para sql de busca e alteracao
            if(in_array(explode(' ', trim($sql))[0],['INSERT', 'UPDATE', 'DELETE']) ){
                $ret['message']=$result->getAffectedRows().' linha(s) afetada(s)';
            } else {
                $ret['table']=$result->getResource()->fetchAll(\PDO::FETCH_ASSOC);
                $ret['primeiroRegistro']=(sizeof($ret['table']))?$ret['table'][0]:[];
            } 
            return $ret;
        } catch (\Exception $e) {
            $ret['error']=1;
            $ret['message']=$e->getMessage();
            return $ret;
        }
    }
    
    private function montaNomeTabela($namespace){
        $ultima_barra = strrpos($namespace, '\\')+1;
        $nome_classe = substr($namespace, $ultima_barra);
        $nome_tabela = strtolower(str_replace(' ', '_', trim(preg_replace("([A-Z])", " $0", $nome_classe))));
        return $nome_tabela;
    }
}