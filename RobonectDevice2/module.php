<?php

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen
require_once __DIR__ . '/../libs/library.php';  // modul-bezogene Funktionen

// normalized MowerStatus
if (!defined('AUTOMOWER_ACTIVITY_UNKNOWN')) {
    define('AUTOMOWER_ACTIVITY_UNKNOWN', 1);
    define('AUTOMOWER_ACTIVITY_PARKED', 2);
    define('AUTOMOWER_ACTIVITY_CUTTING', 3);
    define('AUTOMOWER_ACTIVITY_CHARGING', 4);
    define('AUTOMOWER_ACTIVITY_WAITING', 5);
    define('AUTOMOWER_ACTIVITY_FAILED', 5);
    define('AUTOMOWER_ACTIVITY_NO_SIGNAL', 8);
    define('AUTOMOWER_ACTIVITY_DISABLED', 16);
    define('AUTOMOWER_ACTIVITY_SLEEPING', 17);
}

if (!defined('AUTOMOWER_STATUS_STARTED')) {
    define('AUTOMOWER_STATUS_STARTED', false);
    define('AUTOMOWER_STATUS_STOPPED', true);
}

if (!defined('AUTOMOWER_CONTROL_START')) {
    define('AUTOMOWER_CONTROL_START', 0);
    define('AUTOMOWER_CONTROL_STOP', 1);
}

if (!defined('AUTOMOWER_OPERATE_EOD')) {
    define('AUTOMOWER_OPERATE_EOD', 0);
    define('AUTOMOWER_OPERATE_HOME', 1);
    define('AUTOMOWER_OPERATE_AUTO', 2);
    define('AUTOMOWER_OPERATE_MANUAL', 3);
}

if (!defined('AUTOMOWER_MODE_AUTO')) {
    define('AUTOMOWER_MODE_AUTO', 0);
    define('AUTOMOWER_MODE_MANUAL', 1);
    define('AUTOMOWER_MODE_HOME', 2);
    define('AUTOMOWER_MODE_DEMO', 3);
}

if (!defined('AUTOMOWER_TIMER_DISABLED')) {
    define('AUTOMOWER_TIMER_DISABLED', 0);
    define('AUTOMOWER_TIMER_ACTIVE', 1);
    define('AUTOMOWER_TIMER_STANDBY', 2);
}


class RobonectDevice2 extends IPSModule
{
    use RobonectCommon;
    use RobonectLibrary;

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);
        
        $this->RegisterPropertyString('ip', '192.168.188.184');
        $this->RegisterPropertyString('user', 'sebastian');
        $this->RegisterPropertyString('password', '31827JohanN');
        $this->RegisterPropertyString('model', '');

        $this->RegisterPropertyBoolean('with_gps', true);
        $this->RegisterPropertyBoolean('save_position', false);

        $this->RegisterPropertyInteger('update_interval', '5');

        $this->RegisterTimer('UpdateStatus', 0, 'RobonectDevice_UpdateStatus(' . $this->InstanceID . ');');
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);

        $associations = [];
        $associations[] = ['Wert' => AUTOMOWER_STATUS_STARTED, 'Name' => $this->Translate('started'), 'Farbe' => -1];
        $associations[] = ['Wert' => AUTOMOWER_STATUS_STOPPED, 'Name' => $this->Translate('stopped'), 'Farbe' => 0xFF0000];
        $this->CreateVarProfile('Robonect.Status', VARIABLETYPE_BOOLEAN, '', 0, 0, 0, 0, '', $associations);

        $associations = [];
        $associations[] = ['Wert' => AUTOMOWER_OPERATE_EOD, 'Name' => $this->Translate('End of day'), 'Farbe' => -1];
        $associations[] = ['Wert' => AUTOMOWER_OPERATE_HOME, 'Name' => $this->Translate('Home'), 'Farbe' => -1];
        $associations[] = ['Wert' => AUTOMOWER_OPERATE_AUTO, 'Name' => $this->Translate('Auto'), 'Farbe' => -1];
        $associations[] = ['Wert' => AUTOMOWER_OPERATE_MANUAL, 'Name' => $this->Translate('Manual'), 'Farbe' => -1];
        $this->CreateVarProfile('Robonect.Mode', VARIABLETYPE_INTEGER, '', 0, 0, 0, 0, '', $associations);

        $associations = [];
        $associations[] = ['Wert' => AUTOMOWER_TIMER_DISABLED, 'Name' => $this->Translate('disabled'), 'Farbe' => -1];
        $associations[] = ['Wert' => AUTOMOWER_TIMER_ACTIVE, 'Name' => $this->Translate('active'), 'Farbe' => -1];
        $associations[] = ['Wert' => AUTOMOWER_TIMER_STANDBY, 'Name' => $this->Translate('standby'), 'Farbe' => -1];
        $this->CreateVarProfile('Robonect.Timer', VARIABLETYPE_INTEGER, '', 0, 0, 0, 0, '', $associations);

        $associations = [];
        $associations[] = ['Wert' => AUTOMOWER_ACTIVITY_UNKNOWN, 'Name' => $this->Translate('unknown'), 'Farbe' => -1];
        $associations[] = ['Wert' => AUTOMOWER_ACTIVITY_PARKED, 'Name' => $this->Translate('parked'), 'Farbe' => -1];
        $associations[] = ['Wert' => AUTOMOWER_ACTIVITY_CUTTING, 'Name' => $this->Translate('cutting'), 'Farbe' => -1];
        $associations[] = ['Wert' => AUTOMOWER_ACTIVITY_CHARGING, 'Name' => $this->Translate('charging'), 'Farbe' => -1];
        $associations[] = ['Wert' => AUTOMOWER_ACTIVITY_WAITING, 'Name' => $this->Translate('waiting'), 'Farbe' => -1];
        $associations[] = ['Wert' => AUTOMOWER_ACTIVITY_FAILED, 'Name' => $this->Translate('failed'), 'Farbe' => -1];
        $associations[] = ['Wert' => AUTOMOWER_ACTIVITY_NO_SIGNAL, 'Name' => $this->Translate('no signal'), 'Farbe' => -1];
        $associations[] = ['Wert' => AUTOMOWER_ACTIVITY_DISABLED, 'Name' => $this->Translate('disabled'), 'Farbe' => -1];
        $associations[] = ['Wert' => AUTOMOWER_ACTIVITY_SLEEPING, 'Name' => $this->Translate('sleeping'), 'Farbe' => -1];
        $this->CreateVarProfile('Robonect.Activity', VARIABLETYPE_INTEGER, '', 0, 0, 0, 0, '', $associations);

        $associations = [];
        $associations[] = ['Wert' =>  0, 'Name' => '-', 'Farbe' => -1];
        $associations[] = ['Wert' =>  1, 'Name' => $this->Translate('outside mowing area'), 'Farbe' => 0xFFA500];
        $associations[] = ['Wert' =>  2, 'Name' => $this->Translate('no loop signal'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' =>  4, 'Name' => $this->Translate('Problem loop sensor front'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' =>  5, 'Name' => $this->Translate('Problem loop sensor rear'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' =>  6, 'Name' => $this->Translate('Problem loop sensor'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' =>  7, 'Name' => $this->Translate('Problem loop sensor'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' =>  8, 'Name' => $this->Translate('wrong PIN-code'), 'Farbe' => 0x9932CC];
        $associations[] = ['Wert' =>  9, 'Name' => $this->Translate('locked in'), 'Farbe' => 0x1874CD];
        $associations[] = ['Wert' => 10, 'Name' => $this->Translate('upside down'), 'Farbe' => 0x1874CD];
        $associations[] = ['Wert' => 11, 'Name' => $this->Translate('low battery'), 'Farbe' => 0x1874CD];
        $associations[] = ['Wert' => 12, 'Name' => $this->Translate('battery empty'), 'Farbe' => 0xFFA500];
        $associations[] = ['Wert' => 13, 'Name' => $this->Translate('no drive'), 'Farbe' => 0x1874CD];
        $associations[] = ['Wert' => 15, 'Name' => $this->Translate('Mower raised'), 'Farbe' => 0x1874CD];
        $associations[] = ['Wert' => 16, 'Name' => $this->Translate('trapped in charging station'), 'Farbe' => 0xFFA500];
        $associations[] = ['Wert' => 17, 'Name' => $this->Translate('charging station blocked'), 'Farbe' => 0xFFA500];
        $associations[] = ['Wert' => 18, 'Name' => $this->Translate('Problem shock sensor rear'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' => 19, 'Name' => $this->Translate('Problem shock sensor front'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' => 20, 'Name' => $this->Translate('Wheel motor blocked on the right'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' => 21, 'Name' => $this->Translate('Wheel motor blocked on the left'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' => 22, 'Name' => $this->Translate('Drive problem left'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' => 23, 'Name' => $this->Translate('Drive problem right'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' => 24, 'Name' => $this->Translate('Problem mower engine'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' => 25, 'Name' => $this->Translate('Cutting system blocked'), 'Farbe' => 0xFFA500];
        $associations[] = ['Wert' => 26, 'Name' => $this->Translate('Faulty component connection'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' => 27, 'Name' => $this->Translate('default settings'), 'Farbe' => -1];
        $associations[] = ['Wert' => 28, 'Name' => $this->Translate('Memory defective'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' => 30, 'Name' => $this->Translate('battery problem'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' => 31, 'Name' => $this->Translate('STOP-button problem'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' => 32, 'Name' => $this->Translate('tilt sensor problem'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' => 33, 'Name' => $this->Translate('Mower tilted'), 'Farbe' => 0x1874CD];
        $associations[] = ['Wert' => 35, 'Name' => $this->Translate('Wheel motor overloaded right'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' => 36, 'Name' => $this->Translate('Wheel motor overloaded left'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' => 37, 'Name' => $this->Translate('Charging current too high'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' => 38, 'Name' => $this->Translate('Temporary problem'), 'Farbe' => -1];
        $associations[] = ['Wert' => 42, 'Name' => $this->Translate('limited cutting height range'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' => 43, 'Name' => $this->Translate('unexpected cutting height adjustment'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' => 44, 'Name' => $this->Translate('unexpected cutting height adjustment'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' => 45, 'Name' => $this->Translate('Problem drive cutting height'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' => 46, 'Name' => $this->Translate('limited cutting height range'), 'Farbe' => 0xFF0000];
        $associations[] = ['Wert' => 47, 'Name' => $this->Translate('Problem drive cutting height'), 'Farbe' => 0xFF0000];
        $this->CreateVarProfile('Robonect.Error', VARIABLETYPE_INTEGER, '', 0, 0, 0, 0, '', $associations);
        
        $associations = [];
        $associations[] = ['Wert' => false, 'Name' => $this->Translate('Disconnected'), 'Farbe' => 0xEE0000];
        $associations[] = ['Wert' => true, 'Name' => $this->Translate('Connected'), 'Farbe' => -1];
        $this->CreateVarProfile('Robonect.Connection', VARIABLETYPE_BOOLEAN, '', 0, 0, 0, 1, 'Alarm', $associations);
        
        $this->CreateVarProfile('Robonect.Battery', VARIABLETYPE_INTEGER, ' %', 1, 0, 100, 1, 'Battery');
        $this->CreateVarProfile('Robonect.Blade', VARIABLETYPE_INTEGER, ' %', 1, 0, 100, 1, 'EnergyProduction');
        $this->CreateVarProfile('Robonect.Location', VARIABLETYPE_FLOAT, ' °', 0, 0, 0, 5, '');
        $this->CreateVarProfile('Robonect.Duration', VARIABLETYPE_INTEGER, ' min', 0, 0, 0, 0, 'Hourglass');
        $this->CreateVarProfile('Robonect.Temperature', VARIABLETYPE_INTEGER, ' °C', -20, 50, 0, 0, 'Temperature');
        $this->CreateVarProfile('Robonect.Hours', VARIABLETYPE_INTEGER, ' h', 0, 0, 0, 0, 'Temperature');
    }

    public function Destroy() {
        //Never delete this line!
        parent::Destroy();

        $instances = IPS_GetInstanceListByModuleID('{51D61D4E-17D3-49C0-A07F-3E0BE775FDA5}');
        if (!$instances) {
            $this->SendDebug(__FUNCTION__, 'trigger delete variable profiles', 0);
            IPS_DeleteVariableProfile("Robonect.Status");
            IPS_DeleteVariableProfile("Robonect.Mode");
            IPS_DeleteVariableProfile("Robonect.Action");
            IPS_DeleteVariableProfile("Robonect.Error");
            IPS_DeleteVariableProfile("Robonect.Connection");
            IPS_DeleteVariableProfile("Robonect.Battery");
            IPS_DeleteVariableProfile("Robonect.Location");
            IPS_DeleteVariableProfile("Robonect.Duration");
            IPS_DeleteVariableProfile("Robonect.Temperature");
            IPS_DeleteVariableProfile("Robonect.Hours");
            IPS_DeleteVariableProfile("Robonect.Timer");
            IPS_DeleteVariableProfile("Robonect.Blade");
        }
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $ip = $this->ReadPropertyString('ip');
        $user = $this->ReadPropertyString('user');
        $password = $this->ReadPropertyString('password');
        $model = $this->ReadPropertyString('model');
        $with_gps = $this->ReadPropertyBoolean('with_gps');
        $save_position = $this->ReadPropertyBoolean('save_position');

        $vpos = 0;
        $this->MaintainVariable('Connected', $this->Translate('Connected'), VARIABLETYPE_BOOLEAN, 'Robonect.Connection', $vpos++, true);
        $this->MaintainVariable('Id', $this->Translate('Id'), VARIABLETYPE_INTEGER, '', $vpos++, true);
        $this->MaintainVariable('Name', $this->Translate('Name'), VARIABLETYPE_STRING, '', $vpos++, true);
        $this->MaintainVariable('Battery', $this->Translate('Battery capacity'), VARIABLETYPE_INTEGER, 'Robonect.Battery', $vpos++, true);
        $this->MaintainVariable('Temperature', $this->Translate('Temperature'), VARIABLETYPE_INTEGER, 'Robonect.Temperature', $vpos++, true);
        $this->MaintainVariable('BladeQuality', $this->Translate('Blade quality'), VARIABLETYPE_INTEGER, 'Robonect.Blade', $vpos++, true);
        $this->MaintainVariable('OperationHours', $this->Translate('Operating hours'), VARIABLETYPE_INTEGER, 'Robonect.Hours', $vpos++, true);
        $this->MaintainVariable('OperationMode', $this->Translate('Operation mode'), VARIABLETYPE_INTEGER, 'Robonect.Mode', $vpos++, true);
        $this->MaintainVariable('TimerMode', $this->Translate('Timer mode'), VARIABLETYPE_INTEGER, 'Robonect.Timer', $vpos++, true);
        $this->MaintainVariable('MowerStatus', $this->Translate('Mower status'), VARIABLETYPE_BOOLEAN, 'Robonect.Status', $vpos++, true);
        $this->MaintainVariable('MowerActivity', $this->Translate('Mower activity'), VARIABLETYPE_INTEGER, 'Robonect.Activity', $vpos++, true);
        $this->MaintainVariable('NextStart', $this->Translate('Next start'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);
        $this->MaintainVariable('DailyReference', $this->Translate('Day of cumulation'), VARIABLETYPE_INTEGER, '~UnixTimestampDate', $vpos++, true);
        $this->MaintainVariable('DailyWorking', $this->Translate('Working time (day)'), VARIABLETYPE_INTEGER, 'Robonect.Duration', $vpos++, true);
        $this->MaintainVariable('LastErrorCode', $this->Translate('Last error'), VARIABLETYPE_INTEGER, 'Robonect.Error', $vpos++, true);
        $this->MaintainVariable('LastErrorTimestamp', $this->Translate('Timestamp of last error'), VARIABLETYPE_INTEGER, '~UnixTimestampDate', $vpos++, true);
        $this->MaintainVariable('LastLongitude', $this->Translate('Last position (longitude)'), VARIABLETYPE_FLOAT, 'Robonect.Location', $vpos++, $with_gps);
        $this->MaintainVariable('LastLatitude', $this->Translate('Last position (latitude)'), VARIABLETYPE_FLOAT, 'Robonect.Location', $vpos++, $with_gps);
        $this->MaintainVariable('LastStatus', $this->Translate('Last status'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);
        $this->MaintainVariable('Position', $this->Translate('Position'), VARIABLETYPE_STRING, '', $vpos++, $save_position);

        $this->MaintainAction('OperationMode', true);
        $this->MaintainAction('MowerStatus', true);

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            $this->SetTimerInterval('UpdateStatus', 0);
            $this->SetStatus(IS_INACTIVE);
            return;
        }


        if ($user != '' && $password != '' && $ip != '') {
            $this->SetUpdateInterval();
            // Inspired by module SymconTest/HookServe
            // We need to call the RegisterHook function on Kernel READY
            if (IPS_GetKernelRunlevel() == KR_READY) {
                $this->UpdateStatus();
            }
            $this->SetStatus(IS_ACTIVE);
        } else {
            $this->SetStatus(IS_INACTIVE);
        }

        $this->SetSummary($ip);
    }

    public function GetConfigurationForm()
    {
        $formElements = [];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'module_disable', 'caption' => 'Instance is disabled'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'ip', 'caption' => 'IP-Address'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'user', 'caption' => 'User'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'password', 'caption' => 'Password'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'model', 'caption' => 'Model'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'with_gps', 'caption' => 'with GPS-Data'];
        $formElements[] = ['type' => 'Label', 'label' => 'save position to (logged) variable \'Position\''];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'save_position', 'caption' => 'save position'];
        $formElements[] = ['type' => 'Label', 'label' => ''];
        $formElements[] = ['type' => 'Label', 'label' => 'Update status every X minutes'];
        $formElements[] = ['type' => 'NumberSpinner', 'name' => 'update_interval', 'caption' => 'Minutes'];

        $formActions = [];
        $formActions[] = ['type' => 'Button', 'label' => 'Test account', 'onClick' => 'RobonectDevice_TestAccount($id);'];
        $formActions[] = ['type' => 'Button', 'label' => 'Update status', 'onClick' => 'RobonectDevice_UpdateStatus($id);'];
        $formActions[] = ['type' => 'Label', 'label' => '____________________________________________________________________________________________________'];
        $formActions[] = ['type' => 'Button', 'label' => 'Module description', 'onClick' => 'echo \'https://github.com/sebastianfs-private/IPSymconRobonect/blob/master/README.md\';'];

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

    // Inspired by module SymconTest/HookServe
    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

        if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
            $this->UpdateStatus();
        }
    }

    protected function SetUpdateInterval()
    {
        $min = $this->ReadPropertyInteger('update_interval');
        $msec = $min > 0 ? $min * 1000 * 60 : 0;
        $this->SetTimerInterval('UpdateStatus', $msec);
    }

    public function UpdateStatus()
    {
        $ip = $this->ReadPropertyString('ip');
        $model = $this->ReadPropertyString('model');
        $with_gps = $this->ReadPropertyBoolean('with_gps');
        $save_position = $this->ReadPropertyBoolean('save_position');

        $data = $this->GetMowerData("name");
        if ($data['successful'] == false){
            $this->SetValue('Connected', false);
            return false;
        }

        $this->SetValue('Connected', true);

        $id = $data['id'];
        $this->SetValue('Id', $id);

        $name = $data['name'];
        $this->SetValue('Name', $name);

        $data = $this->GetMowerData("status");
        if ($data['successful'] == false) {
            $this->SetValue('Connected', false);
            return false;
        }
        
        $this->SendDebug(__FUNCTION__, 'Status=' . print_r($data['successful'], true), 0);

        $batteryPercent = $data['status']['battery'];
        $this->SetValue('Battery', $batteryPercent);

        $temperature = $data['health']['temperature'];
        $this->SetValue('Temperature', $temperature);

        $quality = $data['blades']['quality'];
        $this->SetValue('BladeQuality', $quality);

        $omode = $data['status']['mode'];
        $this->SetValue('OperationMode', $omode);

        $hours = $data['status']['hours'];
        $this->SetValue('OperationHours', $hours);

        $tmode = $data['timer']['status'];
        $this->SetValue('TimerMode', $tmode);

        $mowerStatus = $data['status']['stopped'];
        $this->SendDebug(__FUNCTION__, 'mowerStatus="' . $mowerStatus, 0);
        $this->SetValue('MowerStatus', $mowerStatus);

        $mowerActivity = $data['status']['status'];
        $this->SendDebug(__FUNCTION__, 'mowerActivity="' . $mowerActivity, 0);
        $this->SetValue('MowerActivity', $mowerActivity);

        // $oldActivity = $this->GetValue('MowerActivity');
        // switch ($oldActivity) {
        //     case AUTOMOWER_ACTIVITY_MOVING:
        //     case AUTOMOWER_ACTIVITY_CUTTING:
        //         $wasWorking = true;
        //         break;
        //     default:
        //         $wasWorking = false;
        //         break;
        // }
        // $this->SendDebug(__FUNCTION__, 'wasWorking=' . $wasWorking, 0);

        // $mowerActivity = $this->normalize_mowerStatus($status['mowerStatus']);
        // $this->SendDebug(__FUNCTION__, 'MowerActivity=' . $mowerActivity, 0);
        // $this->SetValue('MowerActivity', $mowerActivity);

        // switch ($mowerActivity) {
        //     case AUTOMOWER_ACTIVITY_DISABLED:
        //     case AUTOMOWER_ACTIVITY_PAUSED:
        //     case AUTOMOWER_ACTIVITY_PARKED:
        //     case AUTOMOWER_ACTIVITY_CHARGING:
        //         $action = AUTOMOWER_ACTION_START;
        //         break;
        //     case AUTOMOWER_ACTIVITY_MOVING:
        //     case AUTOMOWER_ACTIVITY_CUTTING:
        //         $action = AUTOMOWER_ACTION_PARK;
        //         break;
        //     default:
        //         $action = AUTOMOWER_ACTION_STOP;
        //         break;
        // }
        // $this->SendDebug(__FUNCTION__, 'MowerAction=' . $action, 0);
        // $this->SetValue('MowerAction', $action);

        // $nextStartSource = $status['nextStartSource'];

        // $nextStartTimestamp = $status['nextStartTimestamp'];
        // if ($nextStartTimestamp > 0) {
        //     // 'nextStartTimestamp' ist nicht UTC sondern auf localtime umgerechnet.
        //     $ts = strtotime(gmdate('Y-m-d H:i', $nextStartTimestamp));
        // } else {
        //     $ts = 0;
        // }
        // $this->SetValue('NextStart', $ts);

        // $operatingMode = $this->decode_operatingMode($status['operatingMode']);
        // $this->SendDebug(__FUNCTION__, 'operatingMode="' . $status['operatingMode'] . '" => OperationMode=' . $operatingMode, 0);
        // $this->SetValue('OperationMode', $operatingMode);

        // if ($with_gps) {
        //     if (isset($status['lastLocations'][0]['longitude'])) {
        //         $lon = $status['lastLocations'][0]['longitude'];
        //         $this->SetValue('LastLongitude', $lon);
        //     }
        //     if (isset($status['lastLocations'][0]['latitude'])) {
        //         $lat = $status['lastLocations'][0]['latitude'];
        //         $this->SetValue('LastLatitude', $lat);
        //     }
        // }

        // $this->SetValue('LastStatus', time());

        // $lastErrorCode = $status['lastErrorCode'];
        // $lastErrorCodeTimestamp = $status['lastErrorCodeTimestamp'];
        // if ($lastErrorCode) {
        //     $msg = __FUNCTION__ . ': error-code=' . $lastErrorCode . ' @' . date('d-m-Y H:i:s', $lastErrorCodeTimestamp);
        //     $this->LogMessage($msg, KL_WARNING);
        // } else {
        //     $lastErrorCodeTimestamp = 0;
        // }
        // $this->SetValue('LastErrorCode', $lastErrorCode);
        // $this->SetValue('LastErrorTimestamp', $lastErrorCodeTimestamp);

        // $dt = new DateTime(date('d.m.Y 00:00:00'));
        // $ts_today = $dt->format('U');
        // $ts_watch = $this->GetValue('DailyReference');
        // if ($ts_today != $ts_watch) {
        //     $this->SetValue('DailyReference', $ts_today);
        //     $this->SetValue('DailyWorking', 0);
        // }
        // switch ($mowerActivity) {
        //     case AUTOMOWER_ACTIVITY_MOVING:
        //     case AUTOMOWER_ACTIVITY_CUTTING:
        //         $isWorking = true;
        //         break;
        //     default:
        //         $isWorking = false;
        //         break;
        // }
        // $tstamp = $this->GetBuffer('Working');
        // $this->SendDebug(__FUNCTION__, 'isWorking=' . $isWorking . ', tstamp[GET]=' . $tstamp, 0);

        // if ($tstamp != '') {
        //     $daily_working = $this->GetBuffer('DailyWorking');
        //     $duration = $daily_working + ((time() - $tstamp) / 60);
        //     $this->SetValue('DailyWorking', $duration);
        //     $this->SendDebug(__FUNCTION__, 'daily_working[GET]=' . $daily_working . ', duration=' . $duration, 0);
        //     if (!$isWorking) {
        //         $this->SetBuffer('Working', '');
        //         $this->SetBuffer('DailyWorking', 0);
        //         $this->SendDebug(__FUNCTION__, 'tstamp[CLR], daily_working[CLR]', 0);
        //     }
        // } else {
        //     if ($isWorking) {
        //         $tstamp = time();
        //         $this->SetBuffer('Working', $tstamp);
        //         $daily_working = $this->GetValue('DailyWorking');
        //         $this->SetBuffer('DailyWorking', $daily_working);
        //         $this->SendDebug(__FUNCTION__, 'tstamp[SET]=' . $tstamp . ', daily_working[SET]=' . $daily_working, 0);
        //     }
        // }

        // if (isset($status['lastLocations'])) {
        //     $lastLocations = $status['lastLocations'];
        //     $this->SetBuffer('LastLocations', json_encode($lastLocations));
        //     if ($save_position && ($wasWorking || $isWorking)) {
        //         if (count($lastLocations)) {
        //             $latitude = (float) $this->format_float($lastLocations[0]['latitude'], 6);
        //             $longitude = (float) $this->format_float($lastLocations[0]['longitude'], 6);
        //             $pos = json_encode(['latitude'  => $latitude, 'longitude' => $longitude]);
        //             if ($this->GetValue('Position') != $pos) {
        //                 $this->SetValue('Position', $pos);
        //                 $this->SendDebug(__FUNCTION__, 'changed Position=' . $pos, 0);
        //             }
        //         }
        //     }
        // }

        // bisher unausgewertet url's:
        //  - $this->url_track . 'mowers/' . $ip . '/settings'
        //  - $this->url_track . 'mowers/' . $ip . '/geofence'
    }

    public function TestAccount()
    {
        $inst = IPS_GetInstance($this->InstanceID);
        if ($inst['InstanceStatus'] == IS_INACTIVE) {
            $this->SendDebug(__FUNCTION__, 'instance is inactive, skip', 0);
            echo $this->translate('Instance is inactive') . PHP_EOL;
            return;
        }

        $ip = $this->ReadPropertyString('ip');
        $user = $this->ReadPropertyString('user');
        $password = $this->ReadPropertyString('password');

        $status = $this->GetMowerData("status");
        if (!$status) {
            $this->SetStatus(IS_UNAUTHORIZED);
            echo $this->Translate('invalid account-data');
            return;
        }

        // if (!$mower_found) {
        //     $this->SetStatus(IS_DEVICE_MISSING);
        //     echo $this->Translate('device not found');
        //     return;
        // }

        echo $this->translate('valid account-data') . "\n" . $status['name'];
    }

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'MowerAction':
                $this->SendDebug(__FUNCTION__, "$Ident=$Value", 0);
                switch ($Value) {
                    case AUTOMOWER_CONTROL_START:
                        $this->StartMower();
                        break;
                    case AUTOMOWER_CONTROL_STOP:
                        $this->StopMower();
                        break;
                    default:
                        $this->SendDebug(__FUNCTION__, "invalid value \"$Value\" for $Ident", 0);
                        break;
                }
                break;
            case 'OperationMode':
                $this->SendDebug(__FUNCTION__, "$Ident=$Value", 0);
                switch ($Value) {
                    case AUTOMOWER_OPERATE_EOD:
                        $this->ModeMower("eod");
                        break;
                    case AUTOMOWER_OPERATE_HOME:
                        $this->ModeMower("home");
                        break;
                    case AUTOMOWER_OPERATE_AUTO:
                        $this->ModeMower("auto");
                        break;
                     case AUTOMOWER_OPERATE_MANUAL:
                        $this->ModeMower("manual");
                        break;
                    default:
                        $this->SendDebug(__FUNCTION__, "invalid value \"$Value\" for $Ident", 0);
                        break;
                }
                break;
            default:
                $this->SendDebug(__FUNCTION__, "invalid ident $Ident", 0);
                break;
        }
    }

    private function decode_operatingMode($val)
    {
        $val2txt = [
                'HOME'               => 'remain in base',
                'AUTO'               => 'automatic',
                'MAIN_AREA'          => 'main area',
                'SECONDARY_AREA'     => 'secondary area',
                'OVERRIDE_TIMER'     => 'override timer',
                'SPOT_CUTTING'       => 'spot cutting',
            ];

        if (isset($val2txt[$val])) {
            $txt = $this->Translate($val2txt[$val]);
        } else {
            $msg = 'unknown value "' . $val . '"';
            $this->LogMessage(__FUNCTION__ . ': ' . $msg, KL_WARNING);
            $this->SendDebug(__FUNCTION__, $msg, 0);
            $txt = $val;
        }
        return $txt;
    }

    private function decode_mowerStatus($val)
    {
        $val2txt = [
            0  => "Status wird ermittelt",
            1  => "Parkt",
            2  => "Mäht",
            3  => "Sucht",
            4  => "Lädt",
            5  => "Warten",
            7  => "Fehlerstatus",
            8  => "kein Schleifensignal",
            16  => "Abgeschaltet",
            17  => "Schläft"
            // 'ERROR'                       => 'error',

            // 'OK_CUTTING'                  => 'cutting',
            // 'OK_CUTTING_NOT_AUTO'         => 'manual cutting',
            // 'OK_CUTTING_TIMER_OVERRIDDEN' => 'manual cutting',

            // 'PARKED_TIMER'                => 'parked',
            // 'PARKED_PARKED_SELECTED'      => 'manual parked',

            // 'PAUSED'                      => 'paused',

            // 'OFF_DISABLED'                => 'disabled',
            // 'OFF_HATCH_OPEN'              => 'hatch open',
            // 'OFF_HATCH_CLOSED'            => 'hatch closed',
            // 'OFF_HATCH_CLOSED_DISABLED'   => 'hatch closed and disabled',

            // 'OK_SEARCHING'                => 'searching base',
            // 'OK_LEAVING'                  => 'leaving base',

            // 'OK_CHARGING'                 => 'charging',
        ];

        if (isset($val2txt[$val])) {
            $txt = $this->Translate($val2txt[$val]);
        } else {
            $msg = 'unknown value "' . $val . '"';
            $this->LogMessage(__FUNCTION__ . ': ' . $msg, KL_WARNING);
            $this->SendDebug(__FUNCTION__, $msg, 0);
            $txt = $val;
        }
        return $txt;
    }

    private function normalize_mowerStatus($val)
    {
        $val2code = [
                'ERROR'                       => AUTOMOWER_ACTIVITY_ERROR,

                'OK_CUTTING'                  => AUTOMOWER_ACTIVITY_CUTTING,
                'OK_CUTTING_NOT_AUTO'         => AUTOMOWER_ACTIVITY_CUTTING,
                'OK_CUTTING_TIMER_OVERRIDDEN' => AUTOMOWER_ACTIVITY_CUTTING,

                'PARKED_TIMER'                => AUTOMOWER_ACTIVITY_PARKED,
                'PARKED_PARKED_SELECTED'      => AUTOMOWER_ACTIVITY_PARKED,

                'PAUSED'                      => AUTOMOWER_ACTIVITY_PAUSED,

                'OFF_DISABLED'                => AUTOMOWER_ACTIVITY_DISABLED,
                'OFF_HATCH_OPEN'              => AUTOMOWER_ACTIVITY_DISABLED,
                'OFF_HATCH_CLOSED'            => AUTOMOWER_ACTIVITY_DISABLED,
                'OFF_HATCH_CLOSED_DISABLED'   => AUTOMOWER_ACTIVITY_DISABLED,

                'OK_SEARCHING'                => AUTOMOWER_ACTIVITY_MOVING,
                'OK_LEAVING'                  => AUTOMOWER_ACTIVITY_MOVING,

                'OK_CHARGING'                 => AUTOMOWER_ACTIVITY_CHARGING,
            ];

        if (isset($val2code[$val])) {
            $code = $val2code[$val];
        } else {
            $msg = 'unknown value "' . $val . '"';
            $this->LogMessage(__FUNCTION__ . ': ' . $msg, KL_WARNING);
            $this->SendDebug(__FUNCTION__, $msg, 0);
            $code = AUTOMOWER_ACTIVITY_ERROR;
        }
        return $code;
    }

    public function ModeMower(string $mode)
    {
        return $this->MowerCmd($mode);
    }

    public function StartMower()
    {
        return $this->MowerCmd('auto');
    }

    public function StopMower()
    {
        return $this->MowerCmd('stop');
    }

    private function MowerCmd($cmd)
    {

        $this->SendDebug(__FUNCTION__, $cmd, 0);

        $data = $this->SetMowerMode($cmd);
        if ($data['successful'] == false){
            $this->SetValue('Connected', false);
            return false;
        }
    }

    protected function SetBuffer($name, $data)
    {
        $this->SendDebug(__FUNCTION__, 'name=' . $name . ', size=' . strlen($data) . ', data=' . $data, 0);
        parent::SetBuffer($name, $data);
    }

    public function GetRawData(string $name)
    {
        $data = $this->GetBuffer($name);
        $this->SendDebug(__FUNCTION__, 'name=' . $name . ', size=' . strlen($data) . ', data=' . $data, 0);
        return $data;
    }
}
