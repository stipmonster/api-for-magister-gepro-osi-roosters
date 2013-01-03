<?php
// api for magister
// Copyright (C) 2010-2011  Erik Kooistra and Koen Wolters
// 
// This library is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public
// License as published by the Free Software Foundation; either
// version 2.1 of the License, or (at your option) any later version.
// 
// This library is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
// Lesser General Public License for more details.
// 
// You should have received a copy of the GNU Lesser General Public
// License along with this library; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA

class rooster
{
	// ============
	// = variablen en array's=
	// ============
	private $schoolid; // het id van de school
	private $jaarlaag; 
	private $leerlingnummer; 
	private $debug = FALSE; //TRUE een print_r van de array false geen
	private $tab = array(0 =>"",1=>"",2=>"",3=>"",4=>"",5=>"");
	private $tabid;
	private $tabarray = array(0 =>"",1=>"",2=>"",3=>"",4=>"",5=>"");
	private $correct;
	private $modify;

	// ===========================
	// = private setup functions =
	// ===========================
	private function explodearray($vtmp)
	{
	    
		$array = explode(" ",$vtmp);
		$array["teacher"] = $array[0];
		$array["lesson"] = $array[1];
		$array["room"] = $array[2];
		$array["classNumber"] = $array[3];
		unset($array[0],$array[1],$array[2],$array[3]);
		return $array;
	}

	private function getDocument($url)// curl aanroep 
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, "Googlebot/2.1 (http://www.googlebot.com/bot.html)");
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$html = curl_exec($ch);

		return $html;
	}
	private function getDaysInWeek ($weekNumber, $year) //zet weeknummer om in datum
	{ 
		// Count from '0104' because January 4th is always in week 1
		// (according to ISO 8601).
		$time = strtotime($year . '0104 +' . ($weekNumber - 1)
			. ' weeks');
		// Get the time of the first day of the week
		$mondayTime = strtotime('-' . (date('w', $time) - 1) . ' days', $time);
		// Get the times of days 0 -> 6
		$dayTimes = array ();
		for ($i = 0; $i < 7; ++$i) {
			$dayTimes[] = strtotime('+' . $i . ' days', $mondayTime);
		}
		// Return timestamps for mon-sun.
		return $dayTimes;
	}

	private function stripweek($weeknummer)// verwijders alle onzin en return alleen het nummer FIXME:oplossing verzinnen voor basis rooster 
	{
		$tmp=explode(' ',$weeknummer);
		if ($tmp[0]=="Planning") {
			return $tmp[2];
		}elseif($tmp[0]=="Wijzigingen") {
		
			return $tmp[2];
		}
		return 0;
	}
	private function clear($clear)
	{
		/*
		* Als een persoon meerdere vakken op 1 uur heeft is de totale lengete minimaal 40 tekens wanneer een persoon maar 1 uur heeft is het minder, de >10 is voor het splitsen van de 2 vakken in 2 arrays daarvoor word de functie explodearray gebruikt.
		Vraag me niet waarvoor al de str_replace zijn, het werkt nu en ik weet zeker wanneer ik er iets aan ga verandern dat het dan niet meer werkt dus blijf er van af.

*/
$count = 1;
$replace=$clear;
if (strlen($clear)>40) {//VOODOO WARING
	$rep1=str_replace(" ".chr(10),"",str_replace(chr(10)." "," ",str_replace(chr(10)." ".chr(10)." "," ",str_replace('&amp;nbsp','',htmlentities($clear)))));
	$rep=str_replace(chr(10),"%",substr_replace($rep1,"",-1,1));
	$replace= str_replace("   ","",str_replace("     ","",$rep));
}else{
	$replace= str_replace("  ","",str_replace("     ","",str_replace(chr(10),"", str_replace(" ".chr(10),"",str_replace(chr(10)." "," ",str_replace(chr(10)." ".chr(10)." "," ",str_replace('&amp;nbsp','',htmlentities($clear))))))));
}
if (strlen($replace)>10) {
	$tmp=explode("%",$replace);
	$replace = array_map(array($this, "explodearray"),$tmp);
}
if ($replace=="? ? SEW ."|| $replace=="? ? SEWK .") {
    return array(array("text"=>"se-week"));
}
if ($replace=="? ? vry"||$replace=="vrij\r") {
    return array(array("text"=>"vrij"));
}
return $replace;
}

// ===================
// = public funtions =
// ===================
function __construct($school,$jlaag,$lnummer)
{
	$this->schoolid = $school;
	$this->jaarlaag = $jlaag;
	$this->leerlingnummer = $lnummer;
	$this->tab[0]= $this->getDocument("http://roosters5.gepro-osi.nl/roosters/rooster.php?leerling=".$this->leerlingnummer."&type=Leerlingrooster&afdeling=".$this->jaarlaag."&tabblad=0&school=".$this->schoolid);
	$this->tab[1]=$this->getDocument("http://roosters5.gepro-osi.nl/roosters/rooster.php?wijzigingen=1&leerling=".$this->leerlingnummer."&type=Leerlingrooster&afdeling=".$this->jaarlaag."&tabblad=0&school=".$this->schoolid);
		//DIT IS EEN SERVERSIDE CACHE BEN ALLEEN BANG DAT IK DOOR MIJN SCHIJFRUIMTE HEEN GA DUS LAAT HET NU NOG FF TUSSEN COMMENT TAGS STAAN. 
		/*$file=glob("./tmp/rooster-".$i."-*");
		if(md5($this->tab[$i])==md5_file($file[0]))
		{
		$this->modify[$i]=FALSE;
		$this->correct[$i]=TRUE;
		}else{
		$this->modify[$i]=TRUE;

if ($this->tab[$i]==FALSE) {
foreach (glob("./tmp/rooster-".$this->leerlingnummer."-".$i."-*") as $filename) {
$file=fopen($filename,"r+");
$this->tab[$i]=fread($file,filesize($filename));
}
}else{
foreach (glob("./tmp/rooster-".$this->leerlingnummer."-".$i."-*") as $filename) {
unlink($filename);
}

if($file=fopen("./tmp/rooster-".$this->leerlingnummer."-".$i."-".time(),"w+"))
{
fwrite($file,$this->tab[$i]);
}
}
}*/

}

public function getTitle($idt)// id is nummer tussen 0-5 of ALL
{
	if($idt=="ALL"){
		for ($i=0; $i <= 5; $i++) { 
			$html=$this->tab[$i];
			$dom = new DOMDocument(); 
			@$dom->loadHtml($html);
			$xpath= new DOMXpath($dom);
			$weektitle = $xpath->evaluate("//span[@class='fnttabon']");
			if ($weektitle->length!=0) {
				$tmp[$i]=$weektitle->item(0)->textContent; 

			}else{
				$tmp[$i]=NULL;
			}
		}     
		return $tmp;
	}else{ 
		$html=$this->tab[$idt];
		$dom = new DOMDocument(); 
		@$dom->loadHtml($html);
		$xpath= new DOMXpath($dom);
		$weektitle = $xpath->evaluate("//span[@class='fnttabon']");
		return $weektitle->item(0)->textContent; 
	}

}
public function getWeek($tab)
{
	//print $this->stripweek($this->getTitle($tab));
	return $this->stripweek($this->getTitle($tab));
}
public function getArray($id)
{
	$html=$this->tab[$id];
	$dom = new DOMDocument(); 
	@$dom->loadHtml($html);
	$uur = array(1 => "", 2 => "",3 => "",4 => "",5 => "",6 => "",7 => "",8 => "",9 => "",10 => "",);
	$rooster = array('week'=>'','ma'=>$uur ,'di'=>$uur,'wo'=>$uur,'do'=>$uur,'vr'=>$uur);
	$rooster['week']=$this->getTitle($id);

	$dagen=array(0=>'ma',1=>'di',2=>'wo',3=>'do',4=>'vr');
	$xpath = new DOMXPath($dom);
	$nodes = $xpath->evaluate("/html/body/div/table/tr[2]/td/table[4]/tr/td[3]/table/tr[5]/td|/html/body/div/table/tr[2]/td/table[4]/tr/td[3]/table/tr[6]/td|/html/body/div/table/tr[2]/td/table[4]/tr/td[3]/table/tr[7]/td|/html/body/div/table/tr[2]/td/table[4]/tr/td[3]/table/tr[8]/td|/html/body/div/table/tr[2]/td/table[4]/tr/td[3]/table/tr[9]/td|/html/body/div/table/tr[2]/td/table[4]/tr/td[3]/table/tr[10]/td|/html/body/div/table/tr[2]/td/table[4]/tr/td[3]/table/tr[11]/td|/html/body/div/table/tr[2]/td/table[4]/tr/td[3]/table/tr[12]/td|/html/body/div/table/tr[2]/td/table[4]/tr/td[3]/table/tr[13]/td|/html/body/div/table/tr[2]/td/table[4]/tr/td[3]/table/tr[14]/td|/html/body/div/table/tr[2]/td/table[4]/tr/td[3]/table/tr[15]/td");
	for ($i=0; $i < $nodes->length; $i++) {
		for($j=1; $j <= 10; $j++){
			if($this->clear($nodes->item($i)->nodeValue)=='0'.$j.'e uur'){
				for ($k=0; $k < 5; $k++) {
					$l=$i+$k; 
					$l++;
					$rooster[$dagen[$k]][$j]=$this->clear($nodes->item($l)->nodeValue);
					$l=0;
					if ($rooster[$dagen[$k]][$j]==NULL) {
						$rooster[$dagen[$k]][$j]=Array ("teacher" =>" ","lesson"=> " ", "room" => " ", "classNumber" => " " ) ;  
					}
				}
			}
		}
	}
	$this->tabarray[$id]=$rooster;
}

public function getDay($id,$day){
	if ($this->tabarray[$id]==NULL) {
		$this->getArray($id);
	}
	return $this->tabarray[$id][$day];
}

public function getChange($id)
{
	return $this->modify[$id];
}

}
?>