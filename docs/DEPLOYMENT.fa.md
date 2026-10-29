# راهنمای استقرار بارراه روی سرور

این راهنما قدم‌به‌قدم توضیح می‌دهد که چطور سامانه باربری «بارراه» (مخزن `sirdadfar-saas-starter`) را که روی Laravel 13، Livewire 4 و Filament 5 ساخته شده، روی یک سرور لینوکسی نصب و اجرا کنید، چطور آن را به‌روز و پشتیبان‌گیری کنید و چطور از درستی هر تغییر با GitHub Actions مطمئن شوید.

برنامه پس از نصب به هیچ سرور خارجی نیاز ندارد. فونت، فایل‌های CSS و JS، پنل مدیریت، صف کارها و زمان‌بند همه روی همین سرور اجرا می‌شوند. اینترنت فقط هنگام نصب و build لازم است.

## فهرست

1. [پیش‌نیازها](#پیشنیازها)
2. [روش الف: استقرار با یک دستور (deploy.sh)](#روش-الف-استقرار-با-یک-دستور-deploysh)
3. [روش ب: استقرار دستی با Docker Compose](#روش-ب-استقرار-دستی-با-docker-compose)
4. [روش ج: اجرا بدون Docker](#روش-ج-اجرا-بدون-docker)
5. [متغیرهای محیطی](#متغیرهای-محیطی)
6. [ساخت اولین مدیر](#ساخت-اولین-مدیر)
7. [به‌روزرسانی و بازگشت به نسخه قبل](#بهروزرسانی-و-بازگشت-به-نسخه-قبل)
8. [پشتیبان‌گیری و بازیابی](#پشتیبانگیری-و-بازیابی)
9. [مشاهده لاگ‌ها و وضعیت](#مشاهده-لاگها-و-وضعیت)
10. [اتصال دامنه و HTTPS](#اتصال-دامنه-و-https)
11. [نکات ویژه سرورهای داخل ایران](#نکات-ویژه-سرورهای-داخل-ایران)
12. [بررسی خودکار با GitHub Actions](#بررسی-خودکار-با-github-actions)
13. [رفع اشکال](#رفع-اشکال)

## پیش‌نیازها

- یک سرور با سیستم‌عامل Ubuntu 22.04 یا 24.04 (یا Debian 12)
- حداقل ۲ گیگابایت رم و ۱۵ گیگابایت فضای خالی دیسک (ساخت ایمیج و MySQL به حافظه نیاز دارند)
- دسترسی SSH با کاربر root یا کاربری که دسترسی sudo دارد
- باز بودن پورت برنامه (پیش‌فرض `8080`) در فایروال، یا پورت‌های `80` و `443` اگر از دامنه و HTTPS استفاده می‌کنید
- دسترسی سرور به GitHub، Docker Hub، Packagist و مخزن npm هنگام نصب (برای سرورهای داخل ایران بخش [نکات ویژه](#نکات-ویژه-سرورهای-داخل-ایران) را ببینید)

اتصال به سرور و باز کردن پورت:

```bash
ssh root@SERVER_IP
sudo ufw allow 8080/tcp
```

## روش الف: استقرار با یک دستور (deploy.sh)

اسکریپت `deploy.sh` این کارها را خودکار انجام می‌دهد:

- نصب `git`، `curl` و `openssl` در صورت نبودن
- نصب Docker Engine و افزونه Docker Compose در صورت نبودن (اگر `get.docker.com` در دسترس نباشد، از بسته‌های `docker.io` و `docker-compose-v2` مخزن سیستم‌عامل استفاده می‌کند)
- دریافت کد از GitHub در مسیر `/opt/sirdadfar-saas-starter` یا به‌روزرسانی آن
- ساخت فایل `.env` از روی `.env.example` و ساخت رمزهای تصادفی برای `DB_PASSWORD` و `DB_ROOT_PASSWORD`
- ساخت ایمیج Docker و تولید `APP_KEY` با خود Laravel (`php artisan key:generate --show`)
- اجرای کانتینرهای برنامه و MySQL، اجرای migration ها و ساخت داده‌های پایه (استان‌ها، شهرها و انواع خودرو)
- ساخت اولین مدیر از `ADMIN_MOBILE` و `ADMIN_PASSWORD` (یا پرسیدن آن‌ها)
- صبر تا پاسخ `/up` و نمایش نشانی برنامه

### قدم ۱: دریافت اسکریپت

```bash
curl -fsSL https://raw.githubusercontent.com/hooshgametalent/sirdadfar-saas-starter/main/deploy.sh -o deploy.sh
chmod +x deploy.sh
```

### قدم ۲: اجرا

```bash
sudo ./deploy.sh
```

اسکریپت در پایان شماره موبایل و رمز مدیر را می‌پرسد. برای اجرای بدون پرسش، مقادیر را همراه دستور بدهید (مقادیر زیر نمونه هستند):

```bash
sudo ADMIN_MOBILE=09xxxxxxxxx ADMIN_PASSWORD='یک-رمز-قوی' ./deploy.sh
```

پس از پایان، پیامی مانند زیر نمایش داده می‌شود:

```text
[deploy] Health check: HTTP 200
[deploy] Done. The app is running:
[deploy]   Site:  http://SERVER_IP:8080/
[deploy]   Admin: http://SERVER_IP:8080/admin
```

### تنظیمات اختیاری

| متغیر | مقدار پیش‌فرض | توضیح |
| --- | --- | --- |
| `REPO_URL` | `https://github.com/hooshgametalent/sirdadfar-saas-starter.git` | نشانی مخزن |
| `BRANCH` | `main` | شاخه‌ای که اجرا می‌شود |
| `APP_DIR` | `/opt/sirdadfar-saas-starter` | مسیر نصب روی سرور |
| `APP_PORT` | مقدار `.env` یا `8080` | پورت برنامه روی سرور |
| `HEALTH_TIMEOUT` | `180` | چند ثانیه برای آماده شدن برنامه صبر شود |

> برای تغییر دائمی پورت، مقدار `APP_PORT` را در فایل `/opt/sirdadfar-saas-starter/.env` تغییر دهید.

### دستورهای دیگر اسکریپت

```bash
sudo ./deploy.sh update            # پشتیبان‌گیری، دریافت آخرین کد، ساخت دوباره و migrate
sudo ./deploy.sh admin             # ساخت مدیر (موبایل و رمز را می‌پرسد)
sudo ./deploy.sh backup            # پشتیبان پایگاه داده و مدارک خصوصی در پوشه backups
sudo ./deploy.sh artisan about     # اجرای هر دستور artisan داخل کانتینر
sudo ./deploy.sh logs              # نمایش زنده لاگ‌ها
sudo ./deploy.sh status            # وضعیت کانتینرها
sudo ./deploy.sh stop              # توقف برنامه (داده‌ها حفظ می‌شوند)
sudo ./deploy.sh help              # راهنما
```

## روش ب: استقرار دستی با Docker Compose

فایل `docker-compose.yml` فقط دو سرویس دارد:

- `app`: ایمیج برنامه شامل nginx، PHP-FPM 8.3، صف کارها (`queue:work database`) و زمان‌بند (`schedule:work`) که همه با supervisor اجرا می‌شوند. پوشه `storage` (نشست‌ها، لاگ‌ها و مدارک خصوصی رانندگان) در volume با نام `app-storage` نگه‌داری می‌شود.
- `mysql`: MySQL 8.4 با volume با نام `mysql-data`.

Redis یا سرویس دیگری لازم نیست.

### قدم ۱: نصب Docker (اگر نصب نیست)

```bash
curl -fsSL https://get.docker.com | sudo sh
sudo systemctl enable --now docker
docker compose version
```

### قدم ۲: دریافت کد

```bash
sudo git clone https://github.com/hooshgametalent/sirdadfar-saas-starter.git /opt/sirdadfar-saas-starter
cd /opt/sirdadfar-saas-starter
```

### قدم ۳: ساخت فایل تنظیمات

```bash
sudo cp .env.example .env
sudo chmod 600 .env
sudo nano .env
```

حداقل این مقادیر را تغییر دهید: `APP_URL`، `DB_PASSWORD` و `DB_ROOT_PASSWORD` (رمزهای طولانی و تصادفی، مثلا خروجی `openssl rand -hex 24`). اگر می‌خواهید مدیر هنگام اجرا ساخته شود، `ADMIN_MOBILE` و `ADMIN_PASSWORD` را هم پر کنید.

### قدم ۴: ساخت ایمیج و کلید برنامه

```bash
sudo docker compose build
sudo docker compose run --rm --no-deps app php artisan key:generate --show
```

خروجی دستور دوم چیزی مانند `base64:...` است. آن را در `.env` جلوی `APP_KEY=` قرار دهید.

### قدم ۵: اجرا

```bash
sudo docker compose up -d
```

هنگام شروع، کانتینر برنامه منتظر MySQL می‌ماند، تنظیمات و مسیرها را cache می‌کند، `php artisan migrate --force` را اجرا می‌کند (`RUN_MIGRATIONS=true`) و داده‌های پایه و مدیر را می‌سازد (`RUN_SEEDERS=true`). این Seeder ها تکرارپذیر هستند و اجرای دوباره آن‌ها داده‌ای را خراب نمی‌کند.

### قدم ۶: بررسی

```bash
sudo docker compose ps
curl -i http://127.0.0.1:8080/up
```

ستون `STATUS` کانتینر `app` باید پس از کمتر از یک دقیقه `healthy` شود.

## روش ج: اجرا بدون Docker

این روش برای Ubuntu 24.04 نوشته شده که PHP 8.3 را در مخزن خودش دارد.

### قدم ۱: نصب بسته‌ها

```bash
sudo apt-get update
sudo apt-get install -y nginx mysql-server supervisor git unzip composer \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-intl php8.3-bcmath php8.3-gd \
  php8.3-zip php8.3-mbstring php8.3-xml php8.3-curl
```

برای build فایل‌های CSS و JS به Node.js نسخه ۲۲ نیاز دارید (نسخه مخزن Ubuntu قدیمی است):

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt-get install -y nodejs
```

> اگر نمی‌خواهید Node.js روی سرور نصب شود، `npm run build` را روی سیستم دیگری اجرا کنید و پوشه `public/build` را به سرور کپی کنید.

### قدم ۲: پایگاه داده

```bash
sudo mysql -e "CREATE DATABASE barrah CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'barrah'@'localhost' IDENTIFIED BY 'رمز-قوی-پایگاه-داده';"
sudo mysql -e "GRANT ALL PRIVILEGES ON barrah.* TO 'barrah'@'localhost';"
```

### قدم ۳: دریافت کد و نصب وابستگی‌ها

```bash
sudo git clone https://github.com/hooshgametalent/sirdadfar-saas-starter.git /var/www/barrah
sudo chown -R "$USER":www-data /var/www/barrah
cd /var/www/barrah
cp .env.example .env
nano .env   # APP_URL، DB_PASSWORD و در صورت نیاز ADMIN_MOBILE و ADMIN_PASSWORD
composer install --no-dev --optimize-autoloader
php artisan key:generate
npm install
npm run build
```

### قدم ۴: migrate، داده‌های پایه و cache

```bash
php artisan migrate --force
php artisan db:seed --force --class='Database\Seeders\ProvinceCitySeeder'
php artisan db:seed --force --class='Database\Seeders\VehicleTypeSeeder'
php artisan db:seed --force --class='Database\Seeders\AdminUserSeeder'
php artisan optimize
sudo chown -R www-data:www-data storage bootstrap/cache
```

### قدم ۵: تنظیم PHP

حجم مجاز بارگذاری باید با `FREIGHT_DOCUMENT_MAX_KB` (پیش‌فرض ۵۱۲۰ کیلوبایت) هماهنگ باشد. در فایل `/etc/php/8.3/fpm/conf.d/99-barrah.ini` بنویسید:

```ini
upload_max_filesize = 6M
post_max_size = 8M
memory_limit = 256M
expose_php = Off
```

```bash
sudo systemctl restart php8.3-fpm
```

### قدم ۶: تنظیم nginx

فایل `/etc/nginx/sites-available/barrah`:

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/barrah/public;
    index index.php;
    charset utf-8;
    client_max_body_size 8m;

    # robots.txt و sitemap.xml مسیرهای Laravel هستند، پس فایل ثابتی برای آن‌ها وجود ندارد.
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ \.php$ {
        return 404;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/barrah /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

### قدم ۷: صف کارها با supervisor

فایل `/etc/supervisor/conf.d/barrah-queue.conf`:

```ini
[program:barrah-queue]
command=php /var/www/barrah/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
user=www-data
autostart=true
autorestart=true
stopwaitsecs=60
stdout_logfile=/var/www/barrah/storage/logs/queue.log
redirect_stderr=true
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
```

### قدم ۸: زمان‌بند با cron

```bash
echo '* * * * * www-data cd /var/www/barrah && php artisan schedule:run >> /dev/null 2>&1' | sudo tee /etc/cron.d/barrah
```

## متغیرهای محیطی

همه تنظیمات در فایل `.env` هستند. پس از هر تغییر در `.env`، در Docker دستور `sudo docker compose up -d --force-recreate app` و بدون Docker دستور `php artisan optimize` را اجرا کنید، چون تنظیمات cache می‌شوند.

| متغیر | پیش‌فرض | توضیح |
| --- | --- | --- |
| `APP_NAME` | `بارراه` | نام سایت |
| `APP_ENV` | `production` | محیط اجرا. روی سرور همیشه `production` |
| `APP_KEY` | خالی | کلید رمزنگاری Laravel. یک بار ساخته می‌شود و نباید تغییر کند |
| `APP_DEBUG` | `false` | روی سرور هرگز `true` نکنید |
| `APP_URL` | `http://localhost` | نشانی کامل سایت، مثلا `https://example.com` |
| `APP_TIMEZONE` | `Asia/Tehran` | منطقه زمانی |
| `APP_PORT` | `8080` | پورت کانتینر روی سرور (فقط Docker) |
| `APP_BIND` | `0.0.0.0` | نشانی شنود پورت. پشت پروکسی روی همان سرور `127.0.0.1` بگذارید |
| `DB_DATABASE` | `barrah` | نام پایگاه داده |
| `DB_USERNAME` | `barrah` | کاربر پایگاه داده |
| `DB_PASSWORD` | `change-me` | رمز کاربر پایگاه داده. حتما تغییر دهید |
| `DB_ROOT_PASSWORD` | `change-me-root` | رمز root در MySQL کانتینر (فقط Docker). حتما تغییر دهید |
| `RUN_MIGRATIONS` | `true` | اجرای خودکار migration هنگام شروع کانتینر |
| `RUN_SEEDERS` | `true` | ساخت خودکار استان‌ها، شهرها، انواع خودرو و مدیر هنگام شروع کانتینر |
| `ADMIN_NAME` | `مدیر سامانه` | نام اولین مدیر |
| `ADMIN_MOBILE` | خالی | موبایل اولین مدیر (۱۱ رقم، شروع با `09`) |
| `ADMIN_PASSWORD` | خالی | رمز اولین مدیر. پس از ساخت مدیر پاک کنید |
| `DEMO_SEED` | `false` | ساخت داده نمونه خیالی. روی سرور واقعی `false` بماند |
| `TRUSTED_PROXIES` | خالی | نشانی پروکسی‌های مورد اعتماد، مثلا `*` پشت nginx یا Caddy روی همان سرور |
| `FREIGHT_DOCUMENT_MAX_KB` | `5120` | حداکثر حجم هر مدرک راننده به کیلوبایت |
| `SMS_DRIVER` | `log` | پیامک‌ها فقط در لاگ نوشته می‌شوند |
| `MAIL_MAILER` | `log` | ایمیل‌ها فقط در لاگ نوشته می‌شوند |
| `LOG_LEVEL` | `warning` | سطح لاگ |
| `SESSION_DRIVER`، `CACHE_STORE`، `QUEUE_CONNECTION` | `database` | نشست، کش و صف روی MySQL |
| `COMPOSER_MIRROR`، `NPM_REGISTRY` | خالی | میرور اختیاری بسته‌ها، فقط هنگام build ایمیج |

> اگر `FREIGHT_DOCUMENT_MAX_KB` را بیشتر از ۶ مگابایت کنید، مقدارهای `upload_max_filesize` و `post_max_size` در `docker/php.ini` و `client_max_body_size` در `docker/nginx.conf` را هم بزرگ‌تر کنید و ایمیج را دوباره بسازید.

## ساخت اولین مدیر

ثبت‌نام عمومی فقط برای صاحب بار و راننده است. اولین مدیر یکی از این راه‌ها ساخته می‌شود:

- **با اسکریپت:** `sudo ./deploy.sh admin` (موبایل و رمز را می‌پرسد و آن‌ها را در `.env` ذخیره نمی‌کند).
- **با `.env` در Docker:** `ADMIN_MOBILE` و `ADMIN_PASSWORD` را پر کنید و `sudo docker compose up -d --force-recreate app` را اجرا کنید.
- **بدون Docker:** پس از پر کردن `.env` دستور `php artisan db:seed --force --class='Database\Seeders\AdminUserSeeder'` را اجرا کنید.

سپس به نشانی `/admin` بروید و با شماره موبایل و رمز وارد شوید. مدیر می‌تواند از بخش کاربران، اپراتورها و ویرایشگرهای محتوا را بسازد.

> اگر کاربری با همان موبایل از قبل وجود داشته باشد، Seeder رمز او را تغییر نمی‌دهد و فقط نقش مدیر و فعال بودن را تنظیم می‌کند. پس از ساخت مدیر، `ADMIN_PASSWORD` را از `.env` پاک کنید. اگر `ADMIN_MOBILE` در `.env` بماند، با هر شروع دوباره کانتینر آن حساب دوباره مدیر و فعال می‌شود.

## به‌روزرسانی و بازگشت به نسخه قبل

### به‌روزرسانی

با اسکریپت (قبل از به‌روزرسانی به صورت خودکار پشتیبان می‌گیرد):

```bash
sudo ./deploy.sh update
```

با Docker Compose:

```bash
cd /opt/sirdadfar-saas-starter
sudo ./deploy.sh backup          # یا دستورهای بخش پشتیبان‌گیری
sudo git pull
sudo docker compose up -d --build
```

migration ها هنگام شروع کانتینر جدید اجرا می‌شوند.

بدون Docker:

```bash
cd /var/www/barrah
mysqldump -u barrah -p --single-transaction barrah | gzip > ~/barrah-$(date +%F).sql.gz
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan optimize
sudo supervisorctl restart barrah-queue
php artisan up
```

### بازگشت به نسخه قبل

1. شناسه commit قبلی را پیدا کنید: `git log --oneline -5`
2. کد را به آن نسخه برگردانید: `sudo git checkout <commit>`
3. اگر نسخه جدید migration تازه‌ای داشت، پایگاه داده را از پشتیبانی که قبل از به‌روزرسانی گرفته‌اید بازیابی کنید (بخش بعد).
4. دوباره بسازید و اجرا کنید: `sudo docker compose up -d --build`

> بازیابی پشتیبان امن‌تر از `php artisan migrate:rollback` است، چون rollback ممکن است ستون‌ها یا جدول‌های تازه و داده‌های آن‌ها را حذف کند.

## پشتیبان‌گیری و بازیابی

دو چیز باید پشتیبان‌گیری شود: پایگاه داده MySQL و پوشه `storage/app/private` (مدارک رانندگان).

### پشتیبان‌گیری

```bash
sudo ./deploy.sh backup
```

فایل‌ها در `/opt/sirdadfar-saas-starter/backups` با نام‌های `db-تاریخ.sql.gz` و `storage-تاریخ.tar.gz` ذخیره می‌شوند. دستورهای معادل دستی:

```bash
cd /opt/sirdadfar-saas-starter
sudo docker compose exec -T mysql sh -c 'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --triggers --no-tablespaces "$MYSQL_DATABASE"' | gzip > db.sql.gz
sudo docker compose exec -T app tar -C /var/www/html/storage -czf - app/private > storage.tar.gz
```

پشتیبان‌گیری خودکار هر شب ساعت ۳ با cron:

```bash
echo '0 3 * * * root /opt/sirdadfar-saas-starter/deploy.sh backup >> /var/log/barrah-backup.log 2>&1' | sudo tee /etc/cron.d/barrah-backup
```

فایل‌های پشتیبان را به صورت دوره‌ای به جای دیگری (سرور یا دیسک دیگر) منتقل کنید و پشتیبان‌های قدیمی را پاک کنید.

### بازیابی

```bash
cd /opt/sirdadfar-saas-starter
gunzip -c backups/db-YYYYMMDD-HHMMSS.sql.gz | sudo docker compose exec -T mysql sh -c 'exec mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'
sudo docker compose exec -T app tar -C /var/www/html/storage -xzf - < backups/storage-YYYYMMDD-HHMMSS.tar.gz
sudo docker compose restart app
```

## مشاهده لاگ‌ها و وضعیت

```bash
cd /opt/sirdadfar-saas-starter
sudo docker compose ps
sudo docker compose logs -f --tail=200 app     # nginx، PHP، صف، زمان‌بند و لاگ Laravel
sudo docker compose logs --tail=100 mysql
sudo docker compose exec app php artisan about
sudo docker compose exec app php artisan queue:failed
```

در Docker لاگ Laravel روی خروجی کانتینر نوشته می‌شود (`LOG_CHANNEL=stderr`). پیامک‌ها هم چون `SMS_DRIVER=log` است در همین لاگ دیده می‌شوند. بدون Docker لاگ‌ها در `storage/logs/laravel.log` هستند.

## اتصال دامنه و HTTPS

ابتدا رکورد `A` دامنه را به IP سرور اشاره دهید. سپس در `.env` این مقادیر را تنظیم کنید تا کانتینر فقط از داخل سرور در دسترس باشد و Laravel نشانی‌ها را با `https` بسازد:

```dotenv
APP_URL=https://example.com
APP_BIND=127.0.0.1
TRUSTED_PROXIES=*
```

```bash
sudo docker compose up -d --force-recreate app
```

> `TRUSTED_PROXIES=*` فقط وقتی امن است که پورت کانتینر مثل بالا فقط روی `127.0.0.1` باز باشد و درخواست‌ها فقط از پروکسی همان سرور برسند.

### گزینه ۱: Nginx و Certbot

```bash
sudo apt-get install -y nginx certbot python3-certbot-nginx
```

فایل `/etc/nginx/sites-available/barrah-proxy`:

```nginx
server {
    listen 80;
    server_name example.com;
    client_max_body_size 8m;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Port $server_port;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/barrah-proxy /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d example.com
sudo ufw allow 'Nginx Full'
sudo ufw delete allow 8080/tcp
```

Certbot گواهی را به صورت خودکار تمدید می‌کند. برای اطمینان: `sudo certbot renew --dry-run`

### گزینه ۲: Caddy (گواهی HTTPS خودکار)

```bash
sudo apt-get install -y caddy
```

فایل `/etc/caddy/Caddyfile`:

```text
example.com {
    reverse_proxy 127.0.0.1:8080
}
```

```bash
sudo systemctl reload caddy
```

در روش ج (بدون Docker) کافی است `sudo certbot --nginx -d example.com` را روی همان تنظیم nginx قدم ۶ اجرا کنید و `APP_URL` را `https://` بگذارید. در این حالت `TRUSTED_PROXIES` لازم نیست.

> به جای `example.com` دامنه خودتان را قرار دهید.

## نکات ویژه سرورهای داخل ایران

برنامه در زمان اجرا به هیچ سرور خارجی نیاز ندارد: فونت وزیرمتن، فایل‌های Filament و Livewire، صف، کش و نشست همه داخل سرور هستند و پیامک و پرداخت به سرویس خارجی وصل نیستند. محدودیت‌های شبکه فقط روی **نصب و build** اثر دارند:

- **میرور Docker:** اگر ارائه‌دهنده سرور یا یک سرویس معتبر میرور Docker Hub دارد، آن را در `/etc/docker/daemon.json` تنظیم کنید و Docker را ری‌استارت کنید:

  ```json
  {
    "registry-mirrors": ["https://YOUR-MIRROR-ADDRESS"]
  }
  ```

  ```bash
  sudo systemctl restart docker
  ```

- **میرور Composer و npm هنگام build:** در `.env` مقدارهای زیر را بگذارید. `docker compose build` آن‌ها را فقط هنگام ساخت ایمیج استفاده می‌کند:

  ```dotenv
  COMPOSER_MIRROR=https://YOUR-PACKAGIST-MIRROR
  NPM_REGISTRY=https://YOUR-NPM-MIRROR/
  ```

  در روش ج همین کار با `composer config -g repos.packagist composer https://YOUR-PACKAGIST-MIRROR` و `npm config set registry https://YOUR-NPM-MIRROR/` انجام می‌شود.

- **نصب Docker:** اگر `get.docker.com` در دسترس نیست، `deploy.sh` خودش سراغ بسته‌های `docker.io` و `docker-compose-v2` مخزن apt سیستم‌عامل می‌رود. دستی: `sudo apt-get install -y docker.io docker-compose-v2`

- **ساخت ایمیج روی سیستم دیگر:** ایمیج را روی سیستمی با دسترسی آزاد بسازید و به سرور منتقل کنید. ایمیج MySQL را هم به همین شکل منتقل کنید:

  ```bash
  docker compose build
  docker pull mysql:8.4
  docker save sirdadfar-saas-starter:latest mysql:8.4 | gzip > barrah-images.tar.gz
  scp barrah-images.tar.gz root@SERVER_IP:/root/
  # روی سرور:
  gunzip -c /root/barrah-images.tar.gz | docker load
  cd /opt/sirdadfar-saas-starter
  sudo docker compose up -d --no-build
  ```

> نشانی میرورها در این راهنما فقط نمونه هستند. قبل از استفاده از معتبر بودن و در دسترس بودن میرور مطمئن شوید.

## بررسی خودکار با GitHub Actions

فایل `.github/workflows/ci.yml` با هر push و هر Pull Request اجرا می‌شود و دو مرحله (job) دارد.

### مرحله `laravel`

1. نصب PHP 8.3 و Node.js 22
2. نصب وابستگی‌ها: اگر `composer.lock` و `package-lock.json` در مخزن باشند از آن‌ها استفاده می‌شود، وگرنه وابستگی‌ها حل می‌شوند و فایل‌های قفل به عنوان Artifact با نام `lockfiles` ذخیره می‌شوند.
   - **توصیه:** پس از اولین اجرای موفق، Artifact `lockfiles` را از صفحه همان اجرا دانلود کنید و `composer.lock` و `package-lock.json` را در ریشه پروژه commit کنید تا نسخه‌ها ثابت بمانند.
3. ساخت فایل‌های CSS و JS با `npm run build`
4. بررسی قالب‌بندی کد با `vendor/bin/pint --test`
5. اجرای migration و Seeder ها روی MySQL 8.4، سپس rollback و migrate دوباره
6. اجرای تست‌ها با `php artisan test`
7. بررسی امنیتی وابستگی‌ها با `composer audit`

### مرحله `docker`

1. ساخت ایمیج با همان فایل‌های قفل مرحله قبل
2. اجرای MySQL 8.4 و کانتینر برنامه در یک شبکه Docker **بدون دسترسی به اینترنت**
3. بررسی پاسخ صفحه‌های `/up`، `/`، `/login`، `/admin/login`، `/robots.txt` و `/sitemap.xml`
4. بررسی اینکه صفحه اصلی فایل‌ها را از `/build/assets` خود برنامه بارگذاری می‌کند و نشانی CDN یا سرویس فونت خارجی ندارد
5. بررسی اجرای صف کارها و زمان‌بند، ساخت داده‌های پایه و مدیر، و `healthy` شدن کانتینر

### مشاهده نتیجه

- به [صفحه Actions مخزن](https://github.com/hooshgametalent/sirdadfar-saas-starter/actions) بروید.
- هر اجرا با تیک سبز (موفق) یا ضربدر قرمز (ناموفق) مشخص است. با کلیک روی آن لاگ هر مرحله را می‌بینید. اگر مرحله docker ناموفق باشد، لاگ کانتینرها در انتهای آن چاپ می‌شود.

### اجرای همین بررسی‌ها روی سیستم خودتان

```bash
composer install
npm install
npm run build
vendor/bin/pint --test
php artisan test
docker build -t sirdadfar:local .
```

## رفع اشکال

| مشکل | راه‌حل |
| --- | --- |
| `permission denied` هنگام اجرای docker | دستور را با `sudo` اجرا کنید یا کاربر را به گروه docker اضافه کنید: `sudo usermod -aG docker $USER` و دوباره وارد شوید |
| `Set DB_PASSWORD in .env` یا `Set DB_ROOT_PASSWORD in .env` | این دو مقدار را در `.env` پر کنید |
| کانتینر با پیام `APP_KEY is not set` متوقف می‌شود | قدم ۴ روش ب را انجام دهید یا `sudo ./deploy.sh update` را اجرا کنید |
| `The database did not become reachable` | `sudo docker compose logs mysql` را بررسی کنید. اگر رمزها را پس از اولین اجرا تغییر داده‌اید، MySQL هنوز رمز قبلی را دارد؛ رمز را به مقدار قبلی برگردانید یا رمز کاربر را داخل MySQL تغییر دهید |
| `port is already allocated` | پورت دیگری در `.env` بگذارید (مثلا `APP_PORT=8081`) و `sudo docker compose up -d` را اجرا کنید |
| خطای `500` پس از تغییر `.env` | `sudo docker compose up -d --force-recreate app` را اجرا کنید تا تنظیمات دوباره cache شوند، سپس لاگ را ببینید |
| پس از فعال کردن HTTPS، فرم‌ها یا پنل مدیریت درست کار نمی‌کنند (Mixed content) | `APP_URL` را با `https://` و `TRUSTED_PROXIES=*` و `APP_BIND=127.0.0.1` تنظیم کنید و کانتینر را دوباره بسازید |
| بارگذاری مدرک با خطای `413` یا خطای حجم | حجم فایل بیشتر از حد مجاز است. بخش متغیرهای محیطی درباره `FREIGHT_DOCUMENT_MAX_KB` را ببینید |
| پیامک‌ها ارسال نمی‌شوند | فعلا پیامک‌ها فقط در لاگ نوشته می‌شوند (`SMS_DRIVER=log`) و این رفتار عمدی است |
| build با خطای کمبود حافظه متوقف می‌شود | رم سرور را افزایش دهید یا فایل swap بسازید (پایین را ببینید) |
| دریافت ایمیج یا بسته‌ها با timeout خطا می‌دهد | بخش [نکات ویژه سرورهای داخل ایران](#نکات-ویژه-سرورهای-داخل-ایران) را ببینید |
| وضعیت کانتینر `unhealthy` است | `sudo docker compose logs app` را بررسی کنید |
| صفحه از بیرون سرور باز نمی‌شود | فایروال سرور و پنل ارائه‌دهنده را بررسی کنید و مطمئن شوید `APP_BIND` روی `127.0.0.1` نیست مگر پشت پروکسی |
| فراموشی رمز مدیر | مدیر دیگری از بخش کاربران رمز را تغییر دهد، یا: `sudo docker compose exec app php artisan tinker` و سپس `App\Models\User::where('mobile', '09xxxxxxxxx')->first()->update(['password' => 'رمز-جدید']);` |

ساخت swap دو گیگابایتی:

```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```
