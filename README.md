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
- wsparcie dla LMS > 28.x,

**Instalacja wtyczki:**
1. Przejdź do głównego katalogu LMS.
2. git clone https://github.com/interduo/LMSPurchasesPlugin/ plugins/LMSPurchasesPlugin.
3. composer update --no-dev
4. Uaktywnić wtyczkę z poziomu interfejsu użytkownika LMS.
5. Ustawić zmienne konfiguracyjne wtyczki.
6. Nadaj uprawnienia użytkownikowi do wtyczki.
(opcjonalnie) Dodaj do crontaba skrypt bin/lms-pdf-import-imap-parser.php zaciągający dokumenty kosztowe z maila

**Aktualizacja wtyczki:**
1. cd plugins/LMSPurchasesPlugin; git fetch origin; git pull origin main;

**TODO: (dla wersji 1.0)**
- testy i porządny review kodu przez kilku mądrzejszych,
- obsługa walut,
- wyświetlenie na wskaźnikach ilości dokumentów z krótkim terminem płatności (do 3 dni),
- refaktoring/naprawa modułu dashboard - rozbudowa funkcji GetPurchaseList() o liczenie sum, (Grzegorz)

Jarosław Kłopotek <jkl@interduo.pl>,
Grzegorz Cichowski <gc@ptlanet.pl>
