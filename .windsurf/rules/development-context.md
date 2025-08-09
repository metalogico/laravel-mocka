---
trigger: always_on
---

# Laravel Mocka – Development Rules & Context

## Obiettivo
Laravel Mocka è un package per Laravel che fornisce risposte mock a richieste HTTP in modo selettivo, senza alterare il traffico reale per gli altri utenti.  
Non sostituisce globalmente `Http`, ma introduce **`MockaHttp`** come Facade alternativa con **parità API completa** rispetto a `Http`.

## Visione d'uso
- Lo sviluppatore sceglie esplicitamente se usare `Http::...` (vero) o `MockaHttp::...` (mockabile).
- Può attivare mock **per utente, rotta, header, query, env, o flag di processo** (anche in queue/job artisan).
- Nessun rewrite dell'app: basta cambiare la Facade nei punti interessati.
- Massima **DX**: stessa API fluente, stesso comportamento di ritorno (`Illuminate\Http\Client\Response`), compatibilità con `pool()`, streaming, sink, ecc.

## Architettura tecnica
1. **Facade** → `MockaHttp` registra nel container `mocka.http`.
2. **Service Provider** → registra binding singleton `mocka.http` verso `MockaFactory`.
3. **MockaFactory** → estende `Illuminate\Http\Client\Factory`, override di `buildHandlerStack()` per inserire `MockaMiddleware` nello stack Guzzle.
4. **MockaMiddleware** → decide se mockare o passare la request al vero handler.
5. **MockaMatcher** → gestisce matching URL con priorità (exact > wildcard > regex) e opzioni extra (query, headers, host whitelist).
6. **MockaResponder** → costruisce `Psr7\Response` o `Illuminate\Http\Client\Response` dal mock (rispettando sink, stream, delay, error simulation, ecc.).
7. **Config `mocka.php`** → contiene:
   - `enabled`, `hard_disable`, `logs`
   - utenti abilitati
   - path mock files
   - default delay
   - mappings con file, chiave, match type, errori, delay
8. **Mock files** → PHP array che supporta static, dynamic, hybrid responses e faker integration.

## Requisiti funzionali v1.0
- **Mock alternativi** attivabili via:
  - Utente (email)
  - Header (`X-Mocka`)
  - Query (`?mocka=1`)
  - Env flag (`MOCKA_FORCE=1`)
  - withOptions(['mocka' => true]) per queue/artisan
- **Queue / Artisan**: mock funzionante anche senza sessione utente.
- **Matching & Priorità**: exact, wildcard, regex, ordine chiaro.
- **Parità API con Http**: tutti i metodi fluenti, `pool()`, streaming, sink, retry, timeout, ecc.
- **Sicurezza**:
  - Hard kill switch (`MOCKA_HARD_DISABLE`)
  - Whitelist host
  - Logging redatto
  - Warning in prod se enabled
- **Error Simulation**: errori statici o dinamici con `error_rate`.
- **Rate Limiting Simulation**: delay ms.

## Regole di implementazione
- **Non modificare Http globale**.
- Usare **decorazione a livello di Guzzle HandlerStack** per mock vs real.
- Restituire sempre un `Illuminate\Http\Client\Response` coerente.
- Rispetto rigoroso di DX: niente API custom per l'utente finale se non necessario.
- Config chiara e leggibile.
- Artisan commands minimi v1.0:
  - `mocka:validate` (validazione config/mocks)
  - `mocka:list` (lista mappings attivi)
- Possibile futura estensione: `mocka:record` per registrare risposte reali.

## Processo di mock
1. Attivazione: controlla header/query/env/utente.
2. Matching URL secondo priorità.
3. Caricamento file mock.
4. Costruzione risposta (static/dynamic/hybrid).
5. Applicazione delay/errore se configurato.
6. Logging (se attivo).
7. Restituzione `Response`.

## Fonti di verità
- Tutti gli esempi e le feature documentati nel README ufficiale ([vedi sezione Features, Advanced Features, Config, How It Works]).
- La logica di matching, error simulation e templating deve essere coerente con gli esempi del README.

---
