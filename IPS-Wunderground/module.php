<?php

// Klassendefinition
    class IPSWundergroundWeather extends IPSModule {
	public function Create() {
            // Diese Zeile nicht löschen.
            parent::Create();
            $this->RegisterPropertyString("Location", "pws:IBAYERNE17");
            $this->RegisterPropertyString("APIKey", "");
            $this->RegisterPropertyBoolean("FetchSummary", true);
            $this->RegisterPropertyBoolean("FetchSummary0", true);
            $this->RegisterPropertyBoolean("FetchSummary1", false);
            $this->RegisterPropertyBoolean("FetchSummary2", false);

            $this->RegisterPropertyInteger("UpdateWeatherInterval", 10);

            //Variablenprofil anlegen ($name, $ProfileType, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Icon)
            $this->CreateVarProfile("IPSWGW.Rainfall", 2, " Liter/m²" ,0 , 10, 0 , 2, "Rainfall");
            $this->CreateVarProfile("IPSWGW.Sunray", 2, " W/m²", 0, 2000, 0, 2, "Sun");
            $this->CreateVarProfile("IPSWGW.Visibility", 2, " km", 0, 0, 0, 2, "");
            $this->CreateVarProfileIPSWGWWindSpeedkmh();
            $this->CreateVarProfileIPSWGWVIndex();
            //Timer erstellen
            $this->RegisterTimer("UpdateWeather", $this->ReadPropertyInteger("UpdateWeatherInterval"), 'IPSWGW_UpdateWeatherData($_IPS[\'TARGET\']);');
	}
	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
	public function ApplyChanges() {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();
            if (($this->ReadPropertyString("APIKey") != "") && ($this->ReadPropertyString("Location") != "")){
                //Timerzeit setzen in Minuten
                if ($this->ReadPropertyBoolean("FetchSummary")) {
                        $this->SetTimerInterval("UpdateWeather", $this->ReadPropertyInteger("UpdateWeatherInterval")*1000*60);
                } else {
                        $this->SetTimerInterval("UpdateWeather", 0);
                }
                //Jetzt Variablen erstellen/löschen
                
                $keep = $this->ReadPropertyBoolean("FetchSummary0");
                $this->MaintainVariable("TempHigh0", "Höchsttemperatur (heute)", 3, "Temperature", 10, $keep);
                $this->MaintainVariable("TempLow0", "Tiefsttemperatur (heute)", 3, "Temperature", 20, $keep);
                $this->MaintainVariable("Condition0", "Kondition (heute)", 2, "Temperature", 30, $keep);
                $this->MaintainVariable("Icon0", "Icon (heute)", 1, "", 40, $keep);
                $this->MaintainVariable("Pop0", "Regenwahrscheinlichkeit (heute)", 1, "", 50, $keep);
                $this->MaintainVariable("Avehumidity0", "Luftfeuchtigkeit (heute)", 1, "", 60, $keep);
                $this->MaintainVariable("FCT_Tag0", "Vorhersage Tag (heute)", 3, "", 70, $keep);
                $this->MaintainVariable("FCT_Nacht0", "Vorhersage Nacht (heute)", 3, "", 80, $keep);

                $keep = $this->ReadPropertyBoolean("FetchSummary1");
                $this->MaintainVariable("TempHigh1", "Höchsttemperatur (morgen)", 2, "Temperature", 110, $keep);
                $this->MaintainVariable("TempLow1", "Tiefsttemperatur (morgen)", 2, "Temperature", 120, $keep);
                $this->MaintainVariable("Condition1", "Kondition (morgen)", 2, "Temperature", 130, $keep);
                $this->MaintainVariable("Icon1", "Icon (morgen)", 2, "", 140, $keep);
                $this->MaintainVariable("Pop1", "Regenwahrscheinlichkeit (morgen)", 1, "", 150, $keep);
                $this->MaintainVariable("Avehumidity1", "Luftfeuchtigkeit (morgen)", 1, "", 160, $keep);
                $this->MaintainVariable("FCT_Tag1", "Vorhersage Tag (morgen)", 3, "", 70, $keep);
                $this->MaintainVariable("FCT_Nacht1", "Vorhersage Nacht (morgen)", 3, "", 80, $keep);                
                
                $keep = $this->ReadPropertyBoolean("FetchSummary2");
                $this->MaintainVariable("TempHigh2", "Höchsttemperatur (übermorgen)", 2, "Temperature", 210, $keep);
                $this->MaintainVariable("TempLow2", "Tiefsttemperatur (übermorgen)", 2, "Temperature", 220, $keep);
                $this->MaintainVariable("Condition2", "Kondition (übermorgen)", 2, "Temperature", 230, $keep);
                $this->MaintainVariable("Icon2", "Icon (übermorgen)", 2, "", 240, $keep);
                $this->MaintainVariable("Pop2", "Regenwahrscheinlichkeit (übermorgen)", 1, "", 250, $keep);
                $this->MaintainVariable("Avehumidity2", "Luftfeuchtigkeit (übermorgen)", 1, "", 260, $keep);
                $this->MaintainVariable("FCT_Tag2", "Vorhersage Tag (übermorgen)", 3, "", 70, $keep);
                $this->MaintainVariable("FCT_Nacht2", "Vorhersage Nacht (übermorgen)", 3, "", 80, $keep);
              
                //Instanz ist aktiv
                $this->SetStatus(102);
            } else {
                    //Instanz ist inaktiv
                    $this->SetStatus(104);
            }
	}
	public function UpdateWeatherData() {
            if ($this->ReadPropertyBoolean("FetchSummary")) {
                //Wetterdaten Forcast
                $WeatherNow = $this->RequestAPI("/forecast/lang:DL/q/");
                $this->SendDebug("IPSWGW Forecast", print_r($WeatherNow, true), 0);
                for ($i = 0; $i <= 2; $i++) {
                    if ($this->ReadPropertyBoolean("FetchSummary".$i)) {
                        SetValue($this->GetIDForIdent("FCT_Tag".$i), $WeatherNow->forecast->txt_forecast->forecastday[$i*2]->fcttext_metric);
                        SetValue($this->GetIDForIdent("FCT_Nacht".$i), $WeatherNow->forecast->txt_forecast->forecastday[$i*2+1]->fcttext_metric);
                        SetValue($this->GetIDForIdent("Avehumidity".$i), $WeatherNow->forecast->simpleforecast->forecastday[$i]->avehumidity);
                        SetValue($this->GetIDForIdent("Pop".$i), $WeatherNow->forecast->simpleforecast->forecastday[$i]->pop);
                        SetValue($this->GetIDForIdent("Icon".$i), $WeatherNow->forecast->simpleforecast->forecastday[$i]->icon);
                        SetValue($this->GetIDForIdent("Condition".$i), $WeatherNow->forecast->simpleforecast->forecastday[$i]->conditions);
                        SetValue($this->GetIDForIdent("TempLow".$i), $WeatherNow->forecast->simpleforecast->forecastday[$i]->low->celsius);
                        SetValue($this->GetIDForIdent("TempHigh".$i), $WeatherNow->forecast->simpleforecast->forecastday[$i]->high->celsius);
                    }
                }
            }
        }

	private function WithoutSpecialChars($String){
		return str_replace(array("ä", "ö", "ü", "Ä", "Ö", "Ü", "ß"), array("a", "o", "u", "A", "O", "U", "ss"), $String);
	}
	//JSON String abfragen und als decodiertes Array zurückgeben
	private function RequestAPI($URLString) {
		$location = $this->WithoutSpecialChars($this->ReadPropertyString("Location"));  // Location
		$APIkey = $this->ReadPropertyString("APIKey");  // API Key Wunderground
		$this->SendDebug("WGW Requested URL", "http://api.wunderground.com/api/".$APIkey.$URLString."/".$location.".json", 0);
		$content = file_get_contents("http://api.wunderground.com/api/".$APIkey.$URLString."/".$location.".json");  //Json Daten öffnen
		if ($content === false) {
			throw new Exception("Die Wunderground-API konnte nicht abgefragt werden!");
		}
		
		$content = json_decode($content);
		
		if (isset($content->response->error)) {
			throw new Exception("Die Anfrage bei Wunderground beinhaltet Fehler: ".$content->response->error->description);
		}
		return $content;
	}
	// Variablenprofile erstellen
	private function CreateVarProfile($name, $ProfileType, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Icon) {
		if (!IPS_VariableProfileExists($name)) {
			IPS_CreateVariableProfile($name, $ProfileType);
			IPS_SetVariableProfileText($name, "", $Suffix);
			IPS_SetVariableProfileValues($name, $MinValue, $MaxValue, $StepSize);
			IPS_SetVariableProfileDigits($name, $Digits);
			IPS_SetVariableProfileIcon($name, $Icon);
		 }
	}
	//Variablenprofil für die Windgeschwindigkeit erstellen
	private function CreateVarProfileIPSWGWWindSpeedKmh() {
		if (!IPS_VariableProfileExists("WGW.WindSpeedkmh")) {
			IPS_CreateVariableProfile("WGW.WindSpeedkmh", 2);
			IPS_SetVariableProfileText("WGW.WindSpeedkmh", "", " km/h");
			IPS_SetVariableProfileValues("WGW.WindSpeedkmh", 0, 200, 0);
			IPS_SetVariableProfileDigits("WGW.WindSpeedkmh", 1);
			IPS_SetVariableProfileIcon("WGW.WindSpeedkmh", "WindSpeed");
			IPS_SetVariableProfileAssociation("WGW.WindSpeedkmh", 0, "%.1f", "", 0xFFFF00);
			IPS_SetVariableProfileAssociation("WGW.WindSpeedkmh", 2, "%.1f", "", 0x66CC33);
			IPS_SetVariableProfileAssociation("WGW.WindSpeedkmh", 4, "%.1f", "", 0xFF6666);
			IPS_SetVariableProfileAssociation("WGW.WindSpeedkmh", 6, "%.1f", "", 0x33A488);
			IPS_SetVariableProfileAssociation("WGW.WindSpeedkmh", 10, "%.1f", "", 0x00CCCC);
			IPS_SetVariableProfileAssociation("WGW.WindSpeedkmh", 20, "%.1f", "", 0xFF33CC);
			IPS_SetVariableProfileAssociation("WGW.WindSpeedkmh", 36, "%.1f", "", 0XFFCCFF);
                     
		 }
	}
	//Variablenprofil für den UVIndex erstellen
	private function CreateVarProfileIPSWGWVIndex() {
		if (!IPS_VariableProfileExists("WGW.UVIndex")) {
			IPS_CreateVariableProfile("WGW.UVIndex", 1);
			IPS_SetVariableProfileValues("WGW.UVIndex", 0, 12, 0);
			IPS_SetVariableProfileAssociation("WGW.UVIndex", 0, "%.1f", "" , 0xC0FFA0);
			IPS_SetVariableProfileAssociation("WGW.UVIndex", 3, "%.1f", "" , 0xF8F040);
			IPS_SetVariableProfileAssociation("WGW.UVIndex", 6, "%.1f", "" , 0xF87820);
			IPS_SetVariableProfileAssociation("WGW.UVIndex", 8, "%.1f", "" , 0xD80020);
			IPS_SetVariableProfileAssociation("WGW.UVIndex", 11, "%.1f", "" , 0xA80080);
		 }
	}
 }
?>