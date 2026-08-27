# ادامه پیاده‌سازی Pixel-Perfect قالب Vuero در CMS

تو مسئول ادامه تبدیل قالب اصلی **Vuero 2.7.0 (Vue)** به **Laravel Blade + HTML + CSS + jQuery** در CMS اختصاصی Jobic هستی.

درخواست فعلی کاربر:

`$ARGUMENTS`

این دستور را یک درخواست کامل برای تحلیل، پیاده‌سازی، تست و تحویل نهایی در نظر بگیر؛ فقط گزارش یا نمونه اولیه تولید نکن. تا زمانی که صفحه واقعاً کامل و تست نشده، کار را تمام‌شده اعلام نکن.

## مفهوم دقیق «مو‌به‌مو» در این پروژه

هر زمان کاربر گفت «مو‌به‌مو»، «مثل Vuero»، «دقیق»، «صفحه را کامل بزن» یا عبارت مشابه، منظور این است:

- طراحی باید تا حد عملی **پیکسل‌به‌پیکسل** با نسخه اصلی Vuero یکسان باشد.
- تمام رفتارها باید **اکشن‌به‌اکشن** با مرجع بررسی شوند؛ صرفاً شبیه بودن ظاهر کافی نیست.
- قبل از کدنویسی، صفحه اصلی را در همه حالت‌ها باز کن و تمام دکمه‌ها، dropdownها، tabها، فرم‌ها، CTAها، pagination، empty state، loading state، hover، focus، scroll و انیمیشن‌ها را شخصاً اجرا کن.
- ساختار DOM، ترتیب محتوا، متن‌ها، اعداد، تصاویر، آیکن‌ها، فونت‌ها، اندازه‌ها، فاصله‌ها، border، radius، shadow، رنگ‌ها و alignment را از مرجع استخراج کن؛ چیزی را براساس سلیقه شخصی تغییر نده.
- حالت‌های Desktop، Tablet و Mobile و همچنین Light/Dark باید جداگانه مقایسه شوند.
- باز و بسته بودن منوی اصلی و تمام حالت‌های Sidebar/Sideblock/Navbar نباید صفحه را خراب کند یا overflow بسازد.
- کاربر نباید مجبور شود بعد از تحویل، خودش اشکالات واضح یا تفاوت‌های مرجع را پیدا کند. تست نهایی مسئولیت تو است.
- اگر در مرجع یک رفتار وجود ندارد، رفتار تزئینی یا UI جدید اختراع نکن. اگر رفتار مفیدی قابل پیاده‌سازی است، باید کاملاً هم‌سبک Vuero و بدون مغایرت باشد.
- عبارت «مو‌به‌مو» به معنی کپی کور Vue نیست؛ خروجی باید HTML/Blade تمیز، قابل نگهداری و در سطح یک CMS عمومی حرفه‌ای باشد.

## مسیرها و محیط پروژه

- پروژه Laravel:
  `C:\laragon\www\jobic`
- آدرس برنامه:
  `https://jobic.test`
- تمام صفحات دموی Vuero باید زیر این namespace بمانند:
  `https://jobic.test/vuero/...`
- پنل مدیریت Vuero:
  `https://jobic.test/vuero/admin`
- ورود دمو:
  - Email: `admin@jobic.test`
  - Password: `secret`
- سورس اصلی Vuero در ویندوز:
  `E:\Software\Template\vuero-270\template-vuero-v2.7.0`
- اگر checkout کمکی قبلی موجود بود:
  `/tmp/vuero-2.7.0`
  اما منبع اصلی و پایدار همان مسیر `E:` است.

کاربر صریحاً خواسته build و تست‌های اجرایی با **Windows PowerShell/CMD** انجام شوند، نه WSL. برای جست‌وجوی read-only می‌توانی از ابزارهای IDE استفاده کنی، اما build، PHP و browser QA را در Windows اجرا کن.

## ابزارهای Windows شناخته‌شده

- Node:
  `C:\laragon\bin\nodejs\node-v18\node.exe`
- npm:
  `C:\laragon\bin\nodejs\node-v18\npm.cmd`
- PHP:
  `C:\laragon\bin\php\php-8.5.8-nts-Win32-vs17-x64\php.exe`
- Chromium موجود برای Playwright:
  `C:\Users\majee\AppData\Local\ms-playwright\chromium-1228\chrome-win64\chrome.exe`

Build قابل‌اعتماد:

```powershell
Set-Location C:\laragon\www\jobic
C:\laragon\bin\nodejs\node-v18\node.exe node_modules\vite\bin\vite.js build
```

تست Vuero:

```powershell
Set-Location C:\laragon\www\jobic
C:\laragon\bin\php\php-8.5.8-nts-Win32-vs17-x64\php.exe -d extension=pdo_sqlite vendor\bin\phpunit tests\Feature\Vuero --no-progress
C:\laragon\bin\php\php-8.5.8-nts-Win32-vs17-x64\php.exe artisan view:cache
```

دو warning قدیمی زیر هنگام Vite build شناخته‌شده‌اند و مربوط به این کار نیستند، به شرط آنکه فقط همین دو مورد باشند:

- `champions-league.svg`
- `food-delivery/restaurant-sprite.png`

هر warning یا error دیگری را بررسی و رفع کن.

## معماری فعلی که باید حفظ شود

- Routeها: `routes/vuero.php`
- Controllerها: `app/Http/Controllers/Vuero/...`
- تنظیمات: `config/vuero.php`
- Layout اصلی: `resources/views/components/vuero/layouts/app.blade.php`
- صفحه‌ها: `resources/views/vuero/...`
- کامپوننت‌ها: `resources/views/components/vuero/...`
- CSS اصلی: `resources/css/vuero/app.css`
- CSSهای موضوعی: `resources/css/vuero/admin/*.css`
- JavaScript عمومی: `resources/js/vuero/admin/common.js`
- JavaScript اختصاصی صفحه‌ها: `resources/js/vuero/pages/admin/...`
- assetهای عمومی: `public/vuero/images/...`
- build خروجی: `public/vuero/build`
- تست‌ها: `tests/Feature/Vuero`

قواعد معماری:

- Vue را وارد خروجی Laravel نکن؛ هدف نهایی Blade/HTML/CSS/jQuery است.
- jQuery نصب‌شده پروژه را استفاده کن؛ یک نسخه جدید CDN یا نسخه تکراری اضافه نکن.
- منطق عمومی در فایل عمومی یا runtime مشترک قرار بگیرد.
- رفتار مخصوص هر صفحه در entry script جداگانه همان صفحه باشد.
- بخش‌های تکرارشونده را براساس مسئولیت محتوایی به Blade component تبدیل کن.
- component بیش‌ازحد عمومی یا component تک‌خطی بی‌دلیل نساز.
- داده نمایشی بزرگ را در Controller/config منظم نگه دار، نه به شکل HTML تکراری و آشفته.
- assetهای اصلی Vuero را کپی و زیر `public/vuero/...` گروه‌بندی کن؛ اگر asset واقعی موجود است placeholder یا بازطراحی دستی نساز.
- برای آیکن‌های برداری، ابتدا سیستم فعلی `x-vuero.icon` و `icon-sprite.blade.php` را توسعه بده.
- همه routeهای دمو باید زیر `/vuero` بمانند و به پروژه واقعی CMS نشت نکنند.
- در صورت اضافه شدن صفحه، لینک و active state منوی مربوطه را نیز کامل کن.
- HTML باید semantic و accessible باشد: `aria-expanded`، `aria-selected`، `aria-current`، focus management، keyboard action و label مناسب را رعایت کن.
- `prefers-reduced-motion` را برای انیمیشن‌ها رعایت کن.
- تغییرات نامرتبط کاربر را حذف یا overwrite نکن و از دستورهای مخرب Git استفاده نکن.
- worktree ممکن است dirty باشد؛ قبل از ویرایش وضعیت Git را بررسی و تغییرات موجود را حفظ کن.

## بخش‌هایی که تا اینجا پیاده‌سازی شده‌اند

این موارد در repository وجود دارند و باید به‌عنوان زیرساخت فعلی حفظ و reuse شوند:

- Login و authentication دموی Vuero
- Admin shell، topbar، mobile header و sidebar
- Activity، Notifications، Select Language، Search و Profile Settings
- Select Layout و حالت‌های Sidebar/Sideblock/Navbar
- circular quick-action menu هنگام scroll
- منوی Dashboards مطابق ترتیب اصلی Vuero
- Dashboardهای Personal، Finance، Banking، Business، Lifestyle، Ecommerce و Apps
- Maps V1/V2، Inbox، Messaging V1/V2 و Food Delivery
- Charts، Widgets، Wizard و Starter pages
- List View V1، V2 و V3

List Viewهای فعلی در این آدرس‌ها هستند:

- `/vuero/admin/layouts/list-view-v1`
- `/vuero/admin/layouts/list-view-v2`
- `/vuero/admin/layouts/list-view-v3`

زیرساخت reusable مربوط به List Viewها:

- `app/Http/Controllers/Vuero/Admin/LayoutController.php`
- `resources/views/components/vuero/layouts/list-toolbar.blade.php`
- `resources/views/components/vuero/layouts/list-placeholder.blade.php`
- `resources/views/components/vuero/layouts/list-pagination.blade.php`
- `resources/js/vuero/pages/admin/layouts/list-view-runtime.js`
- `resources/css/vuero/admin/layouts.css`

قبل از ساخت چیز جدید، componentها و runtimeهای موجود را بررسی و در صورت مناسب بودن reuse کن. پیاده‌سازی‌های قبلی را بی‌دلیل بازنویسی نکن.

## روند اجباری اجرای هر صفحه

### 1. کشف مرجع

- فایل route صفحه در Vuero را پیدا کن.
- فایل Vue اصلی و تمام componentهای nested آن را تا انتها بخوان.
- فایل data مربوطه، SCSS scoped/global، composableها و کتابخانه‌های JS استفاده‌شده را پیدا کن.
- screenshotهای رسمی light/dark موجود در `public/images/screenshots` را بررسی کن.
- صفحه مرجع را در مرورگر اجرا کن و تمام stateها و actionها را کلیک کن.
- یک inventory ذهنی یا موقت از اجزا، stateها و رفتارها بساز.

### 2. تطبیق معماری

- route مناسب زیر `vuero.admin` بساز یا route موجود را توسعه بده.
- Controller و داده‌ها را منظم نگه دار.
- صفحه Blade فقط composition باشد و قسمت‌های تکراری به componentهای موضوعی منتقل شوند.
- CSS همان صفحه در فایل موضوعی Vuero قرار بگیرد.
- entry script جدا برای همان صفحه بساز و runtime مشترک را reuse کن.
- entry جدید را در `vite.config.js` ثبت کن؛ فایل runtime/helper را به‌عنوان entry مستقل build نکن.

### 3. پیاده‌سازی ظاهر

- ابتدا desktop مرجع را با اندازه واقعی پیاده کن.
- سپس breakpointهای خود Vuero را از SCSS استخراج کن، نه breakpoint تصادفی.
- اندازه و نسبت تصویرها، card height، toolbar، typography و spacing را با screenshot مرجع اندازه‌گیری و تطبیق بده.
- Light/Dark را با tokenهای موجود پیاده کن؛ رنگ hard-code فقط وقتی در Vuero واقعاً ثابت است.
- فونت‌های فعلی Vuero یعنی Montserrat و Roboto را حفظ کن.

### 4. پیاده‌سازی رفتار

- Search باید live و case-insensitive باشد و empty state صحیح بدهد.
- شمارنده، singular/plural، pagination، tabs و active state باید sync باشند.
- Dropdown باید با click، outside click، Escape و keyboard درست کار کند.
- CTAها نباید dead باشند؛ رفتار مرجع را پیاده کن یا یک interaction هم‌سبک و معنادار ارائه بده.
- animation ورود/خروج، dropdown، tab indicator، modal/drawer و toast را مطابق مرجع بساز.
- هیچ لینک `#` یا دکمه بدون واکنش در محدوده صفحه تحویلی باقی نگذار، مگر در خود Vuero عمداً نمایشی باشد و آن را بررسی کرده باشی.

### 5. تست اجباری مرورگر

حداقل viewportهای زیر را تست کن:

- Desktop: `1440 × 900`
- Tablet portrait: `820 × 1180`
- Mobile: `390 × 844`

برای هرکدام:

- Light mode
- Dark mode
- منوی navigation بسته و باز
- حالت layout فعلی و حداقل خانواده‌های Sidebar/Sideblock/Navbar در صورت اثرگذاری روی صفحه
- initial state
- تمام tabها
- جست‌وجوی یک نتیجه و صفر نتیجه
- dropdown/CTA/modal/pagination و سایر اکشن‌ها
- scroll تا پایین صفحه و circular menu
- نبود horizontal overflow
- نبود خطای JavaScript، console، network 404 و asset شکسته

از هر حالت مهم screenshot full-page بگیر و کنار screenshot رسمی Vuero مقایسه کن. فقط گرفتن screenshot تست محسوب نمی‌شود؛ آن را واقعاً مشاهده و تفاوت‌ها را اصلاح کن.

### 6. Regression و تحویل

- برای route، محتوای اصلی، assetها، navigation، active state و HTML کلیدی Feature Test اضافه کن.
- تست صفحه جدید را اجرا کن.
- کل `tests/Feature/Vuero` را اجرا کن.
- `artisan view:cache` را اجرا کن.
- build production را در Windows اجرا کن.
- `git diff --check` را اجرا کن.
- پوشه‌ها و scriptهای موقت QA را حذف کن.
- در پاسخ نهایی آدرس صفحه، فایل‌های مهم، رفتارهای تست‌شده، تعداد تست/assertion و نتیجه build را کوتاه و دقیق گزارش کن.

## معیار توقف

تنها زمانی کار را تمام‌شده اعلام کن که:

- صفحه از نظر بصری در viewportهای تعیین‌شده با Vuero تطبیق داده شده باشد.
- تمام interactionهای مرجع و CTAهای محدوده کار اجرا و تست شده باشند.
- Light/Dark و responsive بدون شکست باشند.
- هیچ error جدیدی وجود نداشته باشد.
- build و regression test موفق باشند.

اگر مانع واقعی وجود داشت، ابتدا تمام راه‌های امن داخل repository و منبع Vuero را بررسی کن؛ سپس دقیقاً نام مانع، شواهد و کاری که برای رفع آن لازم است را گزارش بده. کمبود بررسی یا زمان را به‌عنوان مانع اعلام نکن.

حالا درخواست `$ARGUMENTS` را با همین استاندارد اجرا کن.
