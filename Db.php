<?php

class Db extends PDO{
    
    /**
     * @var _fetch_mode Mode of search returns
     */
    private $_fetch_mode = array(
        "arr" => parent::FETCH_ASSOC,
        "obj" => parent::FETCH_OBJ,
        "num" => parent::FETCH_NUM,
    );
    
    /**
     * @var _parameter Parameters to insert value to statement
     */
    private $_parameter = array(
        "null" => parent::PARAM_NULL,
        "int" => parent::PARAM_INT,
        "str" => parent::PARAM_STR,
        "lob" => parent::PARAM_LOB,
        "stmt" => parent::PARAM_STMT,
        "bool" => parent::PARAM_BOOL,
        
    );
    
    /**
     * @var conn variable to control PDO
     */
    private $_conn = null;
    
    /**
     * @var statement  returns status of PDO execution 
     */
    private $_statement = null;
    
    /**
     * @var type $_parametros array(array(variavel,$valor,$parametro),array(variavel,$valor,$parametro))
     */
    private $_parametros = array();
    
    /**
     * @var type $_valores array(array(variavel,$valor,$parametro),array(variavel,$valor,$parametro))
     */
    private $_valores = array();
    
    /**
     * @var type Last Id
     */
    private $last_id = 0;
	
    static private $instance;
    /**
     * Constructer method
     * @global __construct
     */
    public function __construct(){
		$this->_conn = parent::__construct(PDO_DRIVER . ':host=' . PDO_HOST . ';port=' . PDO_PORT . ';dbname=' . PDO_DB,PDO_USER,PDO_PASS, array(parent::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
		parent::setAttribute(parent::ATTR_ERRMODE, parent::ERRMODE_EXCEPTION);	
    }
    
    /**
     * @return instance
     */	
    static function getInstance() {
        if(!(DB::$instance instanceof DB)) {
                DB::$instance = new DB();
        }		
        return DB::$instance;
    }
    
    /**
     * @param type $host = HOST
     * @param type $db = DATABASE
     * @param type $user = USER
     * @param type $pass = PASSWORD
     * @param type $driver = DRIVER
     * @param type $port = PORT
     * @param type $options = OPTIONS
     */
    public function newConnection($host = "",$db = "",$user = "",$pass = "",$driver = "mysql",$port = 3306){
        if(empty($host))
            $this->_conn = parent::__construct(PDO_DRIVER . ':host=' . PDO_HOST . ';port=' . PDO_PORT . ';dbname=' . PDO_DB,PDO_USER,PDO_PASS);
        else
            $this->_conn = parent::__construct($driver . ':host=' . $host . ';port=' . $port . ';dbname=' . $db,$user,$pass);
    }
    
    /**
     * Close connection
     */
    public function closeConnection(){
        $this->conn = null;
    }
    
    /**
     * Execute query
     * @param type $query Inform the query
     * @example query insert into (id,nome) values (:id,:nome) ou values (?,?)
     * @example values for insert array(":id" => 1,":nome" => "test") ou array(1,"test")
     * @example retorn for select obj (objeto) ou arr (array) - retorn
     * @return deleted Retorns quantity of registrations deleted
     */
    public function executeSql($query,array $values = null,$retorno = "arr"){
        
        if(preg_match("/(insert|select|update)/i",$query)){
            
            parent::beginTransaction(); 
            
            $this->_statement = parent::prepare($query);
            
            if(preg_match("/select/i",$query))
                $this->_statement->setFetchMode(parent::FETCH_ASSOC); #default array
            
            if(count($this->_valores) > 0){
                foreach($this->_valores as $value){
                    $parametro = isset($value['parametro']) && !empty($value['parametro']) ? $this->_parameter[$value['parametro']] : $this->_parameter['int'];
                    $this->_statement->bindValue($value['var'], $value['value'], $parametro); 
                }
            }
            
            if(count($this->_parametros) > 0){
                foreach($this->_parametros as $value){
                    $parametro = isset($value['parametro']) && !empty($value['parametro']) ? $this->_parameter[$value['parametro']] : $this->_parameter['int'];
                    $this->_statement->bindParam($value['var'], $value['value'], $parametro); 
                }
            }
            
            if(!empty($values))
                $this->_statement->execute($values);
            else
                $this->_statement->execute();
            
            if(preg_match('/insert/i',$query))
                $this->last_id = parent::lastInsertId();
            
            parent::commit();
            
            
        }elseif(preg_match("/delete/i",$query)){
            
            return $this->execute($query);
            
        }
        
    }
    
    /**
     * Set values for query
     * @param type $arr Array array(array("var" => $variavel,"value" => $valor,"parametro" => $parametro),array("var" => $variavel,"value" => $valor,"parametro" => $parametro))
     * @param type parametro parameter can be null dont need conter in array. Default: int
     * @param type $variavel :tipo :id
     * @param type $parametro (null,int,str,lob,stmt,bool)
     */
    public function setValue(array $arr){
        $this->_valores = $arr;
    }
    
    /**
     * Set parameters to use in query
     * @param type $arr Array array(array("var" => $variavel,"value" => $valor,"parametro" => $parametro),array("var" => $variavel,"value" => $valor,"parametro" => $parametro))
     * @param type parametro parameter can be null dont need conter in array. Default: int
     * @param type $variavel :tipo :id
     * @param type $parametro (null,int,str,lob,stmt,bool)
     */
	public function setParameter(array $arr){
        $this->_parametros = $arr;
		// $this->_parametros = array_merge($this->_parametros, $arr);
    }
    
    /**
     * @param type $query Query for execution there is no limit or specifications
     * @return type $retorno in case query is executed
     */
    public function execute($query){
        
        parent::beginTransaction();
            
        $retorno = parent::exec($query);
        
        if(preg_match('/insert/i',$query))
            $this->last_id = parent::lastInsertId();
        
        parent::commit();

        return $retorno;
        
    }
    
    /**
     * @return fetch returns a registration
     */
     public function fetch($mode = null){
        if(empty($mode))
            return $this->_statement->fetch();
        else
            return $this->_statement->fetch($this->_fetch_mode[$mode]);
    }
    
    /**
     * @return fetchAll returns all registrations
     */
    public function fetchAll($mode = null){
        if(empty($mode))
            return $this->_statement->fetchAll();
        else
            return $this->_statement->fetchAll($this->_fetch_mode[$mode]);
    }
    
    /**
     * @return id Returns last id inserted
     */
    public function lastId(){
        return $this->last_id;
    }
    
    /**
     * @return number of registrations
     */
    public function getNumRows(){
        return $this->_statement->rowCount();
    }
    
    /**
     * @return quantity of columns of the table
     */
	
	public function getColumnCount() {
		return $this->_statement->columnCount();
	}
    
    /**
     * @return name of columns 
     */
	
	public function getColumnMeta($i) {
		return $this->_statement->getColumnMeta($i);
	}
}
?>