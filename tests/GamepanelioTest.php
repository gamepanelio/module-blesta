<?php

class GamepanelioTest extends PHPUnit_Framework_TestCase
{
    /**
     * A bit of a nasty hack to get all the minphp stuff working asap
     */
    protected function setUp()
    {
        /* Courtesy of minphp/bridge */
        $fixtureDir = __DIR__ . '/../vendor/minphp/bridge/tests/Unit/Lib/Fixtures/App/';

        $appDir = $fixtureDir . 'app/';

        Loader::setDirectories([
            $appDir,
            'models' => $appDir . 'models/',
            'controllers' => $appDir . 'controllers/',
            'components' => $fixtureDir . 'components/',
            'helpers' => $fixtureDir . 'helpers/',
            'plugins' => $fixtureDir . 'plugins/',
        ]);
        /* end minphp/bridge loader code */

        $initializer = \Minphp\Bridge\Initializer::get();
        $initializer->setContainer(new \Minphp\Container\Container([
            'minphp.language' => [],
            'minphp.mvc' => [
                'default_view' => '',
                'view_extension' => '.pdt',
            ],
            'minphp.constants' => [
                'APPDIR' => null,
                'WEBDIR' => null,
                'ROOTWEBDIR' => null,
            ]
        ]));
        $initializer->getContainer()->set('minphp.language', []);

        require_once __DIR__ . "/../gamepanelio.php";
    }

    /**
     * @return stdClass
     */
    protected function buildModuleRow()
    {
        return json_decode(json_encode([
            'id' => 1,
            'meta' => [
                'hostname' => 'abcd.domain.com',
                'access_token' => '12345',
            ]
        ]));
    }

    /**
     * @return stdClass
     */
    protected function buildPackageRow()
    {
        return json_decode(json_encode([
            'id' => 1,
            'meta' => [
                'plan_id' => '123',
                'ip_allocation' => 'auto',
                'game_type' => 'minecraft',
            ]
        ]));
    }

    /**
     * @return stdClass
     */
    protected function buildServiceRow()
    {
        return json_decode(json_encode([
            'fields' => [
                [
                    'key' => Gamepanelio::SERVICE_FIELD_SERVER_ID,
                    'value' => 1,
                ]
            ]
        ]));
    }

    /**
     * @return GamePanelio
     */
    protected function buildTestSubject($withModuleRow = false)
    {
        $gpio = new \Gamepanelio();
        $gpio->setDefaultViewPath(__DIR__ . '/../');

        if ($withModuleRow) {
            $gpio->setModuleRow($this->buildModuleRow());
        }

        return $gpio;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function buildMockApiClient()
    {
        return $this->createMock(\GamePanelio\GamePanelio::class);
    }

    /**
     * @covers \Gamepanelio::__construct
     */
    public function testConstructor()
    {
        $this->assertInstanceOf('\\Gamepanelio', new \Gamepanelio());
    }

    /**
     * Ensures manageModule returns some sort of HTML string
     */
    public function testManageModule()
    {
        $gpio = $this->buildTestSubject();

        $module = $this->buildModuleRow();
        $vars = [];

        $this->assertInternalType("string", $gpio->manageModule($module, $vars));
    }

    public function testManageAddRow()
    {
        $gpio = $this->buildTestSubject();

        // The Form class expects this to be set
        $_SERVER['REQUEST_URI'] = '';

        $vars = [];

        $this->assertInternalType("string", $gpio->manageAddRow($vars));
    }

    public function testManageEditRow()
    {
        $gpio = $this->buildTestSubject();

        // The Form class expects this to be set
        $_SERVER['REQUEST_URI'] = '';

        $module = $this->buildModuleRow();
        $vars = [];

        $this->assertInternalType("string", $gpio->manageEditRow($module, $vars));
    }

    /**
     * @param $meta
     */
    protected function assertValidMetaSaveObject($meta)
    {
        $this->assertInternalType('array', $meta);

        foreach ($meta as $item) {
            $this->assertInternalType('array', $item);

            $this->assertArrayHasKey('key', $item);
            $this->assertArrayHasKey('value', $item);
            $this->assertArrayHasKey('encrypted', $item);
        }
    }

    /**
     * @dataProvider internalModuleRowSaveData
     */
    public function testAddModuleRow($vars)
    {
        $gpio = $this->buildTestSubject();

        $this->assertValidMetaSaveObject($gpio->addModuleRow($vars));
    }

    /**
     * @dataProvider internalModuleRowSaveData
     */
    public function testEditModuleRow($vars)
    {
        $gpio = $this->buildTestSubject();

        $module = $this->buildModuleRow();

        $this->assertValidMetaSaveObject($gpio->editModuleRow($module, $vars));
    }

    public function internalModuleRowSaveData()
    {
        return [
            [
                [
                    'name' => 'test',
                    'hostname' => 'test.domain.com',
                    'access_token' => '12345',
                ]
            ]
        ];
    }

    public function testGetPackageFields()
    {
        $gpio = $this->buildTestSubject();

        $out = $gpio->getPackageFields();

        $this->assertInstanceOf('\\ModuleFields', $out);

        foreach ($out->getFields() as $field) {
            $this->assertInstanceOf('\\ModuleField', $field);
        }
    }

    /**
     * @dataProvider packageSaveData
     */
    public function testAddPackage($vars, $success)
    {
        $gpio = $this->buildTestSubject();

        $this->assertValidMetaSaveObject($gpio->addPackage($vars));

        if ($success) {
            $this->assertEmpty($gpio->Input->errors(), 'There should be no form errors');
        } else {
            $this->assertNotEmpty($gpio->Input->errors(), 'The form should have errors');
        }
    }

    /**
     * @dataProvider packageSaveData
     */
    public function testEditPackage($vars, $success)
    {
        $gpio = $this->buildTestSubject();

        $package = [];

        $this->assertValidMetaSaveObject($gpio->editPackage($package, $vars));

        if ($success) {
            $this->assertEmpty($gpio->Input->errors(), 'There should be no form errors');
        } else {
            $this->assertNotEmpty($gpio->Input->errors(), 'The form should have errors');
        }
    }

    public function packageSaveData()
    {
        return [
            [
                [
                    'meta' => [
                        'plan_id' => '123',
                        'ip_allocation' => 'auto',
                        'game_type' => 'minecraft',
                    ]
                ],
                true
            ],
            [
                [],
                false
            ]
        ];
    }

    public function testGetEmailTags()
    {
        $gpio = $this->buildTestSubject();

        $out = $gpio->getEmailTags();

        $this->assertInternalType('array', $out);
        $this->assertArrayHasKey('module', $out);
        $this->assertArrayHasKey('package', $out);
        $this->assertArrayHasKey('service', $out);
    }

    public function testAddService()
    {
        $gpio = $this->buildTestSubject(true);
        $apiClient = $this->buildMockApiClient();

        $apiClient
            ->method('getUserByUsername')
            ->willReturn([
                'id' => 12
            ]);

        $apiClient
            ->expects($this->once())
            ->method('createServer')
            ->withAnyParameters();

        $gpio->setMockApi($apiClient);

        $out = $gpio->addService(
            $this->buildPackageRow(),
            [
                'use_module' => 'true',
                'client_id' => 1
            ]
        );

        $this->assertNotEmpty($out);
        $this->assertValidMetaSaveObject($out);
        $this->assertEmpty($gpio->Input->errors());
    }

    /**
     * @return GamePanelio
     */
    private function updateServerSetup()
    {
        $gpio = $this->buildTestSubject(true);
        $apiClient = $this->buildMockApiClient();

        $apiClient
            ->expects($this->once())
            ->method('updateServer')
            ->withAnyParameters();

        $gpio->setMockApi($apiClient);

        return $gpio;
    }

    public function testSuspendService()
    {
        $gpio = $this->updateServerSetup();

        $out = $gpio->suspendService(
            $this->buildPackageRow(),
            $this->buildServiceRow()
        );

        $this->assertEmpty($gpio->Input->errors());
    }

    public function testUnsuspendService()
    {
        $gpio = $this->updateServerSetup();

        $out = $gpio->unsuspendService(
            $this->buildPackageRow(),
            $this->buildServiceRow()
        );

        $this->assertEmpty($gpio->Input->errors());
    }

    public function testCancelService()
    {
        $gpio = $this->buildTestSubject(true);
        $apiClient = $this->buildMockApiClient();

        $apiClient
            ->expects($this->once())
            ->method('deleteServer')
            ->withAnyParameters();

        $gpio->setMockApi($apiClient);

        $out = $gpio->cancelService(
            $this->buildPackageRow(),
            $this->buildServiceRow()
        );

        $this->assertEmpty($gpio->Input->errors());
    }

    public function testChangeServicePackage()
    {
        $gpio = $this->updateServerSetup();

        $out = $gpio->changeServicePackage(
            $this->buildPackageRow(),
            $this->buildPackageRow(),
            $this->buildServiceRow()
        );

        $this->assertEmpty($gpio->Input->errors());
    }

    public function testGetAdminServiceInfo()
    {
        $gpio = $this->buildTestSubject(true);

        $this->assertInternalType(
            'string',
            $gpio->getAdminServiceInfo(
                $this->buildServiceRow(),
                $this->buildPackageRow()
            )
        );
    }

    public function testGetClientServiceInfo()
    {
        $gpio = $this->buildTestSubject(true);

        $this->assertInternalType(
            'string',
            $gpio->getClientServiceInfo(
                $this->buildServiceRow(),
                $this->buildPackageRow()
            )
        );
    }
}
