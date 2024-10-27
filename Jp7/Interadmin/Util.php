<?php

use Jp7\Interadmin\Record;
use Jp7\Interadmin\Type;

class Jp7_Interadmin_Util
{
    /**
     * Exports records and their children.
     *
     * @param InterAdminTipo $typeObj InterAdminTipo where the records are.
     * @param array          $ids     Array de IDs.
     *
     * @return array InterAdmin[]
     */
    public static function export(InterAdminTipo $typeObj, array $ids, $use_id_string = false)
    {
        $options = [
            'class' => 'InterAdmin',
            'fields_alias' => false,
        ];

        $exports = $typeObj->find($options + [
            'where' => 'id IN('.implode(',', $ids).')',
        ]);
        if ($use_id_string) {
            self::_prepareForIdString($exports, $typeObj);
        }

        $typesChildren = $typeObj->getInterAdminsChildren();
        foreach ($exports as $export) {
            self::_exportChildren($export, $typesChildren, $use_id_string, $options);
        }

        return $exports;
    }

    protected static function _exportChildren($export, $typesChildren, $use_id_string, $options)
    {
        $export->_children = [];
        foreach ($typesChildren as $typeChildrenArr) {
            $typeChildren = $export->getChildrenTipo($typeChildrenArr['type_id']);

            $children = $typeChildren->find($options  + [
                'where' => "deleted = ''",
            ]);
            if ($use_id_string) {
                self::_prepareForIdString($children, $typeChildren);
            }

            $typesGrandChildren = $typeChildren->getInterAdminsChildren();
            foreach ($children as $child) {
                self::_exportChildren($child, $typesGrandChildren, $use_id_string, $options);
                $child->setParent(null);
            }
            $export->_children[$typeChildren->type_id] = $children;
        }
        $export->setTipo(null);
    }

    /**
     * @return void
     */
    protected static function _prepareForIdString($records, $type)
    {
        foreach ($records as $record) {
            $record->_relations = [];
        }

        foreach ($type->getRelationships() as $relation => $data) {
            if ($data['type'] || $data['multi']) {
                continue;
            }
            foreach ($records as $record) {
                $fk = $record->{$relation.'_id'};
                $query = (clone $data['query']);
                $related = $query->select('id_string')->find($fk);
                if ($related && $related->id_string) {
                    $record->_relations[$relation] = $related->id_string;
                }
            }
        }
    }

    /**
     * @return void
     */
    protected static function importRelationsFromIdString($record, $relations, $bind_children = false)
    {
        if (!$relations) {
            return;
        }
        $relationships = $record->getType()->getRelationships();
        $aliases = $record->getType()->getFieldsAlias();
        foreach ($relations as $relation => $id_string) {
            $query = clone $relationships[$relation]['query'];
            if ($bind_children) {
                $query->orderByRaw('parent_id = '.$record->parent_id.' DESC');
            }
            $related = $query->select('id')
                ->where('id_string', $id_string)
                ->orderByRaw("deleted = '' DESC")
                ->first();

            if ($related) {
                $column = array_search($relation.'_id', $aliases);
                $record->$column = $related->id;
            }
        }
    }

    /**
     * Imports records and their children with a new ID.
     *
     * @param array          $records
     * @param InterAdminTipo $typeObj
     * @param InterAdmin     $parent
     * @param bool           $import_children defaults to TRUE
     * @param bool           $use_id_string   defaults to FALSE
     * @param bool           $bind_children   Children 1 has a relationship with Children 2, when copying, this relationship needs to be recreated
     */
    public static function import(array $records, InterAdminTipo $typeObj, InterAdmin $parent = null, $import_children = true, $use_id_string = false, $bind_children = false)
    {
        $returnIds = [];
        foreach ($records as $record) {
            $oldId = $record->id;
            $record->setTipo($typeObj);
            $children = $record->_children;
            $relations = $record->_relations;
            self::prepareNewRecord($record, $parent);

            if ($use_id_string) {
                self::importRelationsFromIdString($record, $relations);
            }

            $record->save();

            if ($import_children) {
                self::_importChildren($record, $children, $use_id_string, $bind_children);
            }
            $returnIds[] = [
                'id' => $oldId,
                'new_id' => $record->id
            ];
        }

        return $returnIds;
    }

    protected static function prepareNewRecord($record, $parent)
    {
        $record->id = 0;
        unset($record->id_slug);
        unset($record->_children);
        unset($record->_relations);

        $record->setParent($parent);
    }

    public static function _importChildren($record, $children, $use_id_string, $bind_children)
    {
        foreach ($children as $child_type_id => $type_children) {
            $childTipo = InterAdminTipo::getInstance($child_type_id);
            $childTipo->setParent($record);

            foreach ($type_children as $child) {
                $child->setTipo($childTipo);
                $grandChildren = $child->_children;
                $childRelations = $child->_relations;

                self::prepareNewRecord($child, $record);

                if ($use_id_string || $bind_children) {
                    self::importRelationsFromIdString($child, $childRelations, $bind_children);
                }

                $child->save();
                self::_importChildren($child, $grandChildren, $use_id_string, $bind_children);
            }
        }
    }

    public static function copy(InterAdminTipo $typeObj, array $ids, InterAdminTipo $typeDestino, InterAdmin $parent = null)
    {
        global $use_id_string, $bind_children; // FIXME usado no intermail
        global $s_user;

        $use_id_string = false;
        $bind_children = false;

        if ($typeDestino->getInterAdminsTableName() != $typeObj->getInterAdminsTableName()) {
            throw new Exception('Não é possível copiar para tipos com tabela customizada.');
        }

        $beforCopyEvent = Interadmin_Event_BeforeCopy::getInstance();
        $beforCopyEvent->setIdTipo($typeObj->type_id);
        $beforCopyEvent->notify();

        $registros = self::export($typeObj, $ids, $use_id_string);

        foreach ($registros as $registro) {
            if ($typeObj->type_id == $typeDestino->type_id) {
                $registro->setTipo($typeDestino);
                if (isset($registro->varchar_key)) {
                    $registro->varchar_key = 'Cópia de '.$registro->varchar_key;
                }
            }
            $registro->publish = '';
        }

        $oldLogUser = InterAdmin::setLogUser($s_user['login'].' - combo copy');
        $returnIds = self::import($registros, $typeDestino, $parent, true, $use_id_string, $bind_children);
        InterAdmin::setLogUser($oldLogUser);

        if (Interadmin_Event_AfterCopy::getInstance()->hasObservers()) {
            foreach ($returnIds as $returnId) {
                $afterCopyEvent = Interadmin_Event_AfterCopy::getInstance();
                $afterCopyEvent->setIdTipo($typeDestino->type_id);
                $afterCopyEvent->setId($returnId['id']);
                $afterCopyEvent->setCopyId($returnId['new_id']);
                $afterCopyEvent->notify();
            }
        }

        return $returnIds;
    }

    public static function syncTipos($model)
    {
        $inheritedTipos = InterAdminTipo::findTiposByModel($model->type_id, [
            'class' => 'InterAdminTipo',
        ]);
        ?>
		&bull; <?= $model->type_id ?> - <?= $model->nome ?> <br />
		<div class="indent">
			<?php foreach ($inheritedTipos as $type) { ?>
				<?php
                $type->syncInheritance();
                $type->saveRaw();
                self::syncTipos($type);
                ?>
			<?php } ?>
		</div>
		<?php
    }

    /**
     * Helper da função _getCampoType.
     *
     * @param InterAdminTipo $campoTipo
     * @param bool           $isTipo
     * @param bool           $isMulti
     *
     * @return string Type para o PHPDoc
     */
    protected function _getCampoTypeClass($campoTipo, $isTipo, $isMulti)
    {
        if ($isTipo) {
            $retorno = 'InterAdminTipo';
        } else {
            $retorno = $campoTipo->class ? $campoTipo->class : 'InterAdmin';
        }
        if ($isMulti && $retorno) {
            $retorno .= '[]';
        }

        return $retorno;
    }

    protected static function _getTipoPhpDocCampo($type, $campo)
    {
        if (strpos($campo['tipo'], 'special_') === 0 && $campo['xtra']) {
            $isMulti = in_array($campo['xtra'], InterAdminField::getSpecialMultiXtras());
            $isTipo = in_array($campo['xtra'], InterAdminField::getSpecialTipoXtras());

            $retorno = self::_getCampoTypeClass($type->getCampoTipo($campo), $isTipo, $isMulti);
        } elseif (strpos($campo['tipo'], 'select_') === 0) {
            $isMulti = (strpos($campo['tipo'], 'select_multi') === 0);
            $isTipo = in_array($campo['xtra'], InterAdminField::getSelectTipoXtras());

            $retorno = self::_getCampoTypeClass($campo['nome'], $isTipo, $isMulti);
        } elseif (strpos($campo['tipo'], 'int') === 0 || strpos($campo['tipo'], 'id') === 0) {
            $retorno = 'int';
        } elseif (strpos($campo['tipo'], 'char') === 0) {
            $retorno = 'string';
        } elseif (strpos($campo['tipo'], 'date') === 0) {
            return 'Jp7_Date';
        } else {
            $retorno = 'string';
        }

        return $retorno;
    }

    public static function gerarClasseInterAdmin(InterAdminTipo $type, $gerarArquivo = true, $nomeClasse = '')
    {
        global $config;
        $prefixoClasse = ucfirst($config->name_id);

        if (!$nomeClasse) {
            $nomeClasse = $type->class;
        }

        $phpdoc = '/**'."\r\n";
        foreach ($type->getFields() as $campo) {
            $phpdoc .= ' * @property '.self::_getTipoPhpDocCampo($type, $campo).' $'.$campo['nome_id']."\r\n";
        }
        $phpdoc .= ' * @property Jp7_Date date_publish'."\r\n";
        $phpdoc .= ' */';

        $conteudo = <<<STR
<?php

$phpdoc
class {$nomeClasse} extends {$prefixoClasse}_InterAdmin {

}
STR;
        if ($gerarArquivo) {
            return self::salvarClasse($nomeClasse, $conteudo);
        } else {
            return $conteudo;
        }
    }

    public static function gerarClasseInterAdminTipo(InterAdminTipo $type, $gerarArquivo = true, $nomeClasse = '', $nomeClasseInterAdmin = '')
    {
        global $config;
        $prefixoClasse = ucfirst($config->name_id);

        if (!$nomeClasse) {
            $nomeClasse = $type->class_tipo;
        }
        if (!$nomeClasseInterAdmin) {
            $nomeClasseInterAdmin = $type->class;
        }
        if (!$nomeClasseInterAdmin) {
            $constname = InterAdminTipo::getDefaultClass().'::DEFAULT_NAMESPACE';
            if (defined($constname)) {
                $nomeClasseInterAdmin = constant($constname).'InterAdmin';
            } else {
                $nomeClasseInterAdmin = 'InterAdmin';
            }
        }
        $phpdoc = '/**'."\r\n";
        $phpdoc .= ' * @method '.$nomeClasseInterAdmin.'[] find'."\r\n";
        $phpdoc .= ' * @method '.$nomeClasseInterAdmin.' findFirst'."\r\n";
        $phpdoc .= ' * @method '.$nomeClasseInterAdmin.' findById'."\r\n";
        $phpdoc .= ' */';

        $conteudo = <<<STR
<?php

$phpdoc
class {$nomeClasse} extends {$prefixoClasse}_InterAdminTipo {
	const ID_TIPO = {$type->type_id};
}
STR;
        if ($gerarArquivo) {
            return self::salvarClasse($nomeClasse, $conteudo);
        } else {
            return $conteudo;
        }
    }
    /**
     * Salva o conteudo da classe em arquivo
     * return array.
     */
    public static function salvarClasse($nomeClasse, $conteudo)
    {
        global $c_interadminConfigPath;

        $arquivo = dirname($c_interadminConfigPath).'/classes/'.str_replace('_', '/', $nomeClasse).'.php';
        if (!is_file($arquivo)) {
            @mkdir(dirname($arquivo), 0777, true);

            $retorno = file_put_contents($arquivo, $conteudo);
            @chmod($arquivo, 0777);
            if ($retorno === false) {
                $avisos['erro'][] = 'Não foi possível gravar arquivo: "'.$arquivo.'". Verifique permissões no diretório.';
            } else {
                $avisos['sucesso'][] = 'Arquivo "'.$arquivo.'" gerado.';
            }
        } else {
            $avisos['erro'][] = 'Arquivo "'.$arquivo.'" já existe.';
        }

        return $avisos;
    }

    public static function getTiposChecksum()
    {
        global $db, $db_prefix;

        $rs = $db->Execute("CHECKSUM TABLE ".$db_prefix."_tipos");
        $row = $rs->FetchNextObj();
        return $row->Checksum;
    }
}
