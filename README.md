# 🏃‍♂️ WikOS.run — Master Time Protocol

[![Version](https://img.shields.io/badge/version-4.0.0-blue.svg)](https://github.com/wiktorzalewski/WikOS)

Nowoczesny system pomiaru czasu dla lekkoatletów i trenerów. Projekt łączy w sobie precyzję urządzeń pomiarowych (IoT) z intuicyjnym interfejsem webowym do zarządzania wynikami i zawodnikami.

## 🚀 O Projekcie

WikOS (Master Time Protocol) to ekosystem zaprojektowany do precyzyjnego pomiaru sprintów i treningów biegowych. System składa się z fizycznych pachołków laserowych, startera radiowego oraz dedykowanego serwera, który przetwarza i udostępnia wyniki w czasie rzeczywistym.

### Główne Moduły:
- **`web/`**: Publiczna strona informacyjna projektu.
- **`panel/`**: Panel operatora/trenera do zarządzania sesjami treningowymi.
- **`admin/`**: Zaawansowany panel administracyjny i CMS treści.
- **`results/`**: Publiczna tablica wyników Live (widok stadionu).
- **`time/`**: Precyzyjny zegar systemowy MTP (Master Time Protocol).
- **`api/`**: Interfejs komunikacyjny dla urządzeń pomiarowych.

---

## 🛠 Technologie

Projekt został zbudowany przy użyciu sprawdzonych i wydajnych technologii:

- **Backend**: PHP 8.4 (PDO, MySQL/MariaDB)
- **Frontend**: Tailwind CSS 3.x, JavaScript (Vanilla ES6+)
- **Czcionki**: Fira Code (Technical Style), Space Grotesk (Modern UI)
- **Baza danych**: MySQL 8.0 z obsługą JSON i precyzyjnych znaczników czasu.

---

## ✨ Funkcje
- ⏱ **Pomiar Live**: Wyświetlanie wyników natychmiast po przecięciu wiązki lasera.
- 📊 **Archiwum Wyników**: Pełna historia pomiarów z podziałem na dystanse i zawodników.
- 👤 **Profile Zawodników**: Statystyki, rekordy życiowe (PB) i historia startów.
- 🛡 **System OTA**: Możliwość zdalnej aktualizacji firmware'u urządzeń pomiarowych.
- 📱 **Mobile UI**: Cały interfejs (poza panelem administracyjnym) jest w pełni responsywny i zoptymalizowany pod urządzenia mobilne.

---

## 👨‍💻 Autorzy
- **System Logic & Backend**: Wiktor Zalewski & Oskar Kuliński
- **Frontend Architecture**: Wiktor Zalewski

---
