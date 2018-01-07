<?

	class Abfallkalender extends IPSModule
	{

	
	
	
	
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			
			$this->RegisterPropertyString("region", "4");
			$this->RegisterPropertyString("area", "789");
			$this->RegisterPropertyString("ort", "916");
			$this->RegisterPropertyString("strasse", "4411");
			$this->RegisterPropertyString("zeittakt", "30");
			$this->RegisterTimer("Updatetonne",1800000,'ZAOE_Update($_IPS[\'TARGET\']);'); 
			
			
			
		}		
	
		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
			
			$this->RegisterVariableString("tonne", "Tonne");
			$this->RegisterVariableBoolean("IsAbholung", "Ist Abholung ?");
			$this->RegisterVariableString("WasteTime", "Restabfall 80-240l Tonne");
			$this->RegisterVariableString("BioTime", "Bioabfall 60-240l Tonne");
			$this->RegisterVariableString("RecycleTime", "Gelbe Säcke/Gelbe Tonne");
			$this->RegisterVariableString("PaperTime", "Papier/Pappe 120/240l Tonne");
			$this->SetTimerInterval("Updatetonne", $this->ReadPropertyString("zeittakt") * 60000);
			$this->Update();
			
			
			
			
		}
		/**
		* This function will be available automatically after the module is imported with the module control.
		* Using the custom prefix this function will be callable from PHP and JSON-RPC through:
		*
		* EZVO_RequestInfo($id);
		*
		*/
	 
		   public function Update()
		{
	    
			try
        {
            $TonneB = $this->GetTonneB()[0];
			$AbholungB = $this->GetTonneB()[1];
			$TonneP = $this->GetTonneP()[0];
			$AbholungP = $this->GetTonneP()[1];
		    $TonneG = $this->GetTonneG()[0];
			$AbholungG = $this->GetTonneG()[1];
			$TonneR = $this->GetTonneR()[0];
			$AbholungR = $this->GetTonneR()[1];
		}
        catch (Exception $exc)
        {
            trigger_error($exc->getMessage(), $exc->getCode());
            $this->SendDebug('ERROR', $exc->getMessage(), 0);
            return false;
        }

			$TonneB = str_replace("Keine Tonne", "", $TonneB);
			$TonneP = str_replace("Keine Tonne", "", $TonneP);
			$TonneG = str_replace("Keine Tonne", "", $TonneG);
			$TonneR = str_replace("Keine Tonne", "", $TonneR);

			$laengeB = strlen($TonneB);
			$laengeP = strlen($TonneP);
			$laengeG = strlen($TonneG);
			$laengeR = strlen($TonneR);


			$TonneArray = array();
				if ($laengeB != 0) {
				$TonneArray[] =$TonneB;
				}
				if ($laengeP != 0) {
				$TonneArray[] =$TonneP;
				}
				if ($laengeG != 0) {
				$TonneArray[] =$TonneG;
				}
				if ($laengeR != 0) {
				$TonneArray[] =$TonneR;
				}

			$Tonne = implode(", ", $TonneArray);

		
		$this->SetValueString("BioTime", $AbholungB);
		$this->SetValueString("PaperTime", $AbholungP);
		$this->SetValueString("RecycleTime", $AbholungG);
		$this->SetValueString("WasteTime", $AbholungR);
		if ($Tonne == "")
        {
            $this->SetValueBoolean("IsAbholung", false);
			$this->SetValueString("tonne", "Keine Abholung");
        }
        else
        {
            $this->SetValueBoolean("IsAbholung", true);
			$this->SetValueString("tonne", $Tonne);
        }
        return true;
    }
	
			   public function GetTonneB()
    {
		 
	   
        $jahr = substr(date("Y"),2);
		//$link = 'https://www.zaoe.de/ical/download/' . $this->ReadPropertyString("strasse") . '/18/?tx_kalenderausgaben_pi3%5Bauswahl_start%5D=01.01.'. $jahr . '&tx_kalenderausgaben_pi3%5Bauswahl_end%5D=31.12.'. $jahr . '&tx_kalenderausgaben_pi3%5Bauswahl_tonnen_ids%5D%5B0%5D=1&tx_kalenderausgaben_pi3%5Bauswahl_tonnen_ids%5D%5B1%5D=3&tx_kalenderausgaben_pi3%5Bauswahl_tonnen_ids%5D%5B2%5D=4&tx_kalenderausgaben_pi3%5Bauswahl_tonnen_ids%5D%5B3%5D=6&tx_kalenderausgaben_pi3%5Bswitch%5D=ical&tx_kalenderausgaben_pi3%5Bauswahl_zeitraum%5D=18';
		$link = "https://www.zaoe.de/ical/" . $this->ReadPropertyString("strasse") . "/_3/" . $jahr ."/";
		$this->SendDebug('GET', $link, 0);
        $meldung = @file($link);
        if ($meldung === false)
          throw new Exception("Cannot load iCal Data.", E_USER_NOTICE);
        $this->SendDebug('LINES', count($meldung), 0);
		
		$tonne = "Keine Tonne";
		$tonnedate = "-";
		$jetzt = date("Ymd",time());
		$searchstr		= 'DTSTART';
		
		foreach ($meldung as $line ) 
			{ 
				if((strpos($line, $searchstr) !== false))
				{ 
					$dateprobe[] = substr(strstr($line,":"),1);
				}
			} 
			sort($dateprobe);

			$result="0";
			for($i=0;$i<count($dateprobe);$i++)
				{
				if($jetzt<=$dateprobe[$i])
					{
					$start=$dateprobe[$i];
					break;
					}    
				}
			
			$start = trim($start, " \t\n\r\0\x0B");
				$this->SendDebug('FOUND', $start , 0);
			
			if ($jetzt +1 == $start)
                {
					$tonne = "Morgen Bioabfall";
					$this->SendDebug('FOUND', $tonne , 0);
                }
			elseif ($jetzt == $start)
				{
					$tonne = "Heute Bioabfall";
					$this->SendDebug('FOUND', $tonne , 0);
                }
			$tonnedate = date("d.m.Y", strtotime($start));
	  
		
		
		/*
		$anzahl = (count($meldung) - 1);

        for ($count = 0; $count < $anzahl; $count++)
        {
            if (strstr($meldung[$count], "SUMMARY:Bioabfall"))
            {
                $name = trim(substr($meldung[$count], 8));
                $start = trim(substr($meldung[$count + 1], 19));
                $ende = trim(substr($meldung[$count + 2], 17));
                $this->SendDebug('SUMMARY', $name, 0);
                $this->SendDebug('START', $start, 0);
                $this->SendDebug('END', $ende, 0);
                $jetzt = date("Ymd",time()) ;
				$jetzt1 = date("Ymd",time() + 86400);
				$jetzt2 = date("Ymd",time() + 172800);
				$jetzt3 = date("Ymd",time() + 259200);
				$jetzt4 = date("Ymd",time() + 345600);
				$jetzt5 = date("Ymd",time() + 432000);
				$jetzt6 = date("Ymd",time() + 518400);				
				$jetzt7 = date("Ymd",time() + 604800);
			//	if (($jetzt6 == $start) || ($jetzt5 == $start) || ($jetzt4 == $start) || ($jetzt3 == $start) || ($jetzt2 == $start) || ($jetzt1 == $start) || ($jetzt == $start) )
				
				if (($jetzt +1 == $start))
                {
					$tonne = "Morgen " . explode(' ', $name) [0];
					$this->SendDebug('FOUND', $tonne , 0);
                }
				elseif ($jetzt == $start)
				{
					$tonne = "Heute " . explode(' ', $name) [0];
					$this->SendDebug('FOUND', $tonne , 0);
                }
			
				if ($jetzt <= $start)
				{
					$tonnedate = date("d.m.Y", strtotime($start));
					break;
				}
			

				
            }
			
        }
	
		*/
		return array($tonne,$tonnedate);
		
    } 
	
			   public function GetTonneP()
    {
		 
	    
        $jahr = substr(date("Y"),2);
		//$link = 'https://www.zaoe.de/ical/download/' . $this->ReadPropertyString("strasse") . '/18/?tx_kalenderausgaben_pi3%5Bauswahl_start%5D=01.01.'. $jahr . '&tx_kalenderausgaben_pi3%5Bauswahl_end%5D=31.12.'. $jahr . '&tx_kalenderausgaben_pi3%5Bauswahl_tonnen_ids%5D%5B0%5D=1&tx_kalenderausgaben_pi3%5Bauswahl_tonnen_ids%5D%5B1%5D=3&tx_kalenderausgaben_pi3%5Bauswahl_tonnen_ids%5D%5B2%5D=4&tx_kalenderausgaben_pi3%5Bauswahl_tonnen_ids%5D%5B3%5D=6&tx_kalenderausgaben_pi3%5Bswitch%5D=ical&tx_kalenderausgaben_pi3%5Bauswahl_zeitraum%5D=18';
		$link = "https://www.zaoe.de/ical/" . $this->ReadPropertyString("strasse") . "/_4/" . $jahr ."/";
		$this->SendDebug('GET', $link, 0);
        $meldung = @file($link);
        if ($meldung === false)
          throw new Exception("Cannot load iCal Data.", E_USER_NOTICE);
        $this->SendDebug('LINES', count($meldung), 0);
		
		$tonne = "Keine Tonne";
		$tonnedate = "-";

		$jetzt = date("Ymd",time());
		$searchstr		= 'DTSTART';
		
		foreach ($meldung as $line ) 
			{ 
				if((strpos($line, $searchstr) !== false))
				{ 
					$dateprobe[] = substr(strstr($line,":"),1);
				}
			} 
			sort($dateprobe);

			$result="0";
			for($i=0;$i<count($dateprobe);$i++)
				{
				if($jetzt<=$dateprobe[$i])
					{
					$start=$dateprobe[$i];
					break;
					}    
				}
			
			$start = trim($start, " \t\n\r\0\x0B");
			$this->SendDebug('FOUND', $start , 0);
			if ($jetzt +1 == $start)
                {
					$tonne = "Morgen Papiertonne";
					$this->SendDebug('FOUND', $tonne , 0);
                }
			elseif ($jetzt == $start)
				{
					$tonne = "Heute Papiertonne";
					$this->SendDebug('FOUND', $tonne , 0);
                }
			$tonnedate = date("d.m.Y", strtotime($start));
		
		
		return array($tonne,$tonnedate);
		
    }
	
			   public function GetTonneG()
    {
		
 
		$jahr = substr(date("Y"),2);
		//$link = 'https://www.zaoe.de/ical/download/' . $this->ReadPropertyString("strasse") . '/18/?tx_kalenderausgaben_pi3%5Bauswahl_start%5D=01.01.'. $jahr . '&tx_kalenderausgaben_pi3%5Bauswahl_end%5D=31.12.'. $jahr . '&tx_kalenderausgaben_pi3%5Bauswahl_tonnen_ids%5D%5B0%5D=1&tx_kalenderausgaben_pi3%5Bauswahl_tonnen_ids%5D%5B1%5D=3&tx_kalenderausgaben_pi3%5Bauswahl_tonnen_ids%5D%5B2%5D=4&tx_kalenderausgaben_pi3%5Bauswahl_tonnen_ids%5D%5B3%5D=6&tx_kalenderausgaben_pi3%5Bswitch%5D=ical&tx_kalenderausgaben_pi3%5Bauswahl_zeitraum%5D=18';
		$link = "https://www.zaoe.de/ical/" . $this->ReadPropertyString("strasse") . "/_6/" . $jahr ."/";
		$this->SendDebug('GET', $link, 0);
        $meldung = @file($link);
        if ($meldung === false)
          throw new Exception("Cannot load iCal Data.", E_USER_NOTICE);
        $this->SendDebug('LINES', count($meldung), 0);
		
		$tonne = "Keine Tonne";
		$tonnedate = "-";
		$jetzt = date("Ymd",time()) ;
		
		
    
		$searchstr		= 'DTSTART';
		foreach ($meldung as $line ) 
			{ 
				if((strpos($line, $searchstr) !== false))
				{ 
					$dateprobe[] = substr(strstr($line,":"),1);
				}
			} 
			sort($dateprobe);

			$result="0";
			for($i=0;$i<count($dateprobe);$i++)
				{
				if($jetzt<=$dateprobe[$i])
					{
					$start=$dateprobe[$i];
					break;
					}    
				}
				
				$start = trim($start, " \t\n\r\0\x0B");
				$this->SendDebug('FOUND', $start , 0);
				
				
				
				if (($jetzt +1 == $start))
                {
					$tonne = "Morgen gelber Sack";
					$this->SendDebug('FOUND', $tonne , 0);
                }
				elseif ($jetzt == $start)
				{
					$tonne = "Heute gelber Sack";
					$this->SendDebug('FOUND', $tonne , 0);
                }
	  
				$tonnedate = date("d.m.Y", strtotime($start));
				
		
		return array($tonne,$tonnedate);
		
    }	
		
    
	
			   public function GetTonneR()
    {
		$jahr = substr(date("Y"),2);
		//$link = 'https://www.zaoe.de/ical/download/' . $this->ReadPropertyString("strasse") . '/18/?tx_kalenderausgaben_pi3%5Bauswahl_start%5D=01.01.'. $jahr . '&tx_kalenderausgaben_pi3%5Bauswahl_end%5D=31.12.'. $jahr . '&tx_kalenderausgaben_pi3%5Bauswahl_tonnen_ids%5D%5B0%5D=1&tx_kalenderausgaben_pi3%5Bauswahl_tonnen_ids%5D%5B1%5D=3&tx_kalenderausgaben_pi3%5Bauswahl_tonnen_ids%5D%5B2%5D=4&tx_kalenderausgaben_pi3%5Bauswahl_tonnen_ids%5D%5B3%5D=6&tx_kalenderausgaben_pi3%5Bswitch%5D=ical&tx_kalenderausgaben_pi3%5Bauswahl_zeitraum%5D=18';
		$link = "https://www.zaoe.de/ical/" . $this->ReadPropertyString("strasse") . "/_1/" . $jahr ."/";
		$this->SendDebug('GET', $link, 0);
        $meldung = @file($link);
        if ($meldung === false)
          throw new Exception("Cannot load iCal Data.", E_USER_NOTICE);
        $this->SendDebug('LINES', count($meldung), 0);
		
		$tonne = "Keine Tonne";
		$tonnedate = "-";
		
		$jetzt = date("Ymd",time());
		$searchstr		= 'DTSTART';
		
		foreach ($meldung as $line ) 
			{ 
				if((strpos($line, $searchstr) !== false))
				{ 
					$dateprobe[] = substr(strstr($line,":"),1);
				}
			} 
			sort($dateprobe);

			$result="0";
			for($i=0;$i<count($dateprobe);$i++)
				{
				if($jetzt<=$dateprobe[$i])
					{
					$start=$dateprobe[$i];
					break;
					}    
				}
			
			$start = trim($start, " \t\n\r\0\x0B");
				$this->SendDebug('FOUND', $start , 0);
			
			if ($jetzt +1 == $start)
                {
					$tonne = "Morgen Restabfall";
					$this->SendDebug('FOUND', $tonne , 0);
                }
			elseif ($jetzt == $start)
				{
					$tonne = "Heute Restabfall";
					$this->SendDebug('FOUND', $tonne , 0);
                }
			$tonnedate = date("d.m.Y", strtotime($start));
		
		
		return array($tonne,$tonnedate);
		
    }
	
	  private function SetValueBoolean(string $Ident, bool $value)
    {
        $id = $this->GetIDForIdent($Ident);
        SetValueBoolean($id, $value);
    }
	
	private function SetValueString(string $Ident, string $value)
    {
        $id = $this->GetIDForIdent($Ident);
        SetValueString($id, $value);
    }
		
		
	}

?>
