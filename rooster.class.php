<?php
// api for schoolmasters gepro-osi
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
    private $tab = array(0 =>"",1=>"",2=>"",3=>"",4=>"",5=>"");
    private $tabid;
    private $tabarray = array(0 =>"",1=>"",2=>"",3=>"",4=>"",5=>"");
    private $aantalUren;

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

    private function stripweek($weeknummer)
    {
        return filter_var($weeknummer,FILTER_SANITIZE_NUMBER_INT);
    }

    private function dom2vakken($node)
    {
        $new = new DomDocument;
        $new->appendChild($new->importNode($node, true));
        $counter=0;
        foreach ($new->getElementsByTagName("tr") as $tr) {
            $newtr = new DomDocument;
            $newtr->appendChild($newtr->importNode($tr, true));
            $bla=$newtr->getElementsByTagName("td");
            $array[$counter]["teacher"] = trim($bla->item(0)->textContent);
            $array[$counter]["lesson"] = trim($bla->item(4)->textContent);
            $array[$counter]["room"] = trim($bla->item(2)->textContent);
            $array[$counter]["classNumber"] = trim($bla->item(7)->textContent);
            $counter++;
        }
        if (!is_array($array)) {
            return Array ("teacher" =>"","lesson"=> "", "room" => "", "classNumber" => "" );
        }
        return $array;
    }

    // ===================
    // = public funtions =
    // ===================
    function __construct($school,$jlaag,$lnummer,$lesuren)
    {
        $this->schoolid = $school;
        $this->jaarlaag = $jlaag;
        $this->leerlingnummer = $lnummer;
        $this->aantalUren= $lesuren;
        for ($i=0; $i <= 5; $i++) { 
            $this->tab[$i] = $this->getDocument("http://roosters5.gepro-osi.nl/roosters/rooster.php?leerling=". $this->leerlingnummer."&type=Leerlingrooster&afdeling=".$this->jaarlaag."&tabblad=".$i."&school=".$this->schoolid);

        }

    }

    public function getTitle($idt)// id is nummer tussen 0-5 of -1
    {
        if($idt==-1){
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
        return $this->stripweek($this->getTitle($tab));
    }
    
    public function getArray($id)
    {
        $html=$this->tab[$id];
        $dom = new DOMDocument(); 
        @$dom->loadHtml($html);
        $rooster['week']=$this->getTitle($id);

        $dagen=array(0=>'ma',1=>'di',2=>'wo',3=>'do',4=>'vr');
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->evaluate("/html/body/div/table/tr[2]/td/table[4]/tr/td[3]/table/tr");
        for ($i=5; $i < ($nodes->length-(12-$this->aantalUren)); $i++) {
            $lesuur = new DomDocument;
            $lesuur->appendChild($lesuur->importNode($nodes->item($i), true));
            $lesuren=$lesuur->getElementsByTagName("table");
            $counter=0;
            foreach ($lesuren as $les) {
                $rooster[$dagen[$counter]][$i-4]=$this->dom2vakken($les);
                $counter++;
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
    
}
?>
