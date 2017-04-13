<?php

class Gamepanelio extends Module
{
    const SERVICE_FIELD_SERVER_ID = 'gamepanelio_server_id';
    const SERVICE_FIELD_USER_ID = 'gamepanelio_user_id';
    const SERVICE_FIELD_USERNAME = 'gamepanelio_username';
    const SERVICE_FIELD_PASSWORD = 'gamepanelio_password';

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

    /**
     * @param $username
     * @param $password
     * @param $email
     * @param $fullName
     * @return array
     */
    private function findCreateApiUser($username, $password, $email, $fullName)
    {
        try {
            $this->log("getUserByUsername", $username, "input", true);
            $response = $this->apiClient->getUserByUsername($username);
            $this->log("getUserByUsername", serialize($response), "output", true);
        } catch (\GamePanelio\Exception\ApiCommunicationException $e) {
            $this->log("getUserByUsername", $e->getMessage(), "output", false);

            $params = [
                'username' => $username,
                'password' => $password,
                'email' => $email,
                'fullName' => $fullName,
            ];

            $masked_params = $params;
            $masked_params['password'] = "***";

            $this->log("createUser", serialize($masked_params), "input", true);
            $response = $this->apiClient->createUser($params);
            $this->log("createUser", serialize($response), "output", true);
        }

        return $response;
    }

    /**
     * @param $serviceDetails
     * @param $clientDetails
     * @return string
     */
    private function buildUsername($serviceDetails, $clientDetails)
    {
        if (
            array_key_exists(self::SERVICE_FIELD_USERNAME, $serviceDetails) &&
            $username = $serviceDetails[self::SERVICE_FIELD_USERNAME]
        ) {
            return $username;
        }

        $usernamePrefix = isset($serviceDetails->meta->username_prefix) ? $serviceDetails->meta->username_prefix : "";

        return $usernamePrefix . $clientDetails->first_name . $clientDetails->last_name . $clientDetails->id;
    }

    /**
     * @param $serviceDetails
     * @param $clientDetails
     * @return string
     */
    private function buildPassword($serviceDetails, $clientDetails)
    {
        if (
            array_key_exists(self::SERVICE_FIELD_PASSWORD, $serviceDetails) &&
            $password = $serviceDetails[self::SERVICE_FIELD_PASSWORD]
        ) {
            return $password;
        }

        return $this->generatePassword();
    }

    /**
     * Generates a password
     *
     * @param int $min_length The minimum character length for the password (5 or larger)
     * @param int $max_length The maximum character length for the password (14 or fewer)
     * @return string The generated password
     */
    private function generatePassword($min_length = 10, $max_length = 14)
    {
        $pool = 'abcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';
        $pool_size = strlen($pool);
        $length = mt_rand(max($min_length, 5), min($max_length, 14));
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= substr($pool, mt_rand(0, $pool_size - 1), 1);
        }

        return $password;
    }

    /**
     * @return array
     */
    public function getEmailTags()
    {
        return [
            'module' => ['hostname'],
            'package' => ['game_type'],
            'service' => [self::SERVICE_FIELD_SERVER_ID, self::SERVICE_FIELD_USERNAME, self::SERVICE_FIELD_PASSWORD]
        ];
    }

    /**
     * @param $package
     * @param array|null $vars
     * @param null $parent_package
     * @param null $parent_service
     * @param string $status
     * @return array|null
     */
    public function addService(
        $package,
        array $vars = null,
        $parent_package = null,
        $parent_service = null,
        $status = "pending"
    ) {
        Loader::loadModels($this, ['Clients']);

        $row = $this->getModuleRow();
        $client = $this->Clients->get($vars['client_id'], false);

        if (!$row) {
            $this->Input->setErrors([
                'module_row' => ['missing' => Language::_('Gamepanelio.!error.module_row.missing', true)]
            ]);
            return;
        }

        $gpioUserId = array_key_exists(self::SERVICE_FIELD_USER_ID, $vars)
            ? $vars[self::SERVICE_FIELD_USER_ID] : null;
        $gpioServerId = array_key_exists(self::SERVICE_FIELD_SERVER_ID, $vars)
            ? $vars[self::SERVICE_FIELD_SERVER_ID] : null;
        $serviceUsername = $this->buildUsername($vars, $client);
        $servicePassword = $this->buildPassword($vars, $client);

        $this->validateService($package, $vars);
        if ($this->Input->errors()) {
            return;
        }

        // Only provision the service remotely if 'use_module' is true
        if (isset($vars['use_module']) && $vars['use_module'] == "true") {
            try {
                $this->buildApiClient($row->meta->hostname, $row->meta->access_token);

                if (!$gpioUserId) {
                    $response = $this->findCreateApiUser(
                        $serviceUsername,
                        $servicePassword,
                        $client->email,
                        $client->first_name . ' ' . $client->last_name
                    );

                    $gpioUserId = $response['id'];
                }

                $serverName = $client->first_name . "'";
                if (substr($client->first_name, -1) != "s") {
                    $serverName .= "s";
                }
                $serverName .= " Game Server";

                $params = [
                    'name' => $serverName,
                    'user' => $gpioUserId,
                    'game' => $package->meta->game_type,
                    'plan' => $package->meta->plan_id,
                    'allocation' => $package->meta->ip_allocation,
                ];

                $this->log("createServer", serialize($params), "input", true);
                $response = $this->apiClient->createServer($params);
                $this->log("createServer", serialize($response), "output", true);

                $gpioServerId = $response['id'];
            } catch (\GamePanelio\Exception\ApiCommunicationException $e) {
                $this->Input->setErrors([
                    'api_response' => ['error' => $e->getMessage()]
                ]);
            }

            // Return on error
            if ($this->Input->errors()) {
                return;
            }
        }

        // Return the service fields
        return [
            [
                'key' => self::SERVICE_FIELD_SERVER_ID,
                'value' => $gpioServerId,
                'encrypted' => 0
            ],
            [
                'key' => self::SERVICE_FIELD_USER_ID,
                'value' => $gpioUserId,
                'encrypted' => 0
            ],
            [
                'key' => self::SERVICE_FIELD_USERNAME,
                'value' => $serviceUsername,
                'encrypted' => 0
            ],
            [
                'key' => self::SERVICE_FIELD_PASSWORD,
                'value' => $servicePassword,
                'encrypted' => 1
            ],
        ];
    }
}
