# LMSPurchasePlugin 
Do czego służy wtyczka:
1. Ewidencja wydatków - szybki dostęp do dokumentów kosztowych i ich skanów.
2. Export niezapłaconych danych faktur w formie CSV do wrzucenia do banku.
3. Pilnowanie terminów płatności dokumentów.
4. Statystykowanie wydatków firmy z uwzględnieniem podziału na projekty/kategorie wydatków/stan płatności.
5. Korzystanie z białej listy podatników pomaga dodatkowo zabezpieczyć się przed utratą miedziaków.
6. Export dokumentów kosztowych do systemu księgowego.
7. Automatyczne zaciąganie dokumentów wydatkowych z maila np. faktury@domena

Wtyczka nie jest i nie będzie substytutem systemu księgowego ani magazynu.

**UWAGA:**
- wsparcie tylko dla PostgreSQL,
- wsparcie dla LMS > 28.x (obecna gałąź master),
- żeby wszystko grało wtyczka wymaga dogrania do mastera:
https://github.com/chilek/lms/pull/2265
https://github.com/chilek/lms/pull/2300

**Instalacja wtyczki:**
1. Przejdź do głównego katalogu LMS.
2. `git clone https://github.com/interduo/LMSPurchasesPlugin/ plugins/LMSPurchasesPlugin`.
3. `composer update --no-dev`
4. Uaktywnić wtyczkę z poziomu interfejsu użytkownika LMS.
5. Ustawić zmienne konfiguracyjne wtyczki.
6. Utworzyć katalog zdefiniowany w pd.storage_dir i nadać mu uprawnienia (jeśli katalog storage_dir jest w katalogu aplikacji LMS należy dodać go do .gitignore) 

np.
```
mkdir -p storage/pd/anteroom;
chown -R 33:33 storage/pd/anteroom;
chmod -R 750 storage/pd/anteroom;
```
8. Nadaj uprawnienia użytkownikom do wtyczki.
9. (opcjonalnie) Dodaj do crontaba skrypt bin/lms-pdf-import-imap-parser.php zaciągający dokumenty kosztowe z maila

**Aktualizacja wtyczki:**
1. `cd plugins/LMSPurchasesPlugin; git fetch origin; git pull origin main;`

**TODO: (dla wersji 1.0)**
- testy i porządny review kodu przez kilku mądrzejszych,
- usunięci klienci / zmiana danych nie powinna generować zmian dokonanych płatności,

**TODO v2:**
- wiązanie wrzucanych proform z dokumentami finansowymi,
- wyświetlenie na wskaźnikach ilości dokumentów z krótkim terminem płatności (do 3 dni),
- płatności cykliczne,
- automatyczne zaczytywanie faktur od kontrachentów, którzy udostępniają je w XML,
- refaktoring/naprawa modułu dashboard - rozbudowa funkcji GetPurchaseList() o liczenie sum, (Grzegorz)
- import potwierdzeń płatności wykonanych w banku,
- przekazywanie dokumentu na magazyn,
- pozwól opłatami stałymi z core LMS dodawać wydatki do wtyczki,
- parser rozliczający wpłaty z importu bankowego,

Jarosław Kłopotek <jkl@interduo.pl>,

Podziękowania dla:
  Grzegorz Cichowski - PTLANET,
  Damian Kieliszek - PROIP,
  Rafał Wójcik - AWBNET,
