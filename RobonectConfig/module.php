<?php

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen
require_once __DIR__ . '/../libs/library.php';  // modul-bezogene Funktionen

class RobonectConfig extends IPSModule
{
    use RobonectCommon;
    use RobonectLibrary;

    public function Create()
    {
        parent::Create();
		
		$this->RegisterPropertyString('ip', '192.168.188.184');
        $this->RegisterPropertyString('user', 'sebastian');
        $this->RegisterPropertyString('password', '');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
        
        $ip = $this->ReadPropertyString('ip');
        $user = $this->ReadPropertyString('user');
        $password = $this->ReadPropertyString('password');

        $ok = true;
        if ($ip != '' || $user == '' || $password == '') {
            $ok = false;
        }
        //$this->SetStatus($ok ? IS_ACTIVE : IS_UNAUTHORIZED);
    }

    public function GetConfigurationForm()
    {
        $formElements = [];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'ip', 'caption' => 'IP-Address'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'user', 'caption' => 'User'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'password', 'caption' => 'Password'];

        $options = [];

        $ip = $this->ReadPropertyString('ip');
        $user = $this->ReadPropertyString('user');
        $password = $this->ReadPropertyString('password');

        if ($ip != '' && $user != '' && $password != '') {
            
            $this->SendDebug(__FUNCTION__, 'instance is inactive, skip', 0);
            
            $name = $this->GetMowerStatus();

            // $getDataUrl = array(
			// 	"status"  => "/json?cmd=status",
			// 	"version" => "/json?cmd=version",
			// 	"error"   => "/json?cmd=error"
			// );
				
			// $content = $this->url_get_contents($getDataUrl['status'], $debug);

			// $status = json_decode($content, true);

			// if($status['successful'] == true){
			// 	$name = $status['name'];
			// 	$options[] = ['label' => $name, 'value' => $name];
			// }
        }

        $formActions = [];
        $formActions[] = ['type' => 'ValidationTextBox', 'name' => 'mower_name', 'caption' => 'Mower-Name', 'value' => $name];
        $formActions[] = [
                            'type'    => 'Button',
                            'caption' => 'Import of mower',
                            'confirm' => 'Triggering the function creates the instances for the selected Automower-device. Are you sure?',
                            'onClick' => 'RobonectConfig_GetMowerStatus($id, false);'
                        ];
        $formActions[] = ['type' => 'Label', 'label' => '____________________________________________________________________________________________________'];
        $formActions[] = [
                            'type'    => 'Button',
                            'caption' => 'Module description',
                            'onClick' => 'echo "https://github.com/sebastianfs-private/IPSymconRobonect/blob/master/README.md";'
                        ];

        $formStatus = [];
        $formStatus[] = ['code' => IS_CREATING, 'icon' => 'inactive', 'caption' => 'Instance getting created'];
        $formStatus[] = ['code' => IS_ACTIVE, 'icon' => 'active', 'caption' => 'Instance is active'];
        $formStatus[] = ['code' => IS_DELETING, 'icon' => 'inactive', 'caption' => 'Instance is deleted'];
        $formStatus[] = ['code' => IS_INACTIVE, 'icon' => 'inactive', 'caption' => 'Instance is inactive'];
        $formStatus[] = ['code' => IS_NOTCREATED, 'icon' => 'inactive', 'caption' => 'Instance is not created'];

        $formStatus[] = ['code' => IS_UNAUTHORIZED, 'icon' => 'error', 'caption' => 'Instance is inactive (unauthorized)'];
        $formStatus[] = ['code' => IS_SERVERERROR, 'icon' => 'error', 'caption' => 'Instance is inactive (server error)'];
        $formStatus[] = ['code' => IS_HTTPERROR, 'icon' => 'error', 'caption' => 'Instance is inactive (http error)'];
        $formStatus[] = ['code' => IS_INVALIDDATA, 'icon' => 'error', 'caption' => 'Instance is inactive (invalid data)'];
        $formStatus[] = ['code' => IS_DEVICE_MISSING, 'icon' => 'error', 'caption' => 'Instance is inactive (device missing)'];

        return json_encode(['elements' => $formElements, 'actions' => $formActions, 'status' => $formStatus]);
    }
}
