<?php

class InterAdminArquivoBanco
{
    public function __construct($options = [])
    {
        $this->db_prefix = ($options['db_prefix']) ? $options['db_prefix'] : $GLOBALS['db_prefix'];
    }

    /**
     * Adiciona arquivo ao banco e retorna ID.
     *
     * @param array $fieldsValues
     *
     * @return string id_file_banco
     */
    public function addFile($fieldsValues)
    {
        $id_file_banco = jp7_db_insert($this->getTableName(), 'id_file_banco', '', $fieldsValues);

        return str_pad($id_file_banco, 8, '0', STR_PAD_LEFT);
    }

    public function getTableName()
    {
        return $this->db_prefix.'_files_banco';
    }
}
