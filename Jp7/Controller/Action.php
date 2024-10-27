<?php

/**
 * Adiciona configurações comuns da JP7 e __call de métodos inexistentes para
 * templates ao Controller da Zend.
 *
 * @category Jp7
 */
class Jp7_Controller_Action extends Zend_Controller_Action
{
    /**
     * @var InterAdminTipo
     */
    protected static $tipo;
    /**
     * @var InterAdmin
     */
    protected static $record;

    public function init()
    {
        $config = Zend_Registry::get('config');
        if (preg_match('~\b(alt|qa)\b~', $_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_USER_AGENT'])) {
            if (!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] != $config->name_id || $_SERVER['PHP_AUTH_PW'] != $config->name_id) {
                header('WWW-Authenticate: Basic realm="'.$config->name.'"');
                header('HTTP/1.0 401 Unauthorized');
                echo '401 Unauthorized';
                exit;
            }
        }

        if ($this->_getParam('controller') == 'interadmin-remote') {
            // !!Ver Jp7_Controller_InteradminController, migrar assim que possivel
            if ($interadmin_remote = reset($config->server->interadmin_remote)) {
                $this->_redirect('http://'.$interadmin_remote.'/'.$config->name_id);
            }
            echo 'No InterAdmin remote found.';
            exit;
        }
        if ($this->_getParam('controller') == 'intermail-remote') {
            require BASE_PATH.'/intermail/config.php';
            if ($interadmin_remote = reset($config->server->interadmin_remote)) {
                $this->_redirect('http://'.$interadmin_remote.'/'.$config->name_id);
            }
            echo 'No InterMail remote found.';
            exit;
        }

        /*
        global $debugger;
        $debugger->showFileName('# Module: <b>' . $this->_getParam('module') . '</b>');
        $debugger->showFileName('# Controller: <b>' . $this->_getParam('controller') . '</b>');
        $debugger->showFileName('# Action: <b>' . $this->_getParam('action') . '</b>');
        */
    }

    public function preDispatch()
    {
        if (!$this->actionExists()) {
            $this->forwardToTemplate();

            return;
        }

        $siteSettingsTipo = InterAdminTipo::findFirstTipoByModel('SiteSettings', [
            'where' => ["admin != ''"],
        ]);
        if ($siteSettingsTipo) {
            $siteSettings = $siteSettingsTipo->findFirst([
                'fields' => ['*'],
            ]);
            if ($siteSettings) {
                $config = Zend_Registry::get('config');
                foreach ($siteSettings->getAttributesAliases() as $alias) {
                    if ($alias === 'mostrar' || $alias === 'template_data') {
                        continue;
                    }
                    $config->$alias = $siteSettings->$alias;
                }
            }
            if ($siteSettings->template_data) {
                $config->template = (object) unserialize($siteSettings->template_data);
                if ($config->template->template) {
                    $this->view->headLink()->removeStylesheet('css/main.css');
                    // @filemtime(jp7_absolute_path(APPLICATION_PATH . '/../interadmin/dynamic.css'));
                    $this->view->headLink()->appendStylesheet('css/'.basename($config->template->template).'/main.css');
                    // Necessário para mudar a ordem
                    $this->view->headLink()->appendStylesheet('css/main.css');
                }
            }
        }
    }

    public function postDispatch()
    {
        /**
         * @var InterSite Configuração geral do site, gerada pelo InterSite
         */
        $config = Zend_Registry::get('config');
        /**
         * @var Jp7_Locale Idioma sendo utilizada no site
         */
        $lang = Zend_Registry::get('lang');
        /**
         * @var array Metatags no formato $nome => $valor
         */
        $metas = Zend_Registry::get('metas');

        $tipo = static::getTipo();

        $record = static::getRecord();

        // View
        $this->view->config = $config;
        $this->view->lang = $lang;
        $this->view->tipo = $tipo;
        $this->view->record = $record;

        // Boxes editáveis pelo InterAdmin
        if ($tipo instanceof InterAdminTipo) {
            $boxTipo = $tipo->getFirstChildByModel('Boxes');
            if ($boxTipo) {
                Jp7_Box_Manager::setView($this->view);
                Jp7_Box_Manager::setRecordMode($record);
                $this->view->boxes = Jp7_Box_Manager::buildBoxes($boxTipo);
            }
        }

        // Layout
        // Title
        $this->_prepareTitle();
        $this->view->headTitle($config->lang->title);
        // Metas
        $this->view->headMeta()->appendHttpEquiv('Content-Type', 'text/html; charset='.$config->charset);
        foreach ($metas as $key => $value) {
            $this->view->headMeta()->appendName($key, $value);
        }
        // Metas Customizadas
        $this->_prepareMetas();
    }
    /**
     * Função responsável por montar o título da página.
     * Permite que se altere o título sem sobrescrever o método postDispatch().
     *
     * @return string
     */
    protected function _prepareTitle()
    {
        $record = static::getRecord();

        $this->view->headTitle()->setSeparator(' | ');

        // Adiciona o nome do registro atual ao título
        if ($record) {
            if ($titulo = $record->varchar_key) {
                $this->view->headTitle($titulo);
            }
        }
        // Adiciona breadcrumb to tipo
        if ($secao = static::getTipo()) {
            if ($secao->getNome() == 'Home' && !$secao->getParent()->type_id) {
                return; // Home
            }
            while ($secao->type_id) {
                $this->view->headTitle($secao->getNome());
                $secao = $secao->getParent();
            }
        }
    }

    protected function _prepareMetas()
    {
        if ($settings = static::getSettings()) {
            $metas = Zend_Registry::get('metas');
            $tipo = static::getTipo();
            $record = static::getRecord();
            if (!$settings->title) {
                if ($tipo->nome != 'Home') {
                    $metas['keywords'] = $tipo->nome.','.$metas['keywords'];
                }
                if ($record instanceof InterAdmin) {
                    $metas['keywords'] = $record->varchar_key.','.$metas['keywords'];
                }
                $this->view->headMeta()->setName('keywords', $metas['keywords']);
            }

            if ($settings instanceof InterAdmin) {
                if ($title = $settings->title) {
                    $this->view->headTitle($title, Zend_View_Helper_Placeholder_Container_Abstract::SET);
                }
                if ($keywords = $settings->keywords) {
                    if ($settings->sobrescrever_keywords) {
                        $this->view->headMeta()->setName('keywords', $keywords);
                    } else {
                        $metas['keywords'] = $keywords.','.$metas['keywords'];
                        $this->view->headMeta()->setName('keywords', $metas['keywords']);
                    }
                }
                if ($description = $settings->description) {
                    $this->view->headMeta()->setName('description', $description);
                }
            }
        }
    }

    /**
     * Trata as actions que não tem a função definida e passa para o template
     * se existir.
     *
     * @param string $method
     * @param array  $args
     */
    public function __call($method, $args)
    {
        if ($this->forwardToTemplate()) {
            return;
        }

        return parent::__call($method, $args);
    }
    /**
     * Forwards the request to the template of this InterAdminTipo.
     *
     * @return bool TRUE if it has a template, FALSE otherwise.
     */
    public function forwardToTemplate()
    {
        if ($tipo = static::getTipo()) {
            if ($template = $tipo->template) {
                $templateArr = explode('/', $template);
                if (count($templateArr) > 2) {
                    list($module, $controller, $action) = $templateArr;
                } else {
                    list($controller, $action) = $templateArr;
                }
                if ($action == '$action') {
                    $action = $this->_getParam('action');
                } elseif ($this->_getParam('action') != 'index') {
                    $tipos = static::getTiposArray();
                    if (!$tipos['action']) {
                        return false; // won't forward unexistent actions
                    }
                }
                static $loop_count = 0;
                $loop_count++;
                if ($loop_count === 1) {
                    $this->_forward($action, $controller, $module);

                    return true;
                } elseif ($loop_count === 2) {
                    $this->_forward($action, $controller, 'jp7');

                    return true;
                }
            }
        }

        return false;
    }
    /**
     * Returns the InterAdminTipo pointed by the current Controller and Action.
     *
     * @return InterAdminTipo
     */
    public static function getTipo()
    {
        if (!isset(static::$tipo)) {
            $tipos = static::getTiposArray();
            static::$tipo = end($tipos);
        }

        return static::$tipo;
    }

    public static function getTiposArray()
    {
        $tipo = static::getRootTipo();
        $path = static::getTiposPath();

        $array = [
            'root' => $tipo,
        ];
        foreach ($path as $key => $directory) {
            $tipo = $tipo->getFirstChild([
                'fields' => ['template', 'nome'],
                'where' => ["type_id_string = '".toId($directory)."'"],
            ]);
            if (toSeo($tipo->nome) != $directory) {
                $tipo = null;
            }
            if (!$tipo) {
                break;
            }
            $array[$key] = $tipo;
        }

        return $array;
    }

    public static function getTiposPath()
    {
        $front = Zend_Controller_Front::getInstance();
        $request = $front->getRequest();

        $path = [];
        if ($request->getModuleName() != $front->getDefaultModule()) {
            $path['module'] = $request->getModuleName();
        }
        if ($request->getControllerName() != 'index') {
            $path['controller'] = $request->getControllerName();
        }
        if ($request->getActionName() != 'index') {
            $path['action'] = $request->getActionName();
        }
        if (!$path) {
            $path['controller'] = 'home';
        }

        return $path;
    }

    /**
     * Sets the InterAdminTipo for this controller.
     *
     * @param InterAdminTipo $tipo
     */
    public static function setTipo(InterAdminTipo $tipo = null)
    {
        static::$tipo = $tipo;
    }

    public static function getRecord()
    {
        return static::$record;
    }

    public static function setRecord(InterAdmin $record = null)
    {
        static::$record = $record;
    }

    /**
     * Checks if the request Action exists.
     *
     * @return bool
     */
    public function actionExists()
    {
        $request = $this->getRequest();
        $actionName = toId($request->getActionName());
        // Case insensitive
        return method_exists($this, $actionName.$request->getActionKey());
    }

    public static function getRootTipo()
    {
        $defaultClassName = InterAdminTipo::getDefaultClass();

        return new $defaultClassName();
    }

    /**
     * @return
     */
    public function getMenu()
    {
        $lang = Zend_Registry::get('lang');

        $options = [
            'fields' => ['nome'],
            'where' => ['menu <> ""'],
        ];

        if ($lang->prefix) {
            // Performance, não é necessário, mas diminui as queries
            $options['fields'][] = 'nome'.$lang->prefix;
        }

        //Retrieves all the menus
        $rootTipo = static::getRootTipo();
        $menu = $rootTipo->getChildren($options);

        foreach ($menu as $item) {
            $item->active = ($this->getTipo() == $item->type_id);
            $item->subitens = $item->getChildren($options);
            foreach ($item->subitens as $subitem) {
                if ($this->getTipo() == $subitem->type_id) {
                    $item->active = true;
                    $subitem->active = true;
                }
            }
        }

        return $menu;
    }

    public static function getSettings()
    {
        if ($tipo = static::getTipo()) {
            $settingsTipo = $tipo->getFirstChildByModel('Settings');
            if ($settingsTipo instanceof InterAdminTipo) {
                return $settingsTipo->findFirst([
                    'fields' => ['title', 'keywords', 'description', 'overwrite_keywords'],
                ]);
            }
        }
    }
}
