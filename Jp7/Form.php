<?php

/**
 * @author JP7
 */
class Jp7_Form extends Zend_Form
{
    /**
     * Constructor.
     *
     * Registers form view helper as decorator
     *
     * @param mixed $options
     */
    public function __construct($options = null)
    {
        parent::__construct($options);
        // Adicionado para que campos dentro de Jp7/Form possam ser exibidos
        $this->addPrefixPath('Jp7_Form', 'Jp7/Form/');
        // Usado pelos validators
        //$this->addElementPrefixPath('Jp7', 'Jp7/');
    }

    /**
     * Creates a Jp7_Mail with the data sent from the form.
     *
     * @param InterAdmin $record
     * @param string     $template Path to the view to be used as a template.
     *
     * @return Jp7_Mail
     */
    public function createMail(InterAdmin $record, $options = [])
    {
        // Configuração
        $config = Zend_Registry::get('config');

        $default = [
            'template' => 'templates/email.phtml',
            'title' => '',
            'subject' => '',
            'message' => '',
            'config' => $config,
            'recipients' => false,
        ];
        $options = $options + $default;

        // Layout
        $view = Zend_Layout::getMvcInstance()->getView();
        // Html
        $options['message'] .= $this->prepareMailHtml($record, $options);
        $html = $view->partial($options['template'], $options);

        // Text Plain
        $text = strip_tags($html);

        // Email
        $mail = new Jp7_Mail();
        $mail->setSubject($options['subject']);
        $mail->setBodyHtml($html);
        $mail->setBodyText($text);

        // Definindo destinatários somente se recipients estiver setado
        if ($options['recipients'] !== false) {
            if ($config->isProducao()) {
                if (is_array($options['recipients']) && count($options['recipients'])) {
                    // Primeiro é To
                    $recipient = array_shift($options['recipients']);
                    $mail->addTo($recipient->email, $recipient->name);
                    // Restante é CC
                    foreach ($options['recipients'] as $recipient) {
                        $mail->addCc($recipient->email, $recipient->name);
                    }
                }
                $mail->addBcc($config->name_id.'@sites.jp7.com.br');
            } else {
                $mail->addTo('debug+'.$config->name_id.'@jp7.com.br');
            }
        }

        return $mail;
    }

    public function prepareMailHtml(InterAdmin $record, $options = [])
    {
        global $lang;

        $linebreak = '<br />'."\r\n";

        if ($options['subject']) {
            $html = '<b>'.$options['subject'].'</b><br />
				<hr size=1 color="#666666"><br />';
        }

        foreach ($record->getAttributesCampos() as $type => $field) {
            if (!$field['form']) {
                continue;
            }
            $html .= '<b>'.$this->_getMailLabel($field).'</b>: '.$this->_getMailValue($record, $field).$linebreak;
            if ($field['separador']) {
                $html .= $linebreak;
            }
        }

        $html .= '<br />
			<hr size="1" color="#666666">
			<font size="1" color="#333333">
				<b>Idioma:</b> '.$lang->lang.'<br />
				<b>Data - Hora de Envio:</b> '.date('d/m/Y - H:i:s').'<br />
				<b>IP:</b> '.$_SERVER['REMOTE_ADDR'].'<br />
				<br />
			</font>';

        return $html;
    }

    /**
     * FIXME temporário.
     */
    protected function _getMailLabel($field)
    {
        if ($field['label']) {
            return $field['label'];
        } elseif ($field['nome'] instanceof InterAdminTipo) {
            return $field['nome']->nome;
        } else {
            return $field['nome'];
        }
    }

    /**
     * FIXME temporário.
     */
    protected function _getMailValue($record, $field)
    {
        $value = $record->{$field['nome_id']};

        // @todo Falta select_multi
        // Relacionamentos
        if ($field['nome'] instanceof InterAdminTipo) {
            $tipo = $field['nome'];

            if (is_numeric($value)) {
                $value = $tipo->findById($value);
            }
            if ($value instanceof InterAdminAbstract) {
                return $value->getStringValue();
            }
        // Texto
        } elseif (strpos($field['tipo'], 'text_') === 0) {
            $value = '<div style="background:#F2F2F2;margin-top:3px;padding:5px;border:1px solid #CCC">
				<font face="verdana" size="2" color="#000000" style="font-size:13px">'.toHtml($value).'</font>
			</div>';

            return $value;
        // String
        } else {
            return $value;
        }
    }

    /**
     * Create an array of elements from an InterAdminTipo.
     *
     * @param InterAdminTipo $tipo
     *
     * @return array
     */
    public function createElements(array $campos, $prefix = '', array $options = [])
    {
        $options = $options + [
            'label_suffix' => ':',
            'required_suffix' => '',
        ];

        $elements = [];
        foreach ($campos as $campo) {
            if ($campo['form']) {
                $element = $this->createElementFromCampo($campo, $prefix, $options);
                $elements[$element->getId()] = $element;
            }
        }

        return $elements;
    }

    public function createElementFromCampo($campo, $prefix, $options)
    {
        list($prefixCampo, $suffixCampo) = explode('_', $campo['tipo']);

        $name = $prefix.$campo['nome_id'];
        $label_suffix = $options['label_suffix'].(($campo['obrigatorio']) ? $options['required_suffix'] : '');

        $options = [
            'label' => $campo['nome'].$label_suffix,
            'description' => $campo['ajuda'],
            'required' => (bool) $campo['obrigatorio'],
        ];

        switch ($prefixCampo) {
            case 'text':
                if ($campo['xtra'] == 'S') {
                    $options['class'] = 'html';
                } elseif ($campo['xtra'] == 'html_light') {
                    $options['class'] = 'html_light';
                }
                $element = $this->createElement('textarea', $name, $options);
                if ($campo['tamanho']) {
                    $element->setOptions(['rows' => $campo['tamanho']]);
                }
                break;
            case 'select':
                $registros = $campo['nome']->find();

                $multiOptions = [];
                foreach ($registros as $registro) {
                    $multiOptions[(string) $registro] = $registro->getStringValue();
                }
                $options['multiOptions'] = $multiOptions;
                // Label não é o $campo['nome'] como nos outros elementos
                $options['label'] = $campo['label'].$label_suffix;

                if ($suffixCampo == 'multi') {
                    $element = $this->createElement('multicheckbox', $name, $options);
                } elseif ($campo['xtra'] == 'radio') {
                    if (!$campo['obrigatorio']) {
                        $options['multiOptions'] = ['' => 'Nenhum'] + $options['multiOptions'];
                    }
                    $element = $this->createElement('radio', $name, $options);
                } else {
                    $options['multiOptions'] = ['' => '-- selecione --'] + $options['multiOptions'];
                    $element = $this->createElement('select', $name, $options);
                }
                break;
            case 'date':
                $temHora = $campo['xtra'] == '0' || strpos($campo['xtra'], 'datetime') !== false;

                $options['showTime'] = $temHora;
                if (strpos($campo['xtra'], 'nocombo') === false) {
                    $element = $this->createElement('datecombo', $name, $options);
                } else {
                    $element = $this->createElement('date', $name, $options);
                    //$element->setAttrib('placeholder', 'Dia/Mês/Ano' . ($temHora ? ' 00:00' : ''));
                }
                $element->addValidator(new Zend_Validate_Date('yyyy-MM-dd'));
                break;
            case 'file':
                $element = $this->createElement('filepreview', $name, $options);
                $element->addValidator('ExcludeExtension', false, ['php', 'exe']);
                $element->addFilter('Rename', uniqid());
                break;
            case 'char':
                $element = $this->createElement('checkbox', $name, $options);
                $element->setCheckedValue('S');
                break;
            case 'password':
                $element = $this->createElement('password', $name, $options);
                break;
            case 'varchar':
            default:
                $element = $this->createElement('text', $name, $options);
                break;
        }

        return $element;
    }

    public function populate(array $values, $prefix = '')
    {
        if ($values instanceof InterAdmin) {
            $values = $values->attributes;
        }
        foreach ($values as $key => $value) {
            if ($value instanceof InterAdminAbstract) {
                $values[$key] = (string) $value;
            } elseif (is_array($value)) {
                // Força conversão de objetos em string
                $values[$key] = explode(',', implode(',', $value));
            }
        }
        if ($prefix) {
            foreach ($values as $key => $value) {
                $values[$prefix.$key] = $value;
                unset($values[$key]);
            }
        }
        parent::populate($values);
    }

    public function setFilesDestination($path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        foreach ($this->getElements() as $element) {
            if ($element instanceof Zend_Form_Element_File) {
                $element->setDestination($path);
            }
        }
        /*$fieldsValues = array(
            'id_tipo' => 0,
            'id' => 0,
            'tipo' => $this->getExtension(),
            'parte' => 0,
            'keywords' => $this->nome,
            'lang' => $lang->lang
        );

        $banco = new InterAdminArquivoBanco();
        $id_arquivo_banco = $banco->addFile($fieldsValues);*/
    }
}

/*
$usuarioTipo = new Ciintranet_UsuarioTipo();
$form = new Jp7_Form();
$elements = $form->createElements($usuarioTipo->getFields());
$form->addElements($elements);

$form->populate($usuarioLogado->attributes);

$form->setAction('atualizar_ok.php');

// Somente em MVC
echo $form->render();
// Somente fora do ambiente MVC
echo $form->render(new Jp7_Form_View());
*/
