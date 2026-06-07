---
project: "MarineLog"
version: 1
status: draft
created: 2026-06-07
context_type: greenfield
product_type: web-app
target_scale:
  users: medium
  qps: low
  data_volume: small
timeline_budget:
  mvp_weeks: 3
  hard_deadline: null
  after_hours_only: true
---

## Vision & Problem Statement

MarineLog odpowiada na potrzeby nurków-amatorów, którzy po spotkaniu ze zwierzęciem wodnym chcą udokumentować obserwację wraz ze specjalistycznymi danymi podwodnymi, takimi jak głębokość. Obecnie mogą korzystać z bezpłatnych, ogólnych platform, takich jak iNaturalist, które nie są wyspecjalizowane w obserwacjach podwodnych.

MarineLog koncentruje cały produkt na dokumentowaniu fauny morskiej i słodkowodnej. Określanie rzadkości na podstawie danych IUCN jest planowaną funkcją, ale nie stanowi głównego wyróżnika produktu.

## User & Persona

### Primary persona

Nurek-amator zainteresowany fauną wodną, który po nurkowaniu rejestruje spotkanie ze zwierzęciem wraz z materiałem i danymi dotyczącymi warunków obserwacji.

### Secondary persona

Biolog lub badacz korzystający z publicznie dostępnych obserwacji i związanych z nimi danych.

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

## Non-Functional Requirements

- Publiczne obserwacje są wyświetlane użytkownikowi w ciągu maksymalnie 2 sekund.
- Pliki w niedozwolonym formacie nie mogą zostać opublikowane jako zdjęcia lub filmy.
- Użytkownik nie może edytować ani usuwać obserwacji należącej do innej osoby.
- Hasła i prywatne dane kont użytkowników nie są publicznie widoczne.

## Business Logic

# TODO: domain rule — see Open Questions

MVP nie podejmuje automatycznej decyzji domenowej. Docelowo MarineLog będzie określać rzadkość zaobserwowanego gatunku na podstawie danych IUCN, ale funkcja ta pozostaje poza zakresem MVP.

## Access Control

Obserwacje są ogólnodostępne i można je przeglądać bez logowania.

Zarejestrowany użytkownik loguje się za pomocą adresu e-mail i hasła. Może dodawać obserwacje oraz edytować i usuwać własne wpisy.

Administrator może moderować lub usuwać wszystkie obserwacje oraz przeglądać i blokować konta użytkowników.

## Non-Goals

- MVP nie ocenia rzadkości gatunków na podstawie danych IUCN, ponieważ ta reguła domenowa pozostaje kierunkiem rozwoju po pierwszej wersji.
- MVP nie generuje alertów migracyjnych ani nie analizuje anomalii pogodowych, ponieważ funkcje analityczne wykraczają poza podstawowy przepływ rejestrowania obserwacji.
- MVP nie agreguje feedów wiadomości z innych stron, ponieważ nie jest to konieczne do publikowania i przeglądania obserwacji.
- MVP nie udostępnia użytkownikom mechanizmu zgłaszania obserwacji, ponieważ moderacja reaktywna zostaje odłożona na późniejszy etap.
- MVP nie obejmuje osobnej aplikacji mobilnej; pierwszą powierzchnią produktu jest aplikacja webowa.

## Open Questions

1. **Jaka jest jednozdaniowa reguła biznesowa działająca już w MVP?** — Do ustalenia przez użytkownika. Blokuje pełne domknięcie PRD: tak, ponieważ obecny MVP jest rejestrem CRUD bez decyzji domenowej.
