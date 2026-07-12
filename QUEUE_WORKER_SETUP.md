# Queue Worker Setup

Panduan ini dipakai untuk menjalankan queue worker yang aman untuk notifikasi email dan WhatsApp lintas plugin:

- `form-transfer`
- `exit-clearance`
- `rekrutmen`

Queue dibagi menjadi tiga:

- `default` untuk job umum
- `notifications` untuk email notification
- `whatsapp` untuk WhatsApp notification

## Rekomendasi Worker

Gunakan jumlah process berikut:

- `default`: `numprocs=2`
- `notifications`: `numprocs=1`
- `whatsapp`: `numprocs=1`

Alasan:

- queue `default` boleh lebih longgar karena tidak semua job adalah outbound notification
- queue `notifications` sengaja dibatasi agar email tidak blast
- queue `whatsapp` sengaja dibatasi agar pengiriman ke provider tidak terlalu agresif

## `.env`

Gunakan konfigurasi berikut:

```env
QUEUE_CONNECTION=database
CACHE_STORE=database

FORM_TRANSFER_NOTIFICATION_QUEUE=notifications
EXIT_CLEARANCE_NOTIFICATION_QUEUE=notifications
REKRUTMEN_NOTIFICATION_QUEUE=notifications

NOTIFICATION_MAIL_THROTTLE_ENABLED=true
NOTIFICATION_MAIL_THROTTLE_MIN_INTERVAL=2
NOTIFICATION_MAIL_THROTTLE_MAX_INTERVAL=5
NOTIFICATION_MAIL_THROTTLE_KEY=global

WHATSAPP_ENABLED=true

# WAHA primary, Fonnte fallback
WHATSAPP_GATEWAY_PROVIDER=waha
WHATSAPP_GATEWAY_FALLBACK_PROVIDER=fonnte
WHATSAPP_GATEWAY_FALLBACK_ENABLED=true
WHATSAPP_GATEWAY_WAHA_BASE_URL=http://localhost:3000
WHATSAPP_GATEWAY_WAHA_API_KEY=your-waha-api-key
WHATSAPP_GATEWAY_WAHA_SESSION=default
WHATSAPP_GATEWAY_FONNTE_ENDPOINT=https://api.fonnte.com/send
WHATSAPP_GATEWAY_FONNTE_TOKEN=your-fonnte-token
WHATSAPP_GATEWAY_COUNTRY_CODE=62
WHATSAPP_GATEWAY_TIMEOUT=15
WHATSAPP_GATEWAY_MIN_SECONDS_BETWEEN_SENDS=3
WHATSAPP_QUEUE=whatsapp
WHATSAPP_CONNECTION=null
WHATSAPP_TIMEOUT=10
WHATSAPP_TRIES=3
WHATSAPP_BACKOFF=10,30,60
WHATSAPP_THROTTLE_ENABLED=true
WHATSAPP_THROTTLE_MIN_INTERVAL=5
WHATSAPP_THROTTLE_MAX_INTERVAL=10
WHATSAPP_THROTTLE_KEY=global
```

## Manual Worker Commands

Jalankan worker berikut bila tidak memakai Supervisor:

```bash
php artisan queue:work database --queue=default --sleep=1 --tries=3 --backoff=5 --timeout=120 --max-time=3600

php artisan queue:work database --queue=notifications --sleep=1 --tries=3 --backoff=10 --timeout=120 --max-time=3600

php artisan queue:work database --queue=whatsapp --sleep=1 --tries=3 --backoff=10 --timeout=120 --max-time=3600
```

## Supervisor Example

Contoh konfigurasi Supervisor:

```ini
[program:web-cesa-default]
command=php /path/to/web-cesa/artisan queue:work database --queue=default --sleep=1 --tries=3 --backoff=5 --timeout=120 --max-time=3600
directory=/path/to/web-cesa
user=www-data
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
process_name=%(program_name)s_%(process_num)02d
redirect_stderr=true
stdout_logfile=/var/log/supervisor/web-cesa-default.log

[program:web-cesa-notifications]
command=php /path/to/web-cesa/artisan queue:work database --queue=notifications --sleep=1 --tries=3 --backoff=10 --timeout=120 --max-time=3600
directory=/path/to/web-cesa
user=www-data
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=1
process_name=%(program_name)s_%(process_num)02d
redirect_stderr=true
stdout_logfile=/var/log/supervisor/web-cesa-notifications.log

[program:web-cesa-whatsapp]
command=php /path/to/web-cesa/artisan queue:work database --queue=whatsapp --sleep=1 --tries=3 --backoff=10 --timeout=120 --max-time=3600
directory=/path/to/web-cesa
user=www-data
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=1
process_name=%(program_name)s_%(process_num)02d
redirect_stderr=true
stdout_logfile=/var/log/supervisor/web-cesa-whatsapp.log
```

## Apply Changes

Setelah update `.env` atau Supervisor, jalankan:

```bash
php artisan config:cache
php artisan queue:restart
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart web-cesa-default:*
sudo supervisorctl restart web-cesa-notifications:*
sudo supervisorctl restart web-cesa-whatsapp:*
```

## Important Notes

- `CACHE_STORE` harus shared antar worker atau antar server
- `database` aman dipakai, `redis` lebih baik
- jangan gunakan cache `file` bila worker berjalan di banyak process atau banyak server
- throttle global email dan WhatsApp mengandalkan shared cache
- queue `notifications` dan `whatsapp` sebaiknya tetap satu process agar ritme kirim tetap konservatif

## Safer Option

Kalau provider WhatsApp atau SMTP Anda sensitif, gunakan interval yang lebih konservatif:

```env
NOTIFICATION_MAIL_THROTTLE_MIN_INTERVAL=3
NOTIFICATION_MAIL_THROTTLE_MAX_INTERVAL=6
WHATSAPP_THROTTLE_MIN_INTERVAL=10
WHATSAPP_THROTTLE_MAX_INTERVAL=15
```
