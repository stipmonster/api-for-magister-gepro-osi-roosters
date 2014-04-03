Inleiding
===============
Deze API is om gegevens op te halen van http://roosters5.gepro-osi.nl/roosters/rooster.php, mocht je school dit gebruiken dan kun je deze API hiervoor gebruiken.

Voor een C# implementatie zie: https://github.com/lieuwex/GEPRO_OSIsharp

Hoe te gebruiken
===============
De API is simpel te gebruiken in PHP, ik geef je een voorbeeld:

```PHP
<?php
include 'rooster.class.php'; //De nieuwste versie van de API laden.
$rooster = new rooster(123,'vwo1',1234567,10); //Nieuw rooster aanmaken, tussen haakjes: Schoolnummer, richting, leerling, aantal lesuren.
                                          //Zijn te vinden in de url.
?>

<bla bla bla een hoop html>

<?php //Php oproepen op het moment dat je het rooster wilt tonen
$rooster->getArray(0);                    //API oproepen, dat hij rooster moet geven
$dag=$rooster->getDay(1,"ma");            //Zeggen welke dag de API op moet halen
$counter=1;                               //Zorgen dat er een nummer voor de lessen komt
foreach($dag as $uur) {                   //Voor iedere dag een uur
  foreach($uur as $les){                  //Voor ieder uur een les
		echo "".$counter." <br> ".$les["teacher"]." - ".$les["room"]." - ".$les["lesson"]."\n <br>"; //Uitvoeren in HTML
	}
	$counter++;                             //1 Optellen bij de couter
}
?>
```

Huidige magister
=================
De huidige magister 5 is geschreven in silverlight.

Ze hebben op dit moment een html5 beta, iemand heeft daarvoor https://github.com/lieuwex/MataSharp geschreven in c#, om dit te kunnen gebruiken in je eigen api.


FAQ
====
V:

Waarom doet de API het bij mijn school niet?


A:

Sinds 0.9.1 zouden alle scholen het moeten doen, omdat er geen hardcoded waardes meer zijn, werkt het toch niet meen gerust contact met me op.

Vragen
=======
Je kunt vragen altijd aan mij stellen:

stipmonster (https://github.com/stipmonster)

Copyright
==========
Originele API door Stipmonster

De API is licensed onder de GNU LESSER GENERAL PUBLIC LICENSE, zie de LICENSE file voor meer informatie.

Documentatie en wiki door tkon99 & stipmonster

Documentatie en uitleg door tkon99 & stipmonster

