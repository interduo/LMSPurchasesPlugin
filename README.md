# LMSPurchasePlugin 

Wtyczka: LMSPurchase Plugin (PD)
Opis: wtyczka służąca do ewidencji dokumentów zakupowych.

**UWAGA:**
- wsparcie tylko dla PostgreSQL,
- wsparcie dla LMS > 28.x,

Instalacja wtyczki:
1. git submodule add https://github.com/interduo/LMSPurchasesPlugin/ plugins/LMSPurchasesPlugin
2. composer update --no-dev
3. Uaktywnić wtyczkę z poziomu interfejsu użytkownika LMS.
4. Nadaj uprawnienia użytkownikowi do wtyczki

**TODO: (dla wersji 1.0)**
- podgląd dla wrzuconych dokumentów obok formularza,
- testy i porządny review kodu przez kogoś mądrzejszego,
- konta dostawców pobierane z białej listy podatników,
- export nierozliczonych dokumentów kosztowych do pliku CSV jako wsad do listy przelewów do banku,

**TODO:**
- obsługa walut,

**Zmienne konfiguracyjne:**
pd.storage_dir - lokalizacja skanów faktur dla nowouploadowanych plików,
pd.default_filter_period - domyślna wartość filtra okres,
pd.default_taxid - ID domyślnej stawki podatkowej,
pd.default_paytype - ID domyślnego typu płatności (patrz: $PAYTYPES w lms/lib/definitions.php),

Jarosław Kłopotek <jkl@interduo.pl>,
Grzegorz Cichowski <gc@ptlanet.pl>
