<?
/**
 * Instancia registros da tabela interadmin_cliente_tipos
 *
 */
class InterAdminTipo{
	public $id;
	public $id_tipo;
	/**
	 * @param int $id_tipo
	 * @param varchar $_db_prefix
	 */
	function __construct($id_tipo, $_db_prefix=''){
		$this->id_tipo=$id_tipo;
		$this->db_prefix=($_db_prefix)?$_db_prefix:$GLOBALS['db_prefix'];
	}
	function __toString(){
		return $this->id_tipo;
	}
	function __get($var){
		if($var == 'interadminsOrderby'){
			$campos = $this->getCampos();
			if($campos){
				foreach($campos as $key=>$row){
					if($row[orderby])$tipo_orderby[$row[orderby]]=$key;
				}
				if($tipo_orderby){
					ksort($tipo_orderby);
					$tipo_orderby=implode(",",$tipo_orderby);
				}
				
				$this->$var = $tipo_orderby;
			}
			
			if (!count($tipo_orderby)) {
				$this->$var = 'date_publish DESC';
			}
			
			
			return $this->$var;
		}
	}
	/**
	 * @return mixed
	 */
	function getFieldsValues($fields){
		return jp7_fields_values($this->db_prefix.'_tipos', 'id_tipo', $this->id_tipo, $fields);
	}
	/**
	 * @return object
	 */
	function getParent($cache = TRUE){
		if ($this->parentInterAdminTipo && $cache) return $this->parentInterAdminTipo;
		else{
			$parent = $this->getFieldsValues('parent_id_tipo');
			if ($parent) {
				eval('$this->parentInterAdminTipo = new ' . get_class($this) . '(' . $parent . ', "' . $this->db_prefix . '");');
				return $this->parentInterAdminTipo;
			} else {
				return FALSE;
			}
		}
	}
	/**
	 * @return array
	 */
	function getChildren($fields = NULL){
		global $db;
		global $jp7_app;
		$sql="SELECT id_tipo" . (($fields) ? ',' . implode(',', $fields) : '') . " FROM ".$this->db_prefix."_tipos".
		" WHERE parent_id_tipo=".$this->id_tipo.
		((!$jp7_app) ? " AND deleted_tipo=''" : "");
		$rs = $db->Execute($sql)or die(jp7_debug($db->ErrorMsg(),$sql));
		while($row = $rs->FetchNextObj()){
			eval('$interadmintipo = new ' . get_class($this) . '(' . $row->id_tipo . ', ' . $this->db_prefix . ');');
			foreach((array)$fields as $field){
				$interadmintipo->$field = $row->$field;
			}
			$interadminsTipos[] = $interadmintipo;
		}
		$rs->Close();
		return $interadminsTipos;
	}
	/**
	 * @return array
	 */
	function getInterAdmins(/*$where = null, */$fields = NULL){
		global $db;
		$sql = "SELECT id" . (($fields) ? ',' . implode(',', $fields) : '') . " FROM " . $this->db_prefix.
		" WHERE id_tipo=" . $this->id_tipo.
		//(($where) ? " AND " . $where : '').
		" ORDER BY " . $this->interadminsOrderby;
		//$rs = $db->Execute($sql)or die(jp7_debug($db->ErrorMsg(),$sql));
		$rs = $db->Execute($sql)or die(jp7_debug($db->ErrorMsg(),$sql));
		while($row = $rs->FetchNextObj()){
			$interadmin = new InterAdmin($row->id, $this->db_prefix);
			foreach((array)$fields as $field){
				$interadmin->$field = $row->$field;
			}
			$interadmins[] = $interadmin;
		}
		$rs->Close();
		return $interadmins;
	}
	/**
	 * @return array
	 */
	function getFirstInterAdmin($where = null){
		global $db;
		$sql = "SELECT id FROM " . $this->db_prefix.
		" WHERE id_tipo=" . $this->id_tipo.
		(($where) ? " AND " . $where : '').
		" ORDER BY " . $this->interadminsOrderby.
		" LIMIT 1";
		//$rs = $db->Execute($sql)or die(jp7_debug($db->ErrorMsg(),$sql));
		$rs = $db->Execute($sql)or die(jp7_debug($db->ErrorMsg(),$sql));
		while($row = $rs->FetchNextObj()){
			$interadmin = new InterAdmin($row->id, $this->db_prefix);
		}
		$rs->Close();
		return $interadmin;
	}
	/**
	 * @return ?
	 */
	function getModel(){
		$model = $this->getFieldsValues('model_id_tipo');
		if ($model) {
			$model_obj = new InterAdminTipo($model);
			return $model_obj->getModel();
		} else {
			return $this;
		}
	}
	/**
	 * @return ?
	 */
	function getCampos(){
		$model = $this->getModel();
		$campos				= $model->getFieldsValues('campos');
		$campos_parameters	= array("tipo", "nome", "ajuda", "tamanho", "obrigatorio", "separador", "xtra", "lista", "orderby", "combo", "readonly", "form", "label", "permissoes", "default");
		$campos				= split("{;}", $campos);
		for($i = 0; $i < count($campos); $i++){
			$parameters = split("{,}", $campos[$i]);
			if($parameters[0]){
				$A[$parameters[0]]['ordem'] = ($i+1);
				$isSelect = (strpos($parameters[0], 'select_') !== false);
				for($j = 0 ; $j < count($parameters); $j++){
					$A[$parameters[0]][$campos_parameters[$j]] = $parameters[$j];
				}
				if($isSelect && $A[$parameters[0]]['nome']!='all'){
					$id_tipo = $A[$parameters[0]]['nome'];
					$Cadastro_r = new InterAdminTipo($id_tipo);	
					$A[$parameters[0]]['children'] = $Cadastro_r->getCampos();
					//jp7_print_r($parameters[0]);
					//jp7_print_r($A[$parameters[0]]['nome']);
				}
			}
		}
		return $A;
		//return interadmin_tipos_campos($this->getFieldsValues('campos'));
	}
	/**
	 * @return string
	 */
	function getURL() {
		global $c_wwwroot, $implicit_parents_names, $seo;
		$url='';
		$url_arr='';
		$parent=$this;
		while($parent) {
			if ($seo) {
				if (!in_array($parent->getFieldsValues('nome'), $implicit_parents_names)) $url_arr[] = toSeo($parent->getFieldsValues('nome'));
			} else {
				$url_arr[] = toId($parent->getFieldsValues('nome'));
			}
			$parent = $parent->getParent();
		}
		$url_arr=array_reverse((array)$url_arr);
		if ($seo) {
			$url = jp7_path($c_wwwroot) . join("/",$url_arr);
		} else {
			$url=join("_",$url_arr);
		}
		if (!$seo) {
			$url=substr_replace($url,'/',strpos($url,'_'),1);
			$url.=(count($url_arr)>1)?'.php':'/';
		}
		return $url;
	}
}
?>