<?
// Klassendefinition
class FahrenheitUmrechner extends IPSModule {

    // Überschreibt die interne IPS_Create($id) Funktion
    public function Create() {
        // Diese Zeile nicht löschen.
        parent::Create();

        $this->RegisterPropertyInteger("SourceID", 0);

        $this->RegisterVariableFloat("Value", "Value", "~Temperature");

    }

    public function ApplyChanges() {

        // Diese Zeile nicht löschen
        parent::ApplyChanges();

        $this->RegisterMessage($this->ReadPropertyInteger("SourceID"), VM_UPDATE);

    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data) {

        $this->SendDebug("MessageSink", "Message from SenderID ".$SenderID." with Message ".$Message."\r\n Data: ".print_r($Data, true), 0);

        $this->UpdateValue();

    }

    /**
     * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
     * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:
     *
     * ABC_MeineErsteEigeneFunktion($id);
     *
     */
    public function MeineErsteEigeneFunktion() {
        echo "Hallo Welt!";
    }

    public function UpdateValue() {

        $id = $this->ReadPropertyInteger("SourceID");
        if($id > 0) {

            $value = GetValue($id);

            //°C = (°F − 32) / 1,8
            $value = ($value - 32) / 1.8;

            SetValue($this->GetIDForIdent("Value"), $value);

        }


    }
}