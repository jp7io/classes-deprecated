<?php

class Jp7_Interadmin_Soap_UsuarioTipo extends InterAdminTipo
{
    const DEFAULT_FIELDS_ALIAS = true;

    public function __construct($options = [])
    {
        /*
         * @global Define o $type_id em que são gravados os usuários com acesso.
         */
        global $c_tipos_permissoes_xml_csv;
        if (!$c_tipos_permissoes_xml_csv) {
            throw new Exception('Variável c_tipos_permissoes_xml_csv não está definida.');
        }
        parent::__construct($c_tipos_permissoes_xml_csv, $options);
    }

    public function login($username, $password)
    {
        $usuario = $this->findFirst([
            'fields' => ['secoes'],
            'where' => [
                "usuario = '".addslashes($username)."'",
                "senha = '".md5($password)."'",
            ],
            'use_published_filters' => true,
        ]);

        if ($usuario && $this->verifyIps($usuario)) {
            return $usuario;
        }
    }

    public function verifyIps($usuario)
    {
        $ips = $usuario->getIps([
            'fields' => ['ip'],
            'fields_alias' => true,
            'use_published_filters' => true,
        ]);

        //Verifica se IP é igual ou está na faixa de ip cadastrados
        $usuarioIp = $_SERVER['REMOTE_ADDR'];

        if ($ips) {
            foreach ($ips as $ip) {
                if ($ip->ip === '*.*.*.*') {
                    return true;
                }

                $ip->ip = '/'.addcslashes(str_replace('*', '[0-9]{1,3}', $ip->ip), '.').'/';
                if (preg_match($ip->ip, $usuarioIp)) {
                    return true;
                }
            }
        }
    }
}
