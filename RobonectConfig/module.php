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
            
            $this->SendDebug(__FUNCTION__, 'Properties completely set', 0);
            
            $status = $this->GetMowerStatus();
            $options[] = ['label' => $status['name'], 'value' => $status['name']];

            $this->SendDebug(__FUNCTION__, $status['name'], 0);
        }

        $formActions = [];
        $formActions[] = ['type' => 'Select', 'name' => 'mower_name', 'caption' => 'Mower-Name', 'options' => $options];
        $formActions[] = [
                            'type'    => 'Button',
                            'caption' => 'Import of mower',
                            'confirm' => 'Triggering the function creates the instances for the selected Automower-device. Are you sure?',
                            'onClick' => 'RobonectConfig_FindOrCreateInstance($id, "Flip");'
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

    public function FindOrCreateInstance($name)
    {   
        $info = '330x';
        $properties = [
            'model'       => '330x',
            'with_gps'    => true
        ];
        $pos = 1000;
        
        $ip = $this->ReadPropertyString('ip');
        $user = $this->ReadPropertyString('user');
        $password = $this->ReadPropertyString('password');

        $instID = '';

        $instIDs = IPS_GetInstanceListByModuleID('{7A095C8E-EA88-4E6F-A010-A97BC234DCE3}');
        foreach ($instIDs as $id) {
            $cfg = IPS_GetConfiguration($id);
            $jcfg = json_decode($cfg, true);
            if (!isset($jcfg['ip'])) {
                continue;
            }
            if ($jcfg['ip'] == $device_id) {
                $instID = $id;
                break;
            }
        }

        $this->SendDebug(__FUNCTION__, $instID, 0);

        if ($instID == '') {
            $this->SendDebug(__FUNCTION__, "No instance found", 0);

            $instID = IPS_CreateInstance('{7A095C8E-EA88-4E6F-A010-A97BC234DCE3}');
            if ($instID == '') {
                $this->SendDebug(__FUNCTION__, "unable to create instance", 0);
                //echo 'unable to create instance "' . $name . '"';
                return $instID;
            }
            IPS_SetProperty($instID, 'ip', $ip);
            IPS_SetProperty($instID, 'user', $user);
            IPS_SetProperty($instID, 'password', $password);
            foreach ($properties as $key => $property) {
                IPS_SetProperty($instID, $key, $property);
            }
            IPS_SetName($instID, $name);
            IPS_SetInfo($instID, $info);
            IPS_SetPosition($instID, $pos);
        }

        IPS_ApplyChanges($instID);

        return $instID;
    }
}
