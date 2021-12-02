# LMSPurchasePlugin 

Wtyczka: LMSPurchase Plugin (PD)
Opis: wtyczka służąca do ewidencji dokumentów zakupowych.

**UWAGA: Wsparcie tylko dla PostgreSQL.**

Instalacja wtyczki:
1. git submodule add https://github.com/interduo/LMSPurchasesPlugin/ plugins/LMSPurchasesPlugin
2. composer update --no-dev
3. Uaktywnić wtyczkę z poziomu interfejsu użytkownika LMS.
4. Nadaj uprawnienia użytkownikowi do wtyczki

**TODO: (dla wersji 1.0)**
- porządek w kodzie z trzymaniem walut i netto/brutto,

**TODO:**
- podgląd dla wrzuconych dokumentów obok formularza, 
- rozbijanie dokumentów na "wydatki",
- obsługa walut,
- konta dostawców pobierane z białej listy podatników,
- export nierozliczonych dokumentów kosztowych do pliku CSV jako wsad do listy przelewów do banku,

**Zmienne konfiguracyjne:**
pd.storage_dir - lokalizacja skanów faktur dla nowouploadowanych plików,
pd.default_filter_period - domyślna wartość filtra okres,

Jarosław Kłopotek <jkl@interduo.pl>,
Grzegorz Cichowski <gc@ptlanet.pl>
