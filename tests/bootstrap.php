<?php

require_once __DIR__ . "/../vendor/autoload.php";

define("DS", "/");

/**!**
 * MOCK CLASSES: for testing purposes only (no library filler available)
 **!**/

class Widget {
    public function clear()
    {
        return true;
    }

    public function setLinkButtons($buttons)
    {
        return true;
    }

    public function create($title, $attributes = null)
    {
        return '';
    }

    public function end()
    {

    }
}

class Clients {
    public function get($id)
    {
        return json_decode(json_encode([
            'id' => $id,
            'username' => 'john',
            'email' => 'john@johnson.dev',
            'first_name' => 'John',
            'last_name' => 'Johnson',
        ]));
    }
}

class Module {
    private $moduleRow;

    protected $base_uri = __DIR__ . '/..';

    public function loadConfig()
    {
        return true;
    }

    protected function log($url, $data = null, $direction = 'input', $success = false)
    {
        return true;
    }

    public function setModuleRow($moduleRow)
    {
        $this->moduleRow = $moduleRow;
    }

    public function getModuleRow()
    {
        return $this->moduleRow;
    }

    public function validateService($package, $vars = null)
    {
        return true;
    }

    public function serviceFieldsToObject()
    {
        return new stdClass();
    }
}

class ModuleField {
    public function attach(ModuleField $field)
    {
        return true;
    }
}

class ModuleFields {
    private $fields;

    public function label($name, $for = null, array $attributes = null, $preserve_tags = false)
    {
        return new ModuleField();
    }

    public function fieldText($name, $value = null, $attributes = [], ModuleField $label = null)
    {
        return new ModuleField();
    }

    public function fieldSelect(
        $name,
        $options = [],
        $selected_value = null,
        $attributes = [],
        $option_attributes = [],
        ModuleField $label = null
    )
    {
        return new ModuleField();
    }

    public function tooltip($message)
    {
        return new ModuleField();
    }

    public function setField(ModuleField $field)
    {
        $this->fields[] = $field;
    }

    public function getFields()
    {
        return $this->fields;
    }
}

/**!**
 * END MOCK CLASSES
 **!**/
