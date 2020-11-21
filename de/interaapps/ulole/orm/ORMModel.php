<?php
namespace de\interaapps\ulole\orm;

trait ORMModel {
    protected $ormInternals_entryExists = false;
    protected $ormInternals_defaultSettings = [
        "identifier" => 'id',
        "exclude" => [
            "ormInternals_defaultSettings",
            "ormInternals_entryExists",
            "ormSettings"
        ]
    ];

    public static function table($database = 'main'){
        return new Query(UloleORM::getDatabase($database), static::class);
    }

    public function save($database = 'main'){
        if ($this->ormInternals_entryExists) {
            $query = self::table($database);
            foreach (get_object_vars($this) as $fieldName=>$value) {
                if (in_array($fieldName, $this->ormInternals_getSettings()["exclude"]))
                    continue;
                if ($value !== null)
                    $query->set($this->ormInternals_getFieldName($fieldName), $value);
            }
            return $query->where($this->ormInternals_getFieldName('-id'), $this->ormInternals_getField('-id'))->update();
        } else {
            return $this->insert($database);
        }
    }

    public function insert($database = 'main') : bool {
        $fields = [];
        $values = [];
        foreach (get_object_vars($this) as $fieldName=>$value) {
            if (in_array($fieldName, $this->ormInternals_getSettings()["exclude"]))
                    continue;
            if ($value !== null){
                array_push($fields, $fieldName);
                array_push($values, $value);
            }
        }
        
        $query = 'INSERT INTO `'.UloleORM::getTableName(static::class).'` (';
        
        foreach ($fields as $i => $field) 
            $query .= ($i == 0 ?'':', ' ) . '`'.$field.'`';

        $query .= ') VALUES (';

        foreach ($values as $i => $value)
            $query .= ($i == 0 ?'':', ' ) . '?';
        $query .= ')';
        
       $statement = UloleORM::getDatabase($database)->getConnection()->prepare($query);

       $result = $statement->execute($values);
       $this->{$this->ormInternals_getFieldName('-id')} = UloleORM::getDatabase($database)->getConnection()->lastInsertId();
       if ($result)
            $this->ormInternals_entryExists = true;
       return $result;
    }

    public function delete($database = 'main'){
        return self::table($database)
            ->where($this->ormInternals_getFieldName('-id'), $this->ormInternals_getField('-id'))
            ->delete();
    }

    protected function ormInternals_getSettings(){
        if (isset($this->ormSettings) && is_array($this->ormSettings))
            return array_merge($this->ormInternals_defaultSettings, $this->ormSettings);
        return $this->ormInternals_defaultSettings;
    }

    public function ormInternals_setEntryExists(){
        $this->ormInternals_entryExists = true;
    }

    protected function ormInternals_getFieldName($name){
        if ($name == '-id')
            return $this->ormInternals_getSettings()["identifier"];
        return $name;
    }

    protected function ormInternals_getField($name){
        return $this->{$this->ormInternals_getFieldName($name)};
    }
}