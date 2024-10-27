<?php

class Jp7_Interadmin_JSTree
{
    public $tree = [];
    public $types = [];
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

        $types = $parentTipo->getChildren($options);
        foreach ($types as $type) {
            // Criando o Node JSON
            $nome_lang = ($lang->prefix && $type->{'nome'.$lang->prefix}) ? $type->{'nome'.$lang->prefix} : $type->nome;
            $node = $this->createTipoNode($nome_lang, $type);
            if (!$node) {
                continue;
            }
            $tree[] = $node;
            // Aqui entra a recursão
            $this->addTipo($node->children, $type, $nivel + 1);
            if (count($node->children) == 0) {
                unset($node->children); // Bug jsTree progressive_render
            }
        }
    }

    public function createTipoNode($nome_lang, $type)
    {
        $node = (object) [
            'text' => $nome_lang,
                'id' => $type->type_id,
            'data' => [
                //'type_id' => $type->type_id,
                'model_type_id' => $type->model_type_id,
                'class' => $type->class,
            ],
            'children' => [],
        ];
        if ($type->icone) {
            $node->icon = $this->getIconeUrl($type->icone);
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
