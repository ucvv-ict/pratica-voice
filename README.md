# PraticaVoice

PraticaVoice è un software sviluppato per la gestione e la consultazione
digitale di pratiche edilizie e documentazione tecnica, con funzionalità
di ricerca avanzata, indicizzazione dei contenuti e supporto vocale.

Questo repository contiene il codice sorgente attualmente in fase di
valutazione per la definizione della titolarità dei diritti patrimoniali
e della futura modalità di rilascio.

## Stato giuridico del software

Il software è attualmente di titolarità dell’Unione di Comuni Valdarno e
Valdisieve, ai sensi dell’art. 12-bis della Legge 22 aprile 1941 n. 633
e dell’art. 69 del Codice dell’Amministrazione Digitale (D.Lgs. 82/2005).

Fino a nuova deliberazione dell’Ente, il software è pubblicato esclusivamente
ai fini di consultazione, valutazione tecnica e collaborazione interna.

Il file `LICENSE` contiene la licenza provvisoria di utilizzo attualmente
applicata al progetto.

## Rilascio futuro

Il progetto potrà essere reso disponibile in:
- **riuso verso altre Pubbliche Amministrazioni** (conforme all’art. 69 CAD),
- **licenza open-source** (ad es. EUPL, MIT, Apache 2.0, AGPL),

previa deliberazione formale da parte dell’Ente.

Fino ad allora, non è autorizzato l’utilizzo operativo, commerciale, la
redistribuzione o la fornitura di servizi a terzi basati sul software.

## Autori

Lo sviluppo del software è stato avviato e curato da personale interno;
i diritti morali dell’autore restano in capo allo sviluppatore originario,
ai sensi della normativa vigente.

## Contatti istituzionali

Unione di Comuni Valdarno e Valdisieve  
Sede legale: Via XXV Aprile, 10 - 50068 Rufina (FI)
Sito web: https://www.uc-valdarnoevaldisieve.fi.it/
Email istituzionale:  segreteria@ucvv.it

# ⚙️ Installazione e note tecniche importanti

## 📁 Directory PDF esterne (OBBLIGATORIO)
- I PDF **non** sono salvati nello storage Laravel: sono conservati in una directory esterna (NAS / disco / share).
- La directory deve essere montata a livello di sistema (es. `/etc/fstab`):
  - `//NAS/PDF /mnt/praticavoice-pdf cifs username=XXX,password=YYY,iocharset=utf8,uid=www-data,gid=www-data 0 0`
  - `UUID=xxxx-xxxx /mnt/praticavoice-pdf ext4 defaults 0 2`
- Variabile `.env` obbligatoria:
  - `PDF_BASE_PATH=/mnt/praticavoice-pdf`

## 🧵 Queue worker (OBBLIGATORIO)
- La generazione dei fascicoli PDF avviene tramite Job in background: **senza worker attivo il fascicolo non viene generato**.
- Comando worker:
  - `php artisan queue:work`
- Variabile `.env`:
  - `QUEUE_CONNECTION=database`
- Migrazioni necessarie:
  - `php artisan queue:table`
  - `php artisan migrate`
- In produzione serve un worker persistente (es. `systemd` o `supervisor`).

## 🗂️ Directory temporanea ZIP
- I fascicoli ZIP vengono generati in `storage/app/tmp`: la directory deve esistere ed essere scrivibile.
- Comandi utili:
  - `mkdir -p storage/app/tmp`
  - `chown -R www-data:www-data storage bootstrap/cache`
  - `chmod -R 775 storage bootstrap/cache`

## 🔐 Permessi filesystem
- Laravel deve poter scrivere in:
  - `storage/`
  - `bootstrap/cache/`
  - directory PDF esterna (almeno in lettura).

## 🌐 Variabile APP_URL
- Un valore errato di `APP_URL` può causare errori 419 Page Expired, problemi CSRF e preview PDF non funzionanti.
- Esempio:
  - `APP_URL=https://praticavoice.dominio.it`

## 🚀 Deploy on-prem per nuovo Comune (multi-tenant)
- Copia codice e dipendenze: `composer install --no-dev --optimize-autoloader`.
- Imposta il `.env`:
  - `PRATICAVOICE_MODE=onprem`
  - `TENANT_SLUG=<slug-comune>` (es. `pelago`)
  - `TENANT_NAME="<Nome Comune>"`
  - `TENANT_PDF_DIR=PDF` (o il nome reale della cartella PDF)
  - `PDF_BASE_PATH=/path/reale/ai/pdf/<slug>/<TENANT_PDF_DIR>` (es. `/mnt/praticavoice-pdf/pelago/PDF`)
  - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://host`
  - Configura DB/QUEUE/MAIL/OPENAI/R2 secondo ambiente.
- Prepara storage/link: assicurati che `PDF_BASE_PATH` esista e che `php artisan storage:link` punti a `public/storage`.
- Cache/config: `php artisan config:clear && php artisan cache:clear` al primo avvio; facoltativo `php artisan config:cache route:cache view:cache` a fine deploy.
- Migrazioni (se DB nuovo): `php artisan migrate --force`.
- Worker code: avvia `php artisan queue:work --daemon` (via supervisor/systemd) per fascicoli/indicizzazioni.
- Permessi: l’utente PHP deve leggere i PDF e scrivere in `storage/` e `bootstrap/cache/`.
- Verifica rapida: apri `/dashboard` (badge tenant), apri una pratica e controlla link PDF; se necessario lancia `php artisan pdf:index --no-interaction`.

### Permessi filesystem in ambienti on-prem
In ambienti on-prem con filesystem montati (CIFS, bind mount, NFS, ecc.), lo script di deploy non forza il cambio ownership delle directory `storage/` e `bootstrap/cache`. I permessi corretti devono essere impostati in fase di prima installazione. Durante i deploy successivi, i permessi vengono applicati in modalità best-effort senza interrompere il processo.

### Nota su Git safe.directory (on-prem)
- Git >= 2.35 richiede di marcare il repo come sicuro in ambienti server multi-utente:
  - `sudo git config --system --add safe.directory /var/www/praticavoice`

## 🧠 Note su prestazioni e UI
- Le anteprime PDF sono generate lato client con PDF.js; su pratiche grandi il caricamento può essere lento.
- Il comportamento è mitigato da:
  - progress bar
  - caricamento progressivo
  - limiti di concorrenza
- Il pulsante di salvataggio è disabilitato finché il caricamento non è completo.

## 🌗 Tema chiaro / scuro
- Tema gestito lato frontend; preferenza salvata nel `localStorage`.
- Nessuna configurazione backend necessaria.

## 🧩 Componenti Blade personalizzati
- Il progetto utilizza componenti Blade custom, tra cui:
  - `<x-back-button>`
- Assicurarsi che la directory `resources/views/components` sia presente in ogni installazione.
