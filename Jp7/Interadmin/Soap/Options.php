<?php

class Jp7_Interadmin_Soap_Options
{
    public function getArray()
    {
        $options = [];
        if ($this) {
            // Where
            if ($this->where) {
                $where = '('.$this->where.')';
            }
            // Fields
            $fields = [];
            if ($this->fields) {
                $fields = jp7_explode(',', $this->fields);

                if (in_array('*', $fields)) {
                    $fields = array_merge($fields, [
                        'parent_id',
                        'date_insert',
                        'date_modify',
                        'date_publish',
                        'deleted_at',
                        'publish',
                    ]);
                }
            }
            // Montagem
            $options = [
                'fields' => $fields,
                'where' => jp7_explode(',', $where),
                'limit' => $this->limit,
            ];
            foreach ($options['fields'] as $key => $field) {
                if (strpos($field, '.')) {
                    list($join, $joinField) = explode('.', $field);
                    $options['fields'][$join][] = $joinField;
                    $options['fields'][$key] = $join;
                }
            }
        }

        return $options;
    }
}
