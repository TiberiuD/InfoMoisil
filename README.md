# InfoMoisil
InfoMoisil este o platformă online ce permite testarea automată a soluțiilor trimise de elevi pentru diferite probleme de informatică.

Cu toate că acesta nu este deloc un concept nou, InfoMoisil propune modificări esențiale, cerute de elevi, care nu se regăsesc la serviciile deja existente de acest tip. O mai bună categorizare a problemelor, o interfață intuitivă și simplă, un sistem de recompense sunt doar câteva dintre aceste facilități.

## Tehnologii
La baza proiectului stau două componente esențiale: site-ul web și Judecătorul. Cel dintâi este responsabil de interfațarea utilizatorului cu baza de date, este modul principal prin care utilizatorul interacționează cu sistemul.

Cel din urmă, Judecătorul, este însă la inima sistemului. Acesta este responsabil pentru compilarea codurilor sursă, supravegherea evaluării acestora etc.

Modularitatea sistemului de evaluare permite dezvoltarea ecosistemului proiectului, putându-se dezvolta aplicații cu scop specific (de exemplu o platformă de concurs), fără a interfera cu modulele deja existente.

Site-ul web este scris în PHP, inspirat de modelul arhitectural MVC. Este bazat la rândul lui pe alte proiecte Open-Source, precum Materialize CSS sau Twig.

Judecătorul este scris în C++. Alegerea acestui limbaj de programare poate părea cel puțin ciudată, în condițiile în care există alternative ce permit o dezvoltare mult mai rapidă a proiectelor. La baza alegerii stă performanța de neegalat a C++, și tot odată portabilitatea pe orice platformă imaginabilă. Dacă se dorește un control mai apropiat față de sistemul de operare (spre exemplu utilizarea antetelor nucleului Linux), acest lucru este aproape imposibil în alte limbaje de programare.

## Cerinte sistem
### Site Web:
Server Web cu interpretor PHP (Sunt necesare modulele openssl, mysqli)

### Judecător:

### Sisteme de operare țintă:
- Microsoft Windows (funcționalitate redusă)
- Linux
- FreeBSD

### Specificații recomandate de sistem:
Procesor compatibil x86 (se recomandă un număr ridicat de nuclee și suport AES-NI pentru funcționalități viitoare)
RAM: 100 MB disponibili
Spațiu de stocare: 500 MB disponibili

### Bază de date:
MySQL

Dacă se alege instalarea pe sisteme de calcul diferite, se recomandă adăugarea acestora într-o Rețea Virtuală Privată, și rutarea traficului prin aceasta.