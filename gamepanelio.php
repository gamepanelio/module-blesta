<?php

class Gamepanelio extends Module
{
    /**
     * @var \GamePanelio\GamePanelio
     */
    private $apiClient;

    /**
     * Gamepanelio constructor.
     */
    public function __construct()
    {
        Loader::loadComponents($this, array("Input"));
        Language::loadLang("gamepanelio", null, dirname(__FILE__) . DS . "language" . DS);
        $this->loadConfig(dirname(__FILE__) . DS . "config.json");
    }

    /**
     * @return string
     */
    public function moduleRowMetaKey()
    {
        return "name";
    }

    /**
     * @param $module
     * @param array $vars
     * @return mixed
     */
    public function manageModule($module, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View("manage", "default");
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView("components" . DS . "modules" . DS . 'gamepanelio' . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, array("Form", "Html", "Widget"));
        $this->view->set("module", $module);

        return $this->view->fetch();
    }

    /**
     * @param array $vars
     * @return mixed
     */
    public function manageAddRow(array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View("add_row", "default");
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView("components" . DS . "modules" . DS . "gamepanelio" . DS);

        // Load the helpers required for this view
        Loader::loadHelpers($this, array("Form", "Html", "Widget"));
        $this->view->set("vars", (object)$vars);

        return $this->view->fetch();
    }

    /**
     * @param $module_row
     * @param array $vars
     * @return mixed
     */
    public function manageEditRow($module_row, array &$vars)
    {
        // Load the view into this object, so helpers can be automatically added to the view
        $this->view = new View("edit_row", "default");
        $this->view->base_uri = $this->base_uri;
        $this->view->setDefaultView("components" . DS . "modules" . DS . "gamepanelio" . DS);

        // Set initial module row meta fields for vars
        if (empty($vars)) {
            $vars = $module_row->meta;
        }

        // Load the helpers required for this view
        Loader::loadHelpers($this, array("Form", "Html", "Widget"));
        $this->view->set("vars", (object)$vars);

        return $this->view->fetch();
    }

    /**
     * @param array $vars
     * @return array
     */
    public function addModuleRow(array &$vars)
    {
        $fields = ['name', 'hostname', 'access_token'];
        $encryptedFields = ['access_token'];
        $meta = [];

        foreach ($vars as $key => $value) {
            if (!in_array($key, $fields)) {
                continue;
            }

            $meta[] = [
                'key' => $key,
                'value' => $value,
                'encrypted' => in_array($key, $encryptedFields) ? 1 : 0
            ];
        }

        return $meta;
    }

    /**
     * @param $module_row
     * @param array $vars
     * @return array
     */
    public function editModuleRow($module_row, array &$vars)
    {
        return $this->addModuleRow($vars);
    }

    /**
     * @param $vars
     * @return ModuleFields
     */
    public function getPackageFields($vars = null)
    {
        Loader::loadHelpers($this, ['Html']);

        $fields = new ModuleFields();

        $planId = $fields->label(
            Language::_('Gamepanelio.package_fields.plan_id', true),
            "plan_id"
        );
        $planId->attach(
            $fields->fieldText(
                "meta[plan_id]",
                $this->Html->ifSet($vars->meta['plan_id']),
                [
                    'id' => "plan_id",
                    'type' => "number",
                ]
            )
        );
        $planId->attach(
            $fields->tooltip(
                Language::_('Gamepanelio.!tooltip.package_fields.plan_id', true)
            )
        );
        $fields->setField($planId);

        $usernamePrefix = $fields->label(
            Language::_('Gamepanelio.package_fields.username_prefix', true),
            "username_prefix"
        );
        $usernamePrefix->attach(
            $fields->fieldText(
                "meta[username_prefix]",
                $this->Html->ifSet($vars->meta['username_prefix']),
                [
                    'id' => "username_prefix",
                ]
            )
        );
        $usernamePrefix->attach(
            $fields->tooltip(
                Language::_('Gamepanelio.!tooltip.package_fields.username_prefix', true)
            )
        );
        $fields->setField($usernamePrefix);

        $ipAllocation = $fields->label(
            Language::_('Gamepanelio.package_fields.ip_allocation', true),
            "ip_allocation"
        );
        $ipAllocation->attach(
            $fields->fieldSelect(
                "meta[ip_allocation]",
                [
                    "auto" => Language::_('Gamepanelio.package_fields.ip_allocation.auto', true),
                    "dedicated" => Language::_('Gamepanelio.package_fields.ip_allocation.dedicated', true),
                ],
                $this->Html->ifSet($vars->meta['ip_allocation'], 'auto'),
                [
                    'id' => "ip_allocation",
                ]
            )
        );
        $ipAllocation->attach(
            $fields->tooltip(
                Language::_('Gamepanelio.!tooltip.package_fields.ip_allocation', true)
            )
        );
        $fields->setField($ipAllocation);

        $gameType = $fields->label(
            Language::_('Gamepanelio.package_fields.game_type', true),
            "game_type"
        );
        $gameType->attach(
            $fields->fieldSelect(
                "meta[game_type]",
                json_decode(file_get_contents(__DIR__ . DS . 'games.json'), true),
                $this->Html->ifSet($vars->meta['game_type'], 'auto'),
                [
                    'id' => "game_type",
                ]
            )
        );
        $gameType->attach(
            $fields->tooltip(
                Language::_('Gamepanelio.!tooltip.package_fields.game_type', true)
            )
        );
        $fields->setField($gameType);

        return $fields;
    }

    /**
     * @param array|null $vars
     * @return array
     */
    public function addPackage(array $vars = null)
    {
        $fields = ['plan_id', 'username_prefix', 'ip_allocation', 'game_type'];

        // Set any package meta field rules
        $rules = [
            'meta[plan_id]' => [
                'empty' => [
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("Gamepanelio.!error.package_fields.plan_id.empty", true)
                ]
            ],
            'meta[ip_allocation]' => [
                'empty' => [
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("Gamepanelio.!error.package_fields.ip_allocation.empty", true)
                ]
            ],
            'meta[game_type]' => [
                'empty' => [
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("Gamepanelio.!error.package_fields.game_type.empty", true)
                ]
            ],
        ];

        $this->Input->setRules($rules);

        // Determine whether the input validates
        $meta = [];

        if ($this->Input->validates($vars)) {
            foreach ($vars['meta'] as $key => $value) {
                if (in_array($key, $fields)) {
                    $meta[] = [
                        'key' => $key,
                        'value' => $value,
                        'encrypted' => 0
                    ];
                }
            }
        }

        return $meta;
    }

    /**
     * @param $package
     * @param array|null $vars
     * @return array
     */
    public function editPackage($package, array $vars = null)
    {
        return $this->addPackage($vars);
    }

    /**
     * @param $hostname
     * @param $accessTokenString
     * @return \GamePanelio\GamePanelio
     */
    private function buildApiClient($hostname, $accessTokenString)
    {
        $accessToken = new \GamePanelio\AccessToken\PersonalAccessToken($accessTokenString);

        return $this->apiClient = new \GamePanelio\GamePanelio($hostname, $accessToken);
    }
}
