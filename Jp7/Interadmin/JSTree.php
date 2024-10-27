<?php

class Jp7_Interadmin_JSTree
{
    public $tree = [];
    public $tipos = [];
    public $options = [];
    protected static $permissionsLevel = 3;

    public function __construct($options = [], $root_type_id = 0)
    {
        global $lang;

        $this->options = $options + ['permissions_level' => static::$permissionsLevel];

        if (!$options['static']) {
            $this->addTipo($this->tree, new InterAdminTipo($root_type_id));
        }
    }

    public static function setNivelPermissao($nivel)
    {
        static::$permissionsLevel = $nivel;
    }

    public static function getNivelPermissao()
    {
        return static::$permissionsLevel;
    }

    public function addTipo(&$tree, $parentTipo, $nivel = 0)
    {
        global $lang;

        $options = [
            'fields' => ['nome', 'parent_type_id', 'model_type_id', 'icone'],
            'use_published_filters' => true,
            'class' => 'InterAdminTipo',
        ];

        if ($nivel == 0) {
            $options['where'][] = ($this->options['admin']) ? "admin <> ''" : "admin = ''";
        }
        if ($nivel < $this->options['permissions_level']) {
            $options = InterAdmin::mergeOptions($this->options, $options);
        }
        if ($lang->prefix) {
            $options['fields'][] = 'nome'.$lang->prefix;
        }

        $tipos = $parentTipo->getChildren($options);
        foreach ($tipos as $tipo) {
            // Criando o Node JSON
            $nome_lang = ($lang->prefix && $tipo->{'nome'.$lang->prefix}) ? $tipo->{'nome'.$lang->prefix} : $tipo->nome;
            $node = $this->createTipoNode($nome_lang, $tipo);
            if (!$node) {
                continue;
            }
            $tree[] = $node;
            // Aqui entra a recursão
            $this->addTipo($node->children, $tipo, $nivel + 1);
            if (count($node->children) == 0) {
                unset($node->children); // Bug jsTree progressive_render
            }
        }
    }

    public function createTipoNode($nome_lang, $tipo)
    {
        $node = (object) [
            'text' => $nome_lang,
                'id' => $tipo->type_id,
            'data' => [
                //'type_id' => $tipo->type_id,
                'model_type_id' => $tipo->model_type_id,
                'class' => $tipo->class,
            ],
            'children' => [],
        ];
        if ($tipo->icone) {
            $node->icon = $this->getIconeUrl($tipo->icone);
        }

        return $node;
    }

    public function toJson()
    {
        return json_encode($this->tree);
    }

    public function addNode($label, $callback = '', $icone = '')
    {
        $node = $this->createNode($label, $callback, $icone);
        $this->tree[] = $node;

        return $node;
    }

    public function createNode($label, $callback = '', $icone = '')
    {
        $node = (object) [
            'text' => $label,
            'data' => [
                'callback' => $callback,
            ]
        ];
        if ($icone) {
            $node->icon = $this->getIconeUrl($icone);
        }

        return $node;
    }

    public function getIconeUrl($icone)
    {
        return DEFAULT_PATH.'/img/icons/'.$icone.'.png';
    }
}
