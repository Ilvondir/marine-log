# MarineLog

Aplikacja webowa dla nurków-amatorów do dokumentowania obserwacji fauny morskiej i słodkowodnej. Użytkownik może rejestrować spotkania z gatunkami wodnymi wraz ze zdjęciami, filmami i danymi o warunkach obserwacji (głębokość, temperatura wody, pogoda). Obserwacje są publicznie dostępne bez logowania. Administratorzy mogą moderować treści i zarządzać kontami użytkowników.

Szczegółowy opis produktu, wymagania i zakres MVP znajdziesz w [`context/foundation/prd.md`](context/foundation/prd.md).

---

## Wymagania

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (lub Docker Engine na Linuxie)
- [Composer](https://getcomposer.org/) — tylko do pierwszej instalacji zależności przed uruchomieniem Sail
- Node.js nie jest wymagany lokalnie — Vite działa przez Sail

---

## Uruchomienie lokalne

```bash
# 1. Sklonuj repozytorium i wejdź do katalogu
git clone https://github.com/Ilvondir/marine-log.git marinelog
cd marinelog

# 2. Skopiuj plik środowiskowy
cp .env.example .env

# 3. Zainstaluj zależności PHP (jednorazowo, przed startem Sail)
composer install --no-scripts

# 4. Uruchom kontenery (Laravel, MySQL, Redis)
./vendor/bin/sail up -d

# 5. Wygeneruj klucz aplikacji
./vendor/bin/sail artisan key:generate

# 6. Uruchom migracje
./vendor/bin/sail artisan migrate

# 7. Utwórz symlink do storage (wymagany do wyświetlania mediów)
./vendor/bin/sail artisan storage:link

# 8. Uruchom serwer Vite (frontend assets)
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

Aplikacja będzie dostępna pod adresem **http://localhost**.

---

## Domyślne konta (Seedery)

Aby zasilić bazę danymi początkowymi (w tym rolami i domyślnym kontem administratora), uruchom:

```bash
./vendor/bin/sail artisan db:seed
```

Domyślne dane logowania utworzone przez seeder:

| Rola | Email | Hasło |
|---|---|---|
| **Administrator** | `admin@marinelog.test` | `password` |
| **Użytkownik** | `user@marinelog.test` | `password` |

---

## Uruchamianie testów

```bash
# Cały zestaw testów (Unit + Feature)
./vendor/bin/sail composer test

# Pojedyncza klasa lub metoda
./vendor/bin/sail artisan test --filter=PublishObservationTest

# Sprawdzenie stylu kodu (Pint)
./vendor/bin/sail pint --test

# Automatyczna naprawa stylu
./vendor/bin/sail pint
```

Strategia testów, mapa ryzyk i wzorce dla nowych testów opisane są w [`context/foundation/test-plan.md`](context/foundation/test-plan.md).

---

## Najważniejsze komendy

| Komenda | Opis |
|---|---|
| `./vendor/bin/sail up -d` | Uruchamia kontenery w tle |
| `./vendor/bin/sail down` | Zatrzymuje kontenery |
| `./vendor/bin/sail artisan migrate` | Aplikuje migracje bazy danych |
| `./vendor/bin/sail artisan migrate:fresh --seed` | Reset bazy z danymi testowymi |
| `./vendor/bin/sail artisan storage:link` | Tworzy symlink `public/storage` |
| `./vendor/bin/sail npm run dev` | Uruchamia Vite w trybie watch |
| `./vendor/bin/sail npm run build` | Buduje produkcyjne assety |
| `./vendor/bin/sail composer test` | Uruchamia testy PHPUnit |
| `./vendor/bin/sail pint` | Formatuje kod (Laravel Pint) |

---

## Struktura projektu

```
app/
  Http/Controllers/   # Cienkie kontrolery — walidacja, wywołanie serwisu, odpowiedź
  Services/           # Logika biznesowa (ObservationService, AdminService, FavoriteService…)
  Contracts/          # Interfejsy repozytoriów
  Repositories/       # Implementacje Eloquent
  Policies/           # Kontrola dostępu na poziomie modelu (ObservationPolicy)
  Models/             # Modele Eloquent
context/
  foundation/         # PRD, plan testów, stack, roadmapa — źródło decyzji produktowych
  archive/            # Historia zmian z planami i przeglądami implementacji
database/
  migrations/         # Schemat bazy danych
tests/
  Unit/               # Testy jednostkowe serwisów (mocki repozytoriów)
  Feature/            # Testy HTTP end-to-end z prawdziwą bazą
  e2e/                # Testy Playwright (IDOR, przepływ przeglądarki)
```

---

## Stos technologiczny

- **Backend:** Laravel 13, PHP 8.3
- **Baza danych:** MySQL 8 (przez Sail)
- **Cache / kolejki:** Redis
- **Frontend:** Blade + Vite
- **Środowisko dev:** Laravel Sail (Docker)
- **CI:** GitHub Actions
- **Deployment:** Hetzner + Coolify

Pełne uzasadnienie wyboru stosu: [`context/foundation/tech-stack.md`](context/foundation/tech-stack.md).
