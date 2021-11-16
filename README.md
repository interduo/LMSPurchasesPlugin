# LMSPurchasePlugin 

Opis:
Wtyczka: LMS Purchases documents Plugin (PD)

Wtyczka z listą dokumentów zakupowych.
**Wsparcie tylko dla postgresql.**

Instalacja wtyczki:
1. git submodule add https://github.com/interduo/LMSPurchasesPlugin/ plugins/LMSPurchasesPlugin
2. composer update --no-dev
3. Uaktywnić wtyczkę z poziomu interfejsu użytkownika LMS.
4. Nadaj uprawnienia użytkownikowi do wtyczki

**TODO: (dla wersji beta)**
- wrzucanie skanów dokumentów kosztowych,
- kategorie dla dokumentów finansowych,
- rozbijanie dokumentów na "wydatki",

**TODO: (po beta)**
- export nierozliczonych dokumentów kosztowych do pliku CSV jako wsad do listy przelewów do banku,
- podsumowanie dokumentów kosztowych na dashboard,

Jarosław Kłopotek <jkl@interduo.pl>,
Grzegorz Cichowski <gc@ptlanet.pl>
