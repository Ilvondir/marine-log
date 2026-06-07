---
project: "MarineLog"
context_type: greenfield
created: 2026-06-07
updated: 2026-06-07
product_type: web-app
target_scale:
  users: medium
  qps: low
  data_volume: small
timeline_budget:
  mvp_weeks: 3
  hard_deadline: null
  after_hours_only: true
checkpoint:
  current_phase: 8
  phases_completed: [1, 2, 3, 4, 5, 6, 7]
  gray_areas_resolved:
    - topic: "context type"
      decision: "Greenfield — a new system built from scratch."
    - topic: "primary persona"
      decision: "Nurek-amator dokumentujący obserwacje; biolog lub badacz jako persona drugorzędna."
    - topic: "fauna scope"
      decision: "Obserwacje zwierząt morskich i słodkowodnych."
    - topic: "product differentiation"
      decision: "Całościowa specjalizacja w obserwacjach podwodnych; ocena rzadkości jest funkcją, a nie głównym wyróżnikiem."
    - topic: "authentication"
      decision: "Dodawanie obserwacji wymaga konta z logowaniem przez e-mail i hasło; przeglądanie obserwacji jest publiczne."
    - topic: "roles"
      decision: "Zwykły użytkownik edytuje i usuwa własne obserwacje; administrator moderuje lub usuwa wszystkie obserwacje oraz przegląda i blokuje konta."
    - topic: "MVP flow"
      decision: "Publiczne przeglądanie obserwacji oraz publikowanie przez zalogowanego użytkownika wpisu z wymaganymi danymi i co najmniej jednym zdjęciem."
    - topic: "MVP timeline"
      decision: "Zakres jest możliwy do wykonania w 3 tygodnie pracy po godzinach."
    - topic: "observation fields"
      decision: "Obowiązkowe: gatunek, data i godzina, lokalizacja i co najmniej jedno zdjęcie. Opcjonalne: opis, filmy, temperatura wody, przybliżona głębokość i pogoda."
    - topic: "secondary success criterion"
      decision: "Strona główna prezentuje zwierzę dnia."
    - topic: "MVP guardrails"
      decision: "Prywatność i bezpieczeństwo kont, bezpieczne wyświetlanie przesłanych mediów oraz szybkie ładowanie publicznych obserwacji."
    - topic: "functional requirement priorities"
      decision: "FR-001–FR-006 są must-have; zwierzę dnia (FR-007) jest nice-to-have."
    - topic: "observation publication"
      decision: "Kompletna obserwacja jest publikowana natychmiast; niepełny formularz jest blokowany. Wcześniejsza moderacja i zgłaszanie treści są poza MVP."
    - topic: "domain rule scope"
      decision: "MVP pozostaje rejestrem obserwacji bez automatycznej klasyfikacji; docelowo MarineLog ma oceniać rzadkość gatunku na podstawie danych IUCN."
    - topic: "non-functional requirements"
      decision: "Przyjęto cel ładowania do 2 sekund, kontrolę typów mediów, ochronę własności wpisów oraz niepubliczność haseł i prywatnych danych kont."
    - topic: "product framing"
      decision: "Aplikacja webowa dla dziesiątek do około stu użytkowników; 3 tygodnie pracy po godzinach; brak twardego terminu."
    - topic: "MVP non-goals"
      decision: "Poza MVP pozostają: ocena rzadkości IUCN, alerty migracyjne i analiza anomalii pogodowych, feed wiadomości, zgłaszanie obserwacji oraz osobna aplikacja mobilna."
  frs_drafted: 7
  quality_check_status: accepted
---

## Initial Idea

Chcę stworzyć nowy systemik o nazwie MarineLog, w którym będzie można wrzucać swoje spotkania z morskimi zwierzętami- zdjęcia lub filmy, opsi, lokalizacja, temperatura wody- te wpisy będą ogólnodostępne dla innychużytkowników bez logowania tu masz krótkie podsumowanie

MarineLog – Inteligentny rejestrator obserwacji fauny morskiejCoś dla pasjonatów whale watchingu, nurków i biologów. Zamiast pisać w notesie, rejestrują spotkania z rekinami, wielorybami czy żółwiami.Użytkownik: Nurek-amator, pasjonat biologii morskiej.  Dane (Sensowny CRUD): Wpisy z obserwacji (gatunek, koordynaty GPS lub region, głębokość spotkania, warunki pogodowe).  Logika biznesowa (Jedno zdanie): System automatycznie kategoryzuje stopień rzadkości zaobserwowanego stworzenia na podstawie Czerwonej Księgi Gatunków Zagrożonych (IUCN) i generuje alert migracyjny dla danego regionu, jeśli wykryje powtarzające się anomalie pogodowe.  Dlaczego to jest dobre: Prosty interfejs formularza, a jako logikę biznesową (AI lub sztywne reguły) możesz podpiąć prosty mechanizm mapowania gatunków i analizy regułowej.

## Vision & Problem Statement

MarineLog odpowiada na potrzeby nurków-amatorów, którzy po spotkaniu ze zwierzęciem wodnym chcą udokumentować obserwację wraz ze specjalistycznymi danymi podwodnymi, takimi jak głębokość. Obecnie mogą korzystać z bezpłatnych, ogólnych platform, takich jak iNaturalist, które nie są wyspecjalizowane w obserwacjach podwodnych.

MarineLog koncentruje cały produkt na dokumentowaniu fauny morskiej i słodkowodnej. Określanie rzadkości na podstawie danych IUCN jest planowaną funkcją, ale nie stanowi głównego wyróżnika produktu.

## User & Persona

### Primary persona

Nurek-amator zainteresowany fauną wodną, który po nurkowaniu rejestruje spotkanie ze zwierzęciem wraz z materiałem i danymi dotyczącymi warunków obserwacji.

### Secondary persona

Biolog lub badacz korzystający z publicznie dostępnych obserwacji i związanych z nimi danych.

## Access Control

Obserwacje są ogólnodostępne i można je przeglądać bez logowania.

Zarejestrowany użytkownik loguje się za pomocą adresu e-mail i hasła. Może dodawać obserwacje oraz zarządzać własnymi wpisami.

Administrator może moderować lub usuwać wszystkie obserwacje oraz zarządzać kontami użytkowników.

## Success Criteria

### Primary

- Niezalogowana osoba może przeglądać publiczne obserwacje.
- Zalogowany użytkownik może utworzyć i opublikować obserwację, podając gatunek, datę i godzinę, lokalizację oraz co najmniej jedno zdjęcie. Może opcjonalnie dodać opis, filmy, temperaturę wody, przybliżoną głębokość i pogodę.
- Po publikacji obserwacja jest widoczna publicznie.

### Secondary

- Strona główna prezentuje zwierzę dnia.

### Guardrails

- Dane kont i dostęp do funkcji wymagających zalogowania nie mogą zostać ujawnione osobom nieuprawnionym.
- Zdjęcia i filmy przesłane przez użytkowników muszą być bezpiecznie wyświetlane osobom przeglądającym obserwacje.
- Publiczne obserwacje muszą ładować się wystarczająco szybko, aby ich przeglądanie pozostawało wygodne.

## User Stories

### US-01: Publikacja obserwacji

- **Given** zalogowanego użytkownika znajdującego się w formularzu nowej obserwacji
- **When** użytkownik uzupełni wszystkie wymagane pola, doda co najmniej jedno zdjęcie i wybierze publikację
- **Then** obserwacja zostaje natychmiast opublikowana i staje się dostępna publicznie

#### Acceptance Criteria

- Formularz wymaga gatunku, daty i godziny, lokalizacji oraz co najmniej jednego zdjęcia.
- Opis, filmy, temperatura wody, przybliżona głębokość i pogoda są opcjonalne.
- Brak któregokolwiek wymaganego pola blokuje publikację.
- Poprawnie opublikowana obserwacja jest od razu widoczna publicznie.
- MVP nie wymaga zatwierdzenia obserwacji przed publikacją i nie udostępnia funkcji zgłaszania treści.

## Functional Requirements

### Publiczne obserwacje

- FR-001: Gość może przeglądać publiczne obserwacje. Priority: must-have
  > Socrates: Rozważono ryzyko rozpowszechniania błędnych oznaczeń gatunków. FR zachowano; w MVP obserwacje są prezentowane jako treści użytkowników bez sugerowania, że zostały naukowo zweryfikowane.

### Konto użytkownika

- FR-002: Osoba może utworzyć konto i zalogować się za pomocą adresu e-mail i hasła. Priority: must-have
  > Socrates: Nie wskazano kontrargumentu; wymaganie pozostaje bez zmian.

### Zarządzanie obserwacjami

- FR-003: Zalogowany użytkownik może opublikować obserwację zawierającą gatunek, datę i godzinę, lokalizację oraz co najmniej jedno zdjęcie, a opcjonalnie opis, filmy, temperaturę wody, przybliżoną głębokość i pogodę. Priority: must-have
  > Socrates: Rozważono, że wymaganie temperatury, pogody i głębokości blokowałoby wartościowe obserwacje, gdy użytkownik nie zna tych danych. Rozwiązanie: pola te zmieniono na opcjonalne.
- FR-004: Zalogowany użytkownik może edytować i usuwać własne obserwacje. Priority: must-have
  > Socrates: Rozważono, że edycja danych po publikacji może wpływać na wiarygodność wpisu. FR zachowano; autor może poprawiać i usuwać własne obserwacje.

### Administracja

- FR-005: Administrator może moderować lub usuwać wszystkie obserwacje. Priority: must-have
  > Socrates: Nie wskazano kontrargumentu; wymaganie pozostaje bez zmian.
- FR-006: Administrator może przeglądać i blokować konta użytkowników. Priority: must-have
  > Socrates: Rozważono, że ogólne zarządzanie kontami nadmiernie rozszerza zakres MVP. Rozwiązanie: ograniczono wymaganie do przeglądania i blokowania kont.

### Treść dodatkowa

- FR-007: Odwiedzający może zobaczyć zwierzę dnia na stronie głównej. Priority: nice-to-have
  > Socrates: Nie wskazano kontrargumentu; wymaganie pozostaje jako nice-to-have.

## Business Logic

MVP nie podejmuje automatycznej decyzji domenowej; docelowo MarineLog będzie określać rzadkość zaobserwowanego gatunku na podstawie danych IUCN.

Ocena rzadkości pozostaje poza zakresem MVP i nie jest wymagana do publikowania ani przeglądania obserwacji.

## Non-Functional Requirements

- Publiczne obserwacje są wyświetlane użytkownikowi w ciągu maksymalnie 2 sekund.
- Pliki w niedozwolonym formacie nie mogą zostać opublikowane jako zdjęcia lub filmy.
- Użytkownik nie może edytować ani usuwać obserwacji należącej do innej osoby.
- Hasła i prywatne dane kont użytkowników nie są publicznie widoczne.

## Non-Goals

- MVP nie ocenia rzadkości gatunków na podstawie danych IUCN, ponieważ ta reguła domenowa pozostaje kierunkiem rozwoju po pierwszej wersji.
- MVP nie generuje alertów migracyjnych ani nie analizuje anomalii pogodowych, ponieważ funkcje analityczne wykraczają poza podstawowy przepływ rejestrowania obserwacji.
- MVP nie agreguje feedów wiadomości z innych stron, ponieważ nie jest to konieczne do publikowania i przeglądania obserwacji.
- MVP nie udostępnia użytkownikom mechanizmu zgłaszania obserwacji, ponieważ moderacja reaktywna zostaje odłożona na późniejszy etap.
- MVP nie obejmuje osobnej aplikacji mobilnej; pierwszą powierzchnią produktu jest aplikacja webowa.

## Open Questions

Brak otwartych pytań blokujących przygotowanie PRD.

## Quality cross-check

- Kontrola dostępu: obecna.
- Logika biznesowa: obecna; MVP świadomie nie podejmuje automatycznej decyzji domenowej, a reguła IUCN pozostaje poza jego zakresem.
- Artefakty projektu: obecne.
- Potwierdzony koszt czasowy: obecny; 3 tygodnie pracy po godzinach.
- Non-Goals: obecne.
- Zachowane zachowanie: nie dotyczy projektu greenfield.
