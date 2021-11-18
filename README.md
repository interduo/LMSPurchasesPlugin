# LMSPurchasePlugin 

Opis:
Wtyczka: LMS Purchases documents Plugin (PD)

Wtyczka z listą dokumentów zakupowych.
**UWAGA: Wsparcie tylko dla PostgreSQL.**

Instalacja wtyczki:
1. git submodule add https://github.com/interduo/LMSPurchasesPlugin/ plugins/LMSPurchasesPlugin
2. composer update --no-dev
3. Uaktywnić wtyczkę z poziomu interfejsu użytkownika LMS.
4. Nadaj uprawnienia użytkownikowi do wtyczki

**TODO: (dla wersji beta)**
- wrzucanie skanów dokumentów kosztowych z podglądem,
- kategorie dla dokumentów finansowych,

**TODO: (po beta)**
- rozbijanie dokumentów na "wydatki",
- export nierozliczonych dokumentów kosztowych do pliku CSV jako wsad do listy przelewów do banku,
- podsumowanie dokumentów kosztowych na dashboard,

Jarosław Kłopotek <jkl@interduo.pl>,
Grzegorz Cichowski <gc@ptlanet.pl>
