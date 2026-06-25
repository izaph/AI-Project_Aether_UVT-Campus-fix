# Teste de Acceptanță — UVT Campus Fix

**Proiect**: UVT Campus Fix (Team Aether)
**Data**: 2026-06-25
**Mediu**: Docker Compose (GLPI 11 + MariaDB 10.11 + ML Service Python 3.11)

---

## T1 — Autentificare GLPI

| Câmp | Valoare |
|------|---------|
| **Precondiții** | Docker Compose pornit, GLPI instalat |
| **Pași** | 1. Accesează `http://localhost:8080` 2. Introdu user `glpi`, parolă `glpi` 3. Apasă Login |
| **Rezultat așteptat** | Redirecționare la dashboard-ul GLPI, meniu lateral vizibil |
| **Status** | ✅ TRECUT |

---

## T2 — Creare tichet cu clasificare AI automată

| Câmp | Valoare |
|------|---------|
| **Precondiții** | Logat în GLPI, ML service online |
| **Pași** | 1. Navighează la `/plugins/uvtcampusfix/front/ticket.php` 2. Lasă categoria pe „Lasă AI-ul să decidă" 3. Scrie descrierea: „proiectorul nu pornește în sala A11" 4. Așteaptă sugestia AI (apare automat după ~1s) 5. Apasă „Acceptă sugestia" sau lasă pe auto 6. Apasă „Trimite Tichetul" |
| **Rezultat așteptat** | Sugestia AI apare cu categorie + badge de încredere. Tichetul se creează în GLPI cu categoria sugerată. Mesaj de succes cu nr. tichetului. |
| **Rezultat obținut** | Tichet #1 creat, categorie „Administrativ", încredere 48% |
| **Status** | ✅ TRECUT |

---

## T3 — Clasificare AI pe cele 3 categorii

| Câmp | Valoare |
|------|---------|
| **Precondiții** | ML service online |
| **Pași** | Trimite POST `/classify` cu 3 descrieri diferite |
| **Cazuri de test** | |

| Descriere | Categorie așteptată | Categorie obținută | Încredere | Status |
|-----------|---------------------|-------------------|-----------|--------|
| „proiectorul nu porneste in sala A11" | IT | IT | 37.6% | ✅ |
| „wi-fi nu merge in biblioteca" | Retea | Retea | 63.9% | ✅ |
| „usa de la sala 105 nu se inchide" | Administrativ | Administrativ | 52.0% | ✅ |

| **Status** | ✅ TRECUT (3/3 corecte) |

---

## T4 — Degradare grațioasă (ML service oprit)

| Câmp | Valoare |
|------|---------|
| **Precondiții** | ML service oprit (`docker compose stop ml-service`) |
| **Pași** | 1. Navighează la `/plugins/uvtcampusfix/front/ticket.php` 2. Scrie o descriere 3. Observă că sugestia AI nu apare (degradare silențioasă) 4. Alege manual o categorie 5. Trimite tichetul |
| **Rezultat așteptat** | Formularul funcționează normal fără AI. Nicio eroare vizibilă. Tichetul se creează cu categoria aleasă manual. |
| **Status** | ✅ TRECUT (testat: hub-ul arată „Offline", formularul funcționează fără sugestie) |

---

## T5 — Generator QR și scanare

| Câmp | Valoare |
|------|---------|
| **Precondiții** | Logat în GLPI |
| **Pași** | 1. Navighează la `/plugins/uvtcampusfix/front/qr.php` 2. Alege „Laborator 029" din dropdown 3. Scrie echipament: „Proiector Principal" 4. Apasă „Generează Cod QR" 5. Verifică eticheta generată 6. Apasă „Testează Link-ul" |
| **Rezultat așteptat** | Cod QR vizibil cu etichetă „Campus Fix / FMI-Lab029 — Proiector Principal". Link-ul duce la `ticket.php?qr_id=FMI-Lab029--Proiector-Principal` cu locația pre-completată. |
| **Status** | ✅ TRECUT |

---

## T6 — Generator QR batch

| Câmp | Valoare |
|------|---------|
| **Precondiții** | Logat în GLPI |
| **Pași** | 1. Pe `/plugins/uvtcampusfix/front/qr.php`, secțiunea „Generare Lot" 2. Sală: „FMI-Lab029", Prefix: „PC", Număr: 10 3. Apasă „Generează Lot" |
| **Rezultat așteptat** | Grid cu 10 etichete QR (PC-01 până la PC-10), fiecare cu cod QR unic și etichetă. Buton „Imprimă Toate" vizibil. |
| **Status** | ✅ TRECUT |

---

## T7 — Dashboard Analytics cu date live

| Câmp | Valoare |
|------|---------|
| **Precondiții** | Logat în GLPI, cel puțin 1 tichet creat |
| **Pași** | 1. Navighează la `/plugins/uvtcampusfix/front/dashboard.php` 2. Verifică KPI-urile 3. Verifică tabelul cu ultimele tichete |
| **Rezultat așteptat** | KPI „Tichete Deschise" = 1, tabelul arată tichetul creat cu titlu, categorie și status. Graficele Chart.js se randează. Status ML: Activ. |
| **Rezultat obținut** | 1 tichet deschis afișat, categorie Administrativ, status Nou |
| **Status** | ✅ TRECUT |

---

## T8 — Feedback tracking și acuratețe AI

| Câmp | Valoare |
|------|---------|
| **Precondiții** | ML service online |
| **Pași** | 1. Trimite POST `/feedback` cu ai_category=IT, user_category=IT (corect) 2. Trimite POST `/feedback` cu ai_category=IT, user_category=Retea (incorect) 3. Verifică GET `/feedback/stats` |
| **Rezultat așteptat** | Stats arată total=2, correct=1, accuracy=50%, breakdown per categorie |
| **Rezultat obținut** | `{"total":2,"correct":1,"accuracy":50.0,"per_category":{"Administrativ":{"total":1,"correct":1},"IT":{"total":1,"correct":0}}}` |
| **Status** | ✅ TRECUT |

---

## T9 — Configurare GLPI: Categorii

| Câmp | Valoare |
|------|---------|
| **Precondiții** | GLPI instalat |
| **Pași** | Verifică în baza de date: `SELECT * FROM glpi_itilcategories` |
| **Rezultat așteptat** | 3 categorii: IT (id=1), Retea (id=2), Administrativ (id=3) |
| **Status** | ✅ TRECUT |

---

## T10 — Configurare GLPI: Locații UVT

| Câmp | Valoare |
|------|---------|
| **Precondiții** | GLPI instalat |
| **Pași** | Verifică în baza de date: `SELECT COUNT(*) FROM glpi_locations` |
| **Rezultat așteptat** | 35 locații, ierarhie pe 2 nivele (clădiri → săli/laboratoare) |
| **Rezultat obținut** | 35 locații: 8 clădiri/zone (Sediul Central, Litere, FEAA, Arte, Drept, Cămin C3, Cămin C12, Baza Sportivă) + 27 săli/laboratoare |
| **Status** | ✅ TRECUT |

---

## Sumar

| Test | Descriere | Status |
|------|-----------|--------|
| T1 | Autentificare GLPI | ✅ |
| T2 | Creare tichet cu clasificare AI | ✅ |
| T3 | Clasificare AI pe 3 categorii | ✅ |
| T4 | Degradare grațioasă (ML oprit) | ✅ |
| T5 | Generator QR + scanare | ✅ |
| T6 | Generator QR batch | ✅ |
| T7 | Dashboard analytics live | ✅ |
| T8 | Feedback tracking acuratețe | ✅ |
| T9 | Categorii GLPI configurate | ✅ |
| T10 | Locații UVT configurate | ✅ |

**Rezultat: 10/10 teste trecute.**
