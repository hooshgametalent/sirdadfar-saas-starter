# راهنمای استقرار پروژه روی سرور

این راهنما قدم به قدم توضیح می‌دهد که چطور پروژه «بارراه» (sirdadfar-saas-starter) را روی یک سرور لینوکسی اجرا کنید و چطور از درستی هر تغییر با GitHub Actions مطمئن شوید.

## فهرست

1. [پیش‌نیازها](#پیشنیازها)
2. [روش الف: استقرار با یک دستور (deploy.sh)](#روش-الف-استقرار-با-یک-دستور-deploysh)
3. [روش ب: استقرار دستی با Docker Compose](#روش-ب-استقرار-دستی-با-docker-compose)
4. [روش ج: اجرا بدون Docker](#روش-ج-اجرا-بدون-docker)
5. [به‌روزرسانی برنامه](#بهروزرسانی-برنامه)
6. [مشاهده لاگ‌ها و وضعیت](#مشاهده-لاگها-و-وضعیت)
7. [اتصال دامنه و HTTPS](#اتصال-دامنه-و-https)
8. [نکات ویژه سرورهای داخل ایران](#نکات-ویژه-سرورهای-داخل-ایران)
9. [بررسی خودکار با GitHub Actions](#بررسی-خودکار-با-github-actions)
10. [رفع اشکال](#رفع-اشکال)

## پیش‌نیازها

- یک سرور با سیستم‌عامل Ubuntu 22.04 یا 24.04 (یا Debian 12)
- حداقل ۲ گیگابایت رم و ۱۰ گیگابایت فضای خالی دیسک (مرحله build برنامه Next.js به رم نیاز دارد)
- دسترسی SSH با کاربر root یا کاربری که دسترسی sudo دارد
- باز بودن پورت برنامه (پیش‌فرض `3000`) در فایروال سرور، یا پورت‌های `80` و `443` اگر از دامنه و HTTPS استفاده می‌کنید
- دسترسی سرور به GitHub، Docker Hub و مخزن npm (برای سرورهای داخل ایران بخش [نکات ویژه](#نکات-ویژه-سرورهای-داخل-ایران) را ببینید)

اتصال به سرور:

```bash
ssh root@SERVER_IP
```

اگر از فایروال `ufw` استفاده می‌کنید، پورت را باز کنید:

```bash
sudo ufw allow 3000/tcp
```

## روش الف: استقرار با یک دستور (deploy.sh)

اسکریپت `deploy.sh` همه کارها را خودکار انجام می‌دهد:

- نصب `git` و `curl` در صورت نبودن
- نصب Docker Engine و افزونه Docker Compose در صورت نبودن
- دریافت کد از GitHub در مسیر `/opt/sirdadfar-saas-starter` (یا به‌روزرسانی آن اگر قبلا دریافت شده)
- ساخت فایل `.env` از روی `.env.example` اگر وجود نداشته باشد
- ساخت ایمیج Docker و اجرای برنامه
- صبر تا آماده شدن برنامه و نمایش نشانی آن

### قدم ۱: دریافت اسکریپت

```bash
curl -fsSL https://raw.githubusercontent.com/hooshgametalent/sirdadfar-saas-starter/main/deploy.sh -o deploy.sh
chmod +x deploy.sh
```

### قدم ۲: اجرا

```bash
sudo ./deploy.sh
```

پس از پایان، پیامی مانند زیر نمایش داده می‌شود:

```text
[deploy] Done. The app is running:
[deploy]   http://SERVER_IP:3000/
```

### تنظیمات اختیاری

اسکریپت با متغیرهای محیطی زیر قابل تنظیم است:

| متغیر      | مقدار پیش‌فرض                                                   | توضیح                  |
| ---------- | --------------------------------------------------------------- | ---------------------- |
| `REPO_URL` | `https://github.com/hooshgametalent/sirdadfar-saas-starter.git` | نشانی مخزن             |
| `BRANCH`   | `main`                                                          | شاخه‌ای که اجرا می‌شود |
| `APP_DIR`  | `/opt/sirdadfar-saas-starter`                                   | مسیر نصب روی سرور      |
| `APP_PORT` | `3000`                                                          | پورت برنامه روی سرور   |

نمونه اجرا روی پورت `8080` از یک شاخه دیگر:

```bash
sudo APP_PORT=8080 BRANCH=chore/ci-docker-deploy ./deploy.sh
```

> نکته: برای اینکه پورت به صورت دائمی تغییر کند، مقدار `APP_PORT` را در فایل `/opt/sirdadfar-saas-starter/.env` تغییر دهید.

### دستورهای دیگر اسکریپت

```bash
sudo ./deploy.sh update   # دریافت آخرین کد و ساخت دوباره
sudo ./deploy.sh logs     # نمایش زنده لاگ‌ها
sudo ./deploy.sh status   # وضعیت کانتینر
sudo ./deploy.sh stop     # توقف برنامه
sudo ./deploy.sh help     # راهنما
```

## روش ب: استقرار دستی با Docker Compose

اگر Docker از قبل روی سرور نصب است یا می‌خواهید مراحل را خودتان انجام دهید:

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

پوشه `/opt/sirdadfar-saas-starter` متعلق به root است، پس این دستورها هم با `sudo` اجرا می‌شوند (اگر با کاربر root وارد شده‌اید، `sudo` لازم نیست):

```bash
sudo cp .env.example .env
sudo nano .env   # در صورت نیاز APP_PORT را تغییر دهید
```

### قدم ۴: ساخت و اجرا

```bash
sudo docker compose up -d --build
```

### قدم ۵: بررسی

```bash
sudo docker compose ps
curl -I http://127.0.0.1:3000/
```

ستون `STATUS` باید پس از چند ثانیه مقدار `healthy` را نشان دهد.

### اجرا فقط با Docker (بدون Compose)

```bash
sudo docker build -t sirdadfar-saas-starter .
sudo docker run -d --name sirdadfar-web --restart unless-stopped -p 3000:3000 sirdadfar-saas-starter
```

## روش ج: اجرا بدون Docker

### قدم ۱: نصب Node.js نسخه ۲۲

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt-get install -y nodejs
node -v   # باید v22 یا بالاتر باشد (حداقل 20.9)
```

### قدم ۲: دریافت کد در مسیر `/opt` و واگذاری مالکیت به کاربر خودتان

کد در همان مسیری قرار می‌گیرد که سرویس systemd در ادامه از آن استفاده می‌کند. مالکیت پوشه به کاربر فعلی داده می‌شود تا `npm` و `git` بدون `sudo` کار کنند:

```bash
sudo git clone https://github.com/hooshgametalent/sirdadfar-saas-starter.git /opt/sirdadfar-saas-starter
sudo chown -R "$USER":"$USER" /opt/sirdadfar-saas-starter
cd /opt/sirdadfar-saas-starter
```

### قدم ۳: نصب وابستگی‌ها و build

```bash
npm install
npm run build
```

### قدم ۴: آماده‌سازی خروجی standalone

در `next.config.ts` گزینه `output: "standalone"` فعال است. در این حالت Next.js یک سرور مستقل در `.next/standalone/server.js` می‌سازد و دستور `npm start` (یعنی `next start`) برای این حالت مناسب نیست و هشدار می‌دهد. فایل‌های ثابت باید کنار سرور مستقل کپی شوند (این کار را بعد از هر build تکرار کنید):

```bash
cp -r .next/static .next/standalone/.next/
[ -d public ] && cp -r public .next/standalone/ || true
```

### قدم ۵: اجرا

```bash
PORT=3000 HOSTNAME=0.0.0.0 NODE_ENV=production node .next/standalone/server.js
```

### اجرای دائمی با pm2 (اختیاری)

```bash
sudo npm install -g pm2
PORT=3000 HOSTNAME=0.0.0.0 NODE_ENV=production pm2 start .next/standalone/server.js --name sirdadfar
pm2 save
pm2 startup   # دستوری که چاپ می‌شود را اجرا کنید تا پس از ری‌استارت سرور هم اجرا شود
```

### اجرای دائمی با systemd (اختیاری)

فایل `/etc/systemd/system/sirdadfar.service` را بسازید و به جای `YOUR_USER` نام کاربری را بگذارید که در قدم ۲ مالک پوشه شد (خروجی دستور `whoami`):

```ini
[Unit]
Description=sirdadfar-saas-starter
After=network.target

[Service]
WorkingDirectory=/opt/sirdadfar-saas-starter
Environment=NODE_ENV=production
Environment=PORT=3000
Environment=HOSTNAME=0.0.0.0
ExecStart=/usr/bin/node .next/standalone/server.js
Restart=always
User=YOUR_USER

[Install]
WantedBy=multi-user.target
```

سپس:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now sirdadfar
```

## به‌روزرسانی برنامه

- با اسکریپت: `sudo ./deploy.sh update`
- با Docker Compose:

```bash
cd /opt/sirdadfar-saas-starter
sudo git pull
sudo docker compose up -d --build
```

- بدون Docker:

```bash
cd /opt/sirdadfar-saas-starter
git pull
npm install
npm run build
cp -r .next/static .next/standalone/.next/
[ -d public ] && cp -r public .next/standalone/ || true
pm2 restart sirdadfar   # یا: sudo systemctl restart sirdadfar
```

## مشاهده لاگ‌ها و وضعیت

```bash
cd /opt/sirdadfar-saas-starter
sudo docker compose ps
sudo docker compose logs -f --tail=200 web
```

## اتصال دامنه و HTTPS

ابتدا رکورد `A` دامنه را به IP سرور اشاره دهید. سپس یکی از دو روش زیر را انتخاب کنید.

### گزینه ۱: Caddy (ساده‌ترین، گواهی HTTPS خودکار)

```bash
sudo apt-get install -y caddy
```

محتوای فایل `/etc/caddy/Caddyfile`:

```text
example.com {
    reverse_proxy 127.0.0.1:3000
}
```

```bash
sudo systemctl reload caddy
```

### گزینه ۲: Nginx و Certbot

```bash
sudo apt-get install -y nginx certbot python3-certbot-nginx
```

محتوای فایل `/etc/nginx/sites-available/sirdadfar`:

```nginx
server {
    listen 80;
    server_name example.com;

    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/sirdadfar /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d example.com
```

> به جای `example.com` دامنه خودتان را قرار دهید. پس از راه‌اندازی پروکسی، بهتر است پورت `3000` را در فایروال ببندید و فقط پورت‌های `80` و `443` باز بمانند.

## نکات ویژه سرورهای داخل ایران

روی برخی سرورهای داخل ایران دسترسی به Docker Hub، مخزن npm یا اسکریپت `get.docker.com` ممکن است محدود یا کند باشد. در این حالت:

- **میرور Docker:** اگر ارائه‌دهنده سرور یا یک سرویس معتبر، میرور Docker Hub در اختیار شما می‌گذارد، آن را در فایل `/etc/docker/daemon.json` تنظیم کنید و Docker را ری‌استارت کنید:

  ```json
  {
    "registry-mirrors": ["https://YOUR-MIRROR-ADDRESS"]
  }
  ```

  ```bash
  sudo systemctl restart docker
  ```

- **میرور npm:** اگر به مخزن npm دسترسی ندارید، نشانی یک مخزن جایگزین معتبر را تنظیم کنید:

  ```bash
  npm config set registry https://YOUR-NPM-MIRROR/
  ```

  برای build داخل Docker می‌توانید یک فایل `.npmrc` با همین مقدار کنار `package.json` قرار دهید.

- **نصب Docker:** اگر `get.docker.com` در دسترس نیست، Docker را از مخزن apt سیستم‌عامل نصب کنید (`sudo apt-get install -y docker.io docker-compose-v2`) یا از میرور ارائه‌دهنده سرور استفاده کنید. اسکریپت `deploy.sh` هم در این حالت به صورت خودکار سراغ بسته‌های `docker.io` و `docker-compose-v2` از مخزن apt سیستم‌عامل می‌رود.

- **راه جایگزین:** می‌توانید ایمیج را روی سیستمی که دسترسی آزاد دارد بسازید و به سرور منتقل کنید:

  ```bash
  docker build -t sirdadfar-saas-starter:latest .
  docker save sirdadfar-saas-starter:latest | gzip > sirdadfar.tar.gz
  scp sirdadfar.tar.gz root@SERVER_IP:/root/
  # روی سرور:
  gunzip -c /root/sirdadfar.tar.gz | docker load
  docker run -d --name sirdadfar-web --restart unless-stopped -p 3000:3000 sirdadfar-saas-starter:latest
  ```

> نشانی میرورها در این راهنما فقط نمونه هستند. قبل از استفاده، از معتبر بودن و در دسترس بودن میرور مطمئن شوید.

## بررسی خودکار با GitHub Actions

فایل `.github/workflows/ci.yml` با هر push به هر شاخه و با هر Pull Request به صورت خودکار روی سرورهای رایگان GitHub اجرا می‌شود و این مراحل را انجام می‌دهد:

1. **نصب وابستگی‌ها:** اگر `package-lock.json` وجود داشته باشد با `npm ci`، در غیر این صورت با `npm install`. فایل `package-lock.json` به عنوان Artifact با نام `package-lock` ذخیره می‌شود و مرحله ساخت ایمیج Docker از همین فایل استفاده می‌کند، تا ایمیج دقیقا با همان نسخه‌های بررسی‌شده ساخته شود.
   - **توصیه مهم:** تا وقتی `package-lock.json` در مخزن نیست، هر اجرای CI ممکن است نسخه‌های جدیدتری از بسته‌ها بگیرد. پس از اولین اجرای موفق، Artifact `package-lock` را از صفحه همان اجرا دانلود کنید، فایل `package-lock.json` را در ریشه پروژه قرار دهید و commit کنید.
2. **بررسی امنیتی وابستگی‌ها:** `npm audit --omit=dev --audit-level=high` (اگر بسته‌های اجرایی آسیب‌پذیری جدی داشته باشند، CI ناموفق می‌شود)
3. **بررسی کد (Lint):** `npm run lint`
4. **بررسی نوع‌ها (TypeScript):** `npm run typecheck`
5. **بررسی قالب‌بندی (Prettier):** `npm run format:check`
6. **ساخت پروژه:** `npm run build`
7. **ساخت ایمیج Docker:** اثبات اینکه پروژه روی هر سروری که Docker دارد اجرا می‌شود.
8. **آزمون اجرا (Smoke test):** کانتینر اجرا می‌شود و صفحه‌های `/` و `/orders` باید پاسخ `200` بدهند.

### مشاهده نتیجه

- در صفحه مخزن در GitHub به تب **Actions** بروید: [Actions مخزن](https://github.com/hooshgametalent/sirdadfar-saas-starter/actions)
- هر اجرا با تیک سبز (موفق) یا ضربدر قرمز (ناموفق) مشخص می‌شود. با کلیک روی آن، لاگ هر مرحله را می‌بینید.
- نشان (Badge) وضعیت CI در فایل `README.md` هم وضعیت آخرین اجرا را نشان می‌دهد.

### اجرای همین بررسی‌ها روی سیستم خودتان

```bash
npm install
npm run lint
npm run typecheck
npm run format:check
npm run build
docker build -t sirdadfar-saas-starter:local .
```

## رفع اشکال

| مشکل                                          | راه‌حل                                                                                                                 |
| --------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------- |
| `permission denied` هنگام اجرای docker        | دستور را با `sudo` اجرا کنید یا کاربر را به گروه docker اضافه کنید: `sudo usermod -aG docker $USER` و دوباره وارد شوید |
| `port is already allocated`                   | پورت دیگری در `.env` تنظیم کنید (مثلا `APP_PORT=8080`) و دوباره `docker compose up -d` بزنید                           |
| build با خطای کمبود حافظه متوقف می‌شود        | رم سرور را افزایش دهید یا فایل swap بسازید (مثلا ۲ گیگابایت)                                                           |
| دریافت ایمیج یا بسته‌ها با timeout خطا می‌دهد | بخش [نکات ویژه سرورهای داخل ایران](#نکات-ویژه-سرورهای-داخل-ایران) را ببینید                                            |
| صفحه از بیرون سرور باز نمی‌شود                | فایروال سرور و فایروال پنل ارائه‌دهنده را بررسی کنید و مطمئن شوید پورت باز است                                         |
| وضعیت کانتینر `unhealthy` است                 | لاگ‌ها را با `sudo docker compose logs web` بررسی کنید                                                                 |

ساخت swap دو گیگابایتی:

```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```
