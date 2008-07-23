<?
/**
 * Instancia registros da tabela interadmin_cliente
 *
*/
class InterAdmin{
	public $id;
	public $id_tipo;
	/**
	 * @param int $id
	 * @param varchar $_db_prefix
	 * @return object
	 */
	function __construct($id = '', $_db_prefix = ''){
		$this->id = $id;
		$this->db_prefix = ($_db_prefix) ? $_db_prefix : $GLOBALS['db_prefix'];
	}
	function __get($var){
		if($var == 'id'){
			return $this->$var;
		}
	}
	function __toString(){
		return $this->id;
	}	
	/**
	 * @return mixed
	 */
	function getFieldsValues($fields, $forceAsString=false){   
		/*if(!$this->fieldsValues)*/$this->fieldsValues=jp7_fields_values($this->db_prefix , 'id', $this->id, $fields/*, true*/);
		foreach ((array)$this->fieldsValues as $key=>$value) {
			$this->$key = $value;
		}
		if($forceAsString){
			foreach($this->fieldsValues as $key=>$value){
				if(strpos($key,"select_")===0)$this->fieldsValuesAsString->$key=jp7_fields_values($this->db_prefix , 'id', $value, 'varchar_key');
				else $this->fieldsValuesAsString->$key=$value;
			}
			return $this->fieldsValuesAsString;
		}else{
			/*
			foreach($this->fieldsValues as $key=>$value){
				if(strpos($key,'varchar_') === 0){
					$this->fieldsValues->$key = toHTML($value);
				}
			}
			*/
			return $this->fieldsValues;
		}
	}
	/**
	 * @return mixed
	 */
	function setFieldsValues($fields_values){
		if ($this->id) {
			jp7_db_insert($this->db_prefix, 'id', $this->id, $fields_values);
		} else {
			$this->id = jp7_db_insert($this->db_prefix, 'id', 0, $fields_values);
		}
	}
	/**
	 * @return object
	 */
	function getTipo(){
		if(!$this->id_tipo)$this->id_tipo=new InterAdminTipo($this->getFieldsValues('id_tipo'), $this->db_prefix);
		return $this->id_tipo;
	}
	/**
	 * @param mixed $tipo
	 * @return array
	 */
	function getChildren($tipo, $orderby = '') {
		global $db;
		global $jp7_app;
		$children_tipo = new InterAdminTipo($tipo);
		$sql="SELECT id FROM ".$this->db_prefix.
		" WHERE 1=1".
		((is_numeric($tipo)) ? " AND id_tipo=".$tipo : " AND nome='".$tipo."'").
		((!$jp7_app) ? " AND deleted<>'S'" : "").
		" AND parent_id=".$this->id.
		(($orderby)?" ORDER BY ".$orderby:" ORDER BY " . $children_tipo->interadminsOrderby);
		//echo $sql;
		$rs = $db->Execute($sql)or die(jp7_debug($db->ErrorMsg(),$sql));
		while($row = $rs->FetchNextObj()){
			$interadmins[]=new InterAdmin($row->id, $this->db_prefix);
		}
		$rs->Close();
		return $interadmins;
	}
	/**
	 * @return string
	 */
	function getURL(){
		return $this->getTipo()->getURL().'?id='.$this->id;
	}
}
?>