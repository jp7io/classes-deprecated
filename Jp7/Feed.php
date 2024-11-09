<?php

class Jp7_Feed extends Zend_Feed_Writer_Feed
{
    /**
     * Datetime do último registro inserido, que servirá como última modificação
     * no feed.
     *
     * @var timestamp
     */
    private $lastDateModified = 0;

    /**
     * Analisa objetos InterAdmin para criar uma entrada.
     *
     * @param array $interAdmins [optional] InterAdmin[]
     * @param array $helpers     [optional] Regras que ajudam ou forçam a leitura
     *                           de atributos do objeto InterAdmin.
     *
     * @example Defaults: array(
     * 	'content' => 'texto', // string
     * 	'title' => 'titulo', // string
     * 	'link' => 'link' | getUrl(), // string Link | getUrl
     * 	'category' => null, // array('term' => , slug)
     * 	'description' => 'resumo', // string
     * 	'id' => link value, // valid URI/IRI
     * 	'date_modified' => 'updated_at', // Timestamp value
     * 	'date_created' => 'publish_at' // Timestamp value
     * )
     *
     * @param string $category [optional]
     */
    public function parserInterAdmins($interAdmins = [], $category = '', $helpers = [])
    {
        if (!is_array($interAdmins)) {
            throw new Jp7_Feed_Exception("Undefined value for 'id'");
        }

        $helpers = array_merge([
            'content' => 'texto', // string
            'title' => 'titulo', // string
            'link' => 'link', // string This or getUrl()
            'category' => null, // array('term' => , slug)
            'description' => 'resumo', // string
            'id' => 'this.link', // valid URI/IRI
            'date_modified' => 'updated_at', // Timestamp value
            'date_created' => 'publish_at', // Timestamp value
        ], $helpers);

        if (mb_substr($helpers['id'], 0, 5) == 'this.') {
            $methodId = 'get' . ucfirst(mb_strtolower(ltrim($helpers['id'], 'this.')));
        }

        foreach ($interAdmins as $entryData) {
            if ($entryData instanceof InterAdmin) {
                $entry = $this->createEntry();
                $entry->setTitle($entryData->{$helpers['title']});
                $entry->setLink($entryData->{$helpers['link']} ? $entryData->{$helpers['link']} : $entryData->getUrl());
                if ($category) {
                    $entry->addCategory(['term' => $category]);
                }

                if ($entryData->{$helpers['content']}) {
                    $entry->setContent($entryData->{$helpers['content']});
                }

                $entry->setId($methodId ? $entry->$methodId() : $helpers['id']);

                if ($entryData->getByAlias($helpers['date_modified'])->isValid()) {
                    $dateModified = $entryData->getByAlias($helpers['date_modified'])->getTimestamp();
                    $entry->setDateModified($dateModified);
                } else {
                    $entry->setDateModified(null);
                }

                if ($entryData->getByAlias($helpers['date_created'])->isValid()) {
                    $entry->setDateCreated($entryData->getByAlias($helpers['date_created'])->getTimestamp());
                } else {
                    $entry->setDateCreated(null);
                }

                if ($entryData->{$helpers['description']}) {
                    $entry->setDescription($entryData->{$helpers['description']});
                }

                $this->addEntry($entry);

                if ($dateModified > $lastDateModified) {
                    $this->lastDateModified = $dateModified;
                }
            } else {
                throw new Jp7_Feed_Exception('Um objeto ou mais não é uma instância de InterAdmin.');
            }
        }
    }

    /**
     * Retorna o valor concatenado dos campos descritos.
     *
     * @param InterAdmin $object
     * @param array      $fields Campos do objeto a serem concatenados.
     * @param string     $glue   [optional] Separador
     *
     * @return string Valor dos campos concatenados
     */
    private function concatenateFields(InterAdmin $object, $fields, $glue = ' - ')
    {
        if (is_array($fields)) {
            $concatenated = [];

            foreach ($fields as $field) {
                if ($str = $object->$field) {
                    $concatenated[] = $str;
                }
            }

            return implode($glue, $concatenated);
        } else {
            return $object->$fields;
        }
    }

    public function getEntries()
    {
        return $this->_entries;
    }

    /**
     * @see Zend_Feed_Writer_Feed::createEntry()
     */
    public function createEntry()
    {
        $entry = new Jp7_Feed_Entry();
        if ($this->getEncoding()) {
            $entry->setEncoding($this->getEncoding());
        }
        $entry->setType($this->getType());

        return $entry;
    }

    public function getLastDateModified()
    {
        return $this->lastDateModified;
    }

    /**
     * @see Zend_Feed_Writer_Feed::export()
     */
    public function export($type, $ignoreExceptions = false)
    {
        if (!$this->getDateModified()) {
            if ($this->lastDateModified == 0) {
                $this->lastDateModified = null;
            }
            $this->setDateModified($this->lastDateModified);
        }

        return parent::export($type, $ignoreExceptions = false);
    }
}
