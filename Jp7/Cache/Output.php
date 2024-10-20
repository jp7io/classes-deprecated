<?php

 /**
  * Extende o Zend_Cache_Frontend_Output com Zend_Cache_Backend_File.
  *
  * @category Jp7
  * @deprecated
  */
 class Jp7_Cache_Output extends Zend_Cache_Frontend_Output
 {
     protected static $_instance = null;
     protected static $_started = false;
     protected static $_enabled = false;
     protected static $_cachedir = './cache/';
     protected static $_logdir = './interadmin/';
     protected static $_delay = 0;
     protected static $_className = __CLASS__;
     protected static $placeholderEnabled = false;
    /**
     * Retorna uma instância configurada do Jp7_Cache_Page.
     *
     * @todo Ver como irá funcionar com o Preview do InterAdmin
     *
     * @param array $frontOptions
     * @param array $backOptions
     *
     * @return Jp7_Cache_Output
     */
    public static function getInstance(array $frontOptions = [], array $backOptions = [])
    {
        if (!self::$_instance) {
            global $debugger, $s_session;
            $config = Zend_Registry::get('config');

            if ($config->cache && !$debugger->debugFilename && !$debugger->debugSql && emoty($s_session['preview'])) {
                self::$_enabled = true;
            }

            self::$_delay = intval($config->cache_delay);

            $frontDefault = [
                'lifetime' => 86400, // 1 dia
                'class_name' => self::$_className,
                'automatic_cleaning_factor' => 100,
            ];
            $backDefault = [
                'cache_dir' => self::$_cachedir,
            ];
            $frontOptions = $frontOptions + $frontDefault;
            // Necessário pela falta do PHP 5.3 e late static bind
            self::$_cachedir = $backDefault['cache_dir'];
            self::$_className = $frontOptions['class_name'];

            $frontend = new self::$_className($frontOptions + $frontDefault);

            if (is_dir(self::$_cachedir)) {
                $backend = new Jp7_Cache_Backend_File($backOptions + $backDefault);
            } else {
                $backend = new Zend_Cache_Backend_Test();
                self::$_enabled = false;
            }

            $frontend->setBackend($backend);

            self::$_instance = $frontend;
        }

        return self::$_instance;
    }

     /**
      * Inicia o cache.
      *
      * @param mixed $_ Valores que se alteram na página e que portanto geram outra versão de cache.
      *
      * @see Zend/Cache/Frontend/Zend_Cache_Frontend_Page#start()
      */
     public function start($id = null, $doNotTestCacheValidity = false, $echoData = true) /* FIXME dynamic args */
     {
         if (!self::$_enabled) {
             return false;
         }

        // Gera o id do cache
        $id = $this->_makeId(func_get_args());

        // Verifica se o log foi alterado
        $logTime = $this->_checkLog($id);

        // Desabilita o cache individual da página pelo $_GET
        if (isset($_GET['nocache_force'])) {
            $this->remove($id);
        }

        /* CODIGO ORIGINAL DA ZEND */
        $data = $this->load($id, false);
         if ($data !== false) {
             echo $this->replacePlaceholders($data);     // JP7
            $retorno = true;
         } else {
             ob_start();
             ob_implicit_flush(false);
             $this->_idStack[] = $id;
             $retorno = false;
         }
        /* Fim: CODIGO ORIGINAL DA ZEND */
        if ($retorno) {
            header('X-Cache: HIT');
            $this->_showDebug($id, $logTime);
            exit;
        }
        header('X-Cache: MISS');
         self::$placeholderEnabled = true;
         self::$_started = true;

         return $retorno;
     }

    /**
     * Stop the cache.
     *
     * @param array  $tags             Tags array
     * @param int    $specificLifetime If != false, set a specific lifetime for this cache record (null => infinite lifetime)
     * @param string $forcedDatas      If not null, force written datas with this
     * @param bool   $echoData         If set to true, datas are sent to the browser
     * @param int    $priority         integer between 0 (very low priority) and 10 (maximum priority) used by some particular backends
     */
    public function end($tags = [], $specificLifetime = false, $forcedDatas = null, $echoData = true, $priority = 8)
    {
        /* CODIGO ORIGINAL DA ZEND */
        if ($forcedDatas === null) {
            $data = ob_get_clean();
        } else {
            $data = &$forcedDatas;
        }
        $id = array_pop($this->_idStack);
        if ($id === null) {
            Zend_Cache::throwException('use of end() without a start()');
        }
        if ($data) {
            $this->save($data, $id, $tags, $specificLifetime, $priority);
        } // Jp7
        if ($echoData) {
            echo $this->replacePlaceholders($data); // Jp7
        }
        /* Fim: CODIGO ORIGINAL DA ZEND */
    }

    /**
     * Cancela o cache.
     */
    public function cancel()
    {
        self::$_enabled = false;
        ob_end_flush();
    }

    /**
     * Retorna true se o cache tiver iniciado.
     *
     * @return bool
     */
    public static function hasStarted()
    {
        return self::$_started;
    }

    /**
     * Gera marcador para página cacheada.
     *
     * @param string $name
     * @param mixed  $vars [optional]
     *
     * @return
     */
    public static function getPlaceholder($name, $vars = [])
    {
        return '{CACHE:'.$name.'|'.serialize($vars).'}'."\n";
    }

    /**
     * Substitui o marcador por conteúdo real.
     *
     * @param string $filecontent
     *
     * @return string
     */
    public function replacePlaceholders($filecontent)
    {
        self::$placeholderEnabled = false;

        return $filecontent;
    }

     public static function isPlaceholderEnabled()
     {
         return self::$placeholderEnabled;
     }

     protected function _replacePlaceholder($name, $include, $filecontent)
     {
         if (strpos($filecontent, '{CACHE:'.$name) !== false) {
             preg_match('/{CACHE:'.$name.'\|(.*)}/', $filecontent, $matches);
             $vars = unserialize($matches[1]);
             extract($vars);

            //include $include;
            $view = Zend_Layout::getMvcInstance()->getView();
             $include_content = $view->render($include);

             $filecontent = preg_replace('/{CACHE:'.$name.'\|(.*)}/', preg_replacement_quote($include_content), $filecontent);
         }

         return $filecontent;
     }

     /**
      * Cria um ID na forma: controller_action_lang_module_SUFIXO.
      *
      * @param mixed $data Gera um hash e adiciona como sufixo ao ID.
      *
      * @return string ID gerado.
      */
     protected function _makeId($data)
     {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $params = $request->getParams();
         $id = toId(implode('_', [
            $request->getScheme(),
            $request->getHttpHost(),
            $params['controller'],
            $params['action'],
            $params['lang'],
            $params['module'], ]
        ));

         if ($data) {
             if (count($data) == 1 && is_string($data[0]) && mb_strlen($data[0]) < 100) {
                 $id .= '_'.toId($data[0]);
             } else {
                 $id .= '_'.md5(serialize($data));
             }
         }

         return $id;
     }

    /**
     * Verifica se o log do InterAdmin foi alterado. E limpa o cache se necessário.
     *
     * @return int Retorna a data de alteração do arquivo de log.
     */
    protected function _checkLog($id)
    {
        $metas = $this->getMetadatas($id);
        if (!$metas) {
            return false;
        }

        $cache_time = $metas['mtime'];

        // Verificação do log
        $log_time = @filemtime(self::$_logdir.'interadmin.log');

        $antesDelay = time() - $log_time < self::$_delay;
        // Outro dia é atualizado o cache
        $expiradoOuOutroDia = ($log_time >= $cache_time) || date('d', $cache_time) != date('d');

        if ($expiradoOuOutroDia && !$antesDelay) {
            $this->remove($id);
        }

        return $cache_time;
    }

    /**
     * Exibe informações para debug em um overlay no topo da página.
     *
     * @param string $id      ID do cache.
     * @param int    $logTime Timestamp da última alteração do log.
     */
    protected function _showDebug($id, $logTime)
    {
        global $c_jp7;

        if ($c_jp7 && Zend_Layout::getMvcInstance()->isEnabled()) {
            global $config;
            $metas = $this->getBackend()->getMetadatas($id);

            $css = 'position:absolute;border:1px solid black;border-top:0px;font-weight:bold;top:0px;padding:5px;background:#FFCC00;filter:alpha(opacity=50);opacity: .5;z-index:1000;cursor:pointer;';
            $title = [
                '# Cache: ',
                    '  '.self::$_cachedir.$id,
                    '  '.date('d/m/Y H:i:s', $metas['mtime']),
                '# Log: ',
                    '  '.self::$_logdir.'interadmin.log',
                    '  '.date('d/m/Y H:i:s', $logTime),
                '# Hora do servidor: '.date('d/m/Y H:i:s', time()),
                '# Delay para limpeza: '.self::$_delay.' segundos',
                '# IP Servidor: '.$_SERVER['SERVER_ADDR'],
                '# DB: '.$config->db->host.'/'.$config->db->name,
            ];

            $title = implode('&#013;', $title);

            $pos = strpos($_SERVER['REQUEST_URI'], '?');
            if ($pos === false) {
                $urlNoCache = $_SERVER['REQUEST_URI'].'?nocache_force=true';
            } else {
                $urlNoCache = mb_substr($_SERVER['REQUEST_URI'], 0, $pos).'?nocache_force=true&'.mb_substr($_SERVER['REQUEST_URI'], $pos + 1);
            }
            $event = 'onclick="if (confirm(\'Deseja atualizar o cache desta página?\')) window.location = \''.$urlNoCache.'\'"';

            echo '<div style="'.$css.'left:0px;" title="'.$title.'" '.$event.'>CACHE</div>';
            echo '<div style="'.$css.'right:0px;" title="'.$title.'" '.$event.'>CACHE</div>';
        }
    }
 }
