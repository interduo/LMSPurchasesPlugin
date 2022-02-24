# LMSPurchasePlugin 

Wtyczka: LMSPurchase Plugin (PD)
Opis: wtyczka służąca do ewidencji dokumentów zakupowych.

**UWAGA:**
- wsparcie tylko dla PostgreSQL,
- wsparcie dla LMS > 28.x,

Instalacja wtyczki:
1. Przejdź do głównego katalogu LMS,
2. git clone https://github.com/interduo/LMSPurchasesPlugin/ plugins/LMSPurchasesPlugin
3. composer update --no-dev
4. Uaktywnić wtyczkę z poziomu interfejsu użytkownika LMS.
5. Nadaj uprawnienia użytkownikowi do wtyczki

Aktualizacja wtyczki:
1. cd plugins/LMSPurchasesPlugin; git fetch origin; git pull origin main;

**TODO: (dla wersji 2.0)**
- testy i porządny review kodu przez kilku mądrzejszych,

**TODO:**
- obsługa walut,
- refaktoring/naprawa modułu dashboard - rozbudowa funkcji GetPurchaseList() o liczenie sum, (Grzegorz)
- obsługa potrzeb, zamówień i wiązanie z dokumentami,

**Zmienne konfiguracyjne:**
- **pd.storage_dir** - lokalizacja skanów faktur dla nowouploadowanych plików,
- **pd.default_filter_period** - domyślna wartość filtra okres,
- **pd.default_divisionid** - ID domyślnego oddziału firmy,
- **pd.default_taxid** - ID domyślnej stawki podatkowej,
- **pd.default_paytype** - ID domyślnego typu płatności (patrz: $PAYTYPES w lms/lib/definitions.php),
- **pd.source_iban** - numer źródłowego rachunku bankowego dla pliku eksportu płatności do banku,
- **pd.export_filename** - nazwa pliku exportu

Do czego służy wtyczka:
1. Ewidencja wydatków - szybki dostęp do dokumentów kosztowych i ich skanów.
2. Export koszyka niezapłaconych faktur jako wrzut płatności do koszyka banku.
3. Statystykowanie wydatków firmy z uwzględnieniem podziału na projekty/kategorie wydatków/stan płatności.
4. Korzystanie z białej listy podatników pomaga dodatkowo zabezpieczyć się przed utratą miedziaków.
5. Export dokumentów kosztowych do systemu księgowego.

Wtyczka nie jest i nie będzie substytutem systemu księgowego ani magazynu.

Jarosław Kłopotek <jkl@interduo.pl>,
Grzegorz Cichowski <gc@ptlanet.pl>
