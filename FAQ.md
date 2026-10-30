# Flow-D: คำถามที่พบบ่อยและการแก้ไขปัญหา (Troubleshooting & FAQ)

เอกสารรวบรวมปัญหาที่พบบ่อย สาเหตุ และวิธีการแก้ไขอย่างละเอียด สำหรับการติดตั้ง ปรับแต่ง และดูแลรักษาระบบ **Flow-D** บนสภาพแวดล้อมจริง (โดยเฉพาะบน **Windows Server / IIS** และ **MariaDB**)

---

## สารบัญหัวข้อ (Table of Contents)

1. [หมวด IIS และการตั้งค่า Sub-directory / wwwroot](#1-หมวด-iis-และการตั้งค่า-sub-directory--wwwroot)
   - [FAQ 1.1: เข้าเว็บแล้วเจอ HTTP 404 Not Found ทันที](#faq-11-เข้าเว็บแล้วเจอ-http-404-not-found-ทันที)
   - [FAQ 1.2: อาการหน้าเว็บ Redirect ไปที่ `/public/index.php/login` แล้วขึ้น 404](#faq-12-อาการหน้าเว็บ-redirect-ไปที่-publicindexphplogin-แล้วขึ้น-404)
   - [FAQ 1.3: หน้าเว็บเปิดได้ แต่ CSS แตก ตัวหนังสือไม่จัดรูปแบบ รูปและไอคอนไม่ขึ้น](#faq-13-หน้าเว็บเปิดได้-แต่-css-แตก-ตัวหนังสือไม่จัดรูปแบบ-รูปและไอคอนไม่ขึ้น)
   - [FAQ 1.4: เข้าเมนูย่อยแล้วขึ้น 404 (URL Rewrite ทำงานไม่ถูกต้อง)](#faq-14-เข้าเมนูย่อยแล้วขึ้น-404-url-rewrite-ทำงานไม่ถูกต้อง)
2. [หมวดสิทธิ์ไฟล์และโฟลเดอร์ (Permissions & Error 500)](#2-หมวดสิทธิ์ไฟล์และโฟลเดอร์-permissions--error-500)
   - [FAQ 2.1: Error 500: `tempnam(): file created in the system's temporary directory`](#faq-21-error-500-tempnam-file-created-in-the-systems-temporary-directory)
   - [FAQ 2.2: หน้าจอค้าง หรือกดส่งต่อแล้วเกิด Error เขียน Cache/Session ไม่ได้](#faq-22-หน้าจอค้าง-หรือกดส่งต่อแล้วเกิด-error-เขียน-cachesession-ไม่ได้)
3. [หมวดความปลอดภัยและคีย์ระบบ (App Key & Protection)](#3-หมวดความปลอดภัยและคีย์ระบบ-app-key--protection)
   - [FAQ 3.1: Error 500: `Illuminate\Encryption\MissingAppKeyException`](#faq-31-error-500-illuminateencryptionmissingappkeyexception)
   - [FAQ 3.2: ป้องกันไม่ให้คนภายนอกพิมพ์ดาวน์โหลด `.env` หรือโฟลเดอร์ `.git` ได้อย่างไร](#faq-32-ป้องกันไม่ให้คนภายนอกพิมพ์ดาวน์โหลด-env-หรือโฟลเดอร์-git-ได้อย่างไร)
4. [หมวดฐานข้อมูล (MariaDB / MySQL / SQLite)](#4-หมวดฐานข้อมูล-mariadb--mysql--sqlite)
   - [FAQ 4.1: Error: `The server requested authentication method unknown to the client [auth_gssapi_client]`](#faq-41-error-the-server-requested-authentication-method-unknown-to-the-client-auth_gssapi_client)
   - [FAQ 4.2: ยังไม่ได้ติดตั้ง MariaDB หรือยังสร้างฐานข้อมูลไม่เสร็จ แต่อยากเปิดเว็บทดสอบทันที](#faq-42-ยังไม่ได้ติดตั้ง-mariadb-หรือยังสร้างฐานข้อมูลไม่เสร็จ-แต่อยากเปิดเว็บทดสอบทันที)
   - [FAQ 4.3: ภาษาไทยในฐานข้อมูลกลายเป็นเครื่องหมายคำถาม `???` หรือสี่เหลี่ยม](#faq-43-ภาษาไทยในฐานข้อมูลกลายเป็นเครื่องหมายคำถาม--หรือสี่เหลี่ยม)
5. [หมวดเวลาและระบบ (Timezone & Localization)](#5-หมวดเวลาและระบบ-timezone--localization)
   - [FAQ 5.1: เวลาที่บันทึกรับหนังสือเข้าช้ากว่าเวลาจริง 7 ชั่วโมง (UTC vs Asia/Bangkok)](#faq-51-เวลาที่บันทึกรับหนังสือเข้าช้ากว่าเวลาจริง-7-ชั่วโมง-utc-vs-asiabangkok)
6. [หมวดบัญชีผู้ใช้เริ่มต้น (Default Credentials)](#6-หมวดบัญชีผู้ใช้เริ่มต้น-default-credentials)
   - [FAQ 6.1: รหัสผ่านเริ่มต้นของผู้ดูแลระบบ (Admin) และเจ้าหน้าที่ (Staff) คืออะไร](#faq-61-รหัสผ่านเริ่มต้นของผู้ดูแลระบบ-admin-และเจ้าหน้าที่-staff-คืออะไร)
   - [FAQ 6.2: ลืมรหัสผ่านของ Admin บน Production Server ทำอย่างไร](#faq-62-ลืมรหัสผ่านของ-admin-บน-production-server-ทำอย่างไร)

---

## 1. หมวด IIS และการตั้งค่า Sub-directory / wwwroot

### FAQ 1.1: เข้าเว็บแล้วเจอ HTTP 404 Not Found ทันที
* **อาการ:** เปิด `https://domain.com/flow-d/` แล้วหน้าจอขาวขึ้นข้อความว่า *404 - File or directory not found.*
* **สาเหตุ:** ใน IIS Manager โฟลเดอร์ `flow-d` ยังเป็นเพียงโฟลเดอร์ธรรมดา ไม่ได้ถูกแปลงเป็น Application
* **วิธีแก้:**
  1. เปิด **IIS Manager** (`inetmgr`)
  2. ขยายเมนูทางซ้าย: `Sites` -> `Default Web Site` -> มองหาโฟลเดอร์สีเหลืองชื่อ **`flow-d`**
  3. คลิกขวาที่ `flow-d` แล้วเลือก **Convert to Application**
  4. ในช่อง **Physical path:** ให้เลือกชี้ไปที่โฟลเดอร์ `public` ดังนี้:
     ```text
     C:\inetpub\wwwroot\flow-d\public
     ```
  5. กด **OK** ไอคอนจะเปลี่ยนเป็นรูปลูกโลก/เว็บแอปพลิเคชัน

---

### FAQ 1.2: อาการหน้าเว็บ Redirect ไปที่ `/public/index.php/login` แล้วขึ้น 404
* **อาการ:** พิมพ์เข้า `https://domain.com/flow-d/` แล้ว URL เด้งเปลี่ยนเป็น `.../flow-d/public/index.php/login` แล้วเจอ Error 404
* **สาเหตุ:** ตัว Physical Path ของ Application ใน IIS ยังชี้ค้างอยู่ที่ Root `C:\inetpub\wwwroot\flow-d` (ไม่ได้ใส่ `\public` ต่อท้าย)
* **วิธีแก้:**
  1. ใน IIS Manager คลิกที่ชื่อแอป **`flow-d`** ทางซ้าย
  2. แถบขวามือสุด (Actions) คลิกที่ **`Basic Settings...`**
  3. แก้ไขช่อง **Physical path:** ให้มี `\public` ต่อท้าย:
     ```text
     C:\inetpub\wwwroot\flow-d\public
     ```
  4. กด **OK** แล้วลองกด Refresh หน้าเว็บใหม่อีกครั้ง

---

### FAQ 1.3: หน้าเว็บเปิดได้ แต่ CSS แตก ตัวหนังสือไม่จัดรูปแบบ รูปและไอคอนไม่ขึ้น
* **อาการ:** หน้า Login หรือ Dashboard เปิดได้แต่เป็นตัวอักษรเรียงลงมาทางซ้าย ไม่มีสีสันและเส้นขอบ
* **สาเหตุ:** เมื่อรันเว็บเป็นโฟลเดอร์ย่อย (Sub-directory) ตัวเบราว์เซอร์จะพยายามดึงไฟล์ CSS จาก Root `/build/...` แทนที่จะเป็น `/flow-d/build/...`
* **วิธีแก้:**
  1. เปิดไฟล์ `C:\inetpub\wwwroot\flow-d\.env`
  2. ตั้งค่าตัวแปร `APP_URL` และ `ASSET_URL` ให้ตรงกับชื่อโฟลเดอร์:
     ```env
     APP_URL=https://your-domain.com/flow-d
     ASSET_URL=/flow-d
     ```
  3. รันคำสั่งเคลียร์แคชใน Command Prompt:
     ```cmd
     cd C:\inetpub\wwwroot\flow-d
     php artisan config:cache
     php artisan view:clear
     ```

---

### FAQ 1.4: เข้าเมนูย่อยแล้วขึ้น 404 (URL Rewrite ทำงานไม่ถูกต้อง)
* **อาการ:** หน้าแรก `/flow-d/` เปิดได้ แต่พอกดลิงก์เมนู เช่น `/flow-d/history` หรือ `/flow-d/reports` กลับขึ้น Error 404
* **สาเหตุ:** เซิร์ฟเวอร์ IIS ยังไม่ได้ติดตั้ง **URL Rewrite Module** หรือไม่มีไฟล์ `web.config` อยู่ในโฟลเดอร์ `public`
* **วิธีแก้:**
  1. ติดตั้ง **IIS URL Rewrite Module 2.1** จากเว็บไซต์ Microsoft:  
     👉 [Microsoft IIS URL Rewrite Download](https://www.iis.net/downloads/microsoft/url-rewrite)
  2. ตรวจสอบว่ามีไฟล์ `C:\inetpub\wwwroot\flow-d\public\web.config` อยู่จริง (ระบบ Flow-D ได้เตรียมไฟล์นี้ไว้ให้แล้ว)
  3. ทำการ Restart IIS โดยเปิด CMD พิมพ์: `iisreset`

---

## 2. หมวดสิทธิ์ไฟล์และโฟลเดอร์ (Permissions & Error 500)

### FAQ 2.1: Error 500: `tempnam(): file created in the system's temporary directory`
* **อาการ:** เปิดเว็บแล้วขึ้นข้อความแจ้ง Error สีแดงว่า:
  ```text
  tempnam(): file created in the system's temporary directory
  in C:\inetpub\wwwroot\flow-d\vendor\laravel\framework\src\Illuminate\Filesystem\Filesystem.php:226
  ```
* **สาเหตุ:** Laravel พยายามคอมไพล์หน้าจอ Blade View ลงใน `storage\framework\views` แต่ User ประจำของ IIS (`IIS_IUSRS` และ `IUSR`) ไม่มีสิทธิ์เขียนไฟล์ (Write Permission) ลงในโฟลเดอร์ดังกล่าว
* **วิธีแก้:**
  เปิด **Command Prompt (คลิกขวาเลือก Run as Administrator)** แล้วรันชุดคำสั่งด้านล่างนี้:
  ```cmd
  cd C:\inetpub\wwwroot\flow-d

  :: สร้างโฟลเดอร์ที่จำเป็นให้ครบถ้วน
  mkdir storage\framework\views 2>nul
  mkdir storage\framework\sessions 2>nul
  mkdir storage\framework\cache 2>nul
  mkdir storage\framework\cache\data 2>nul
  mkdir storage\logs 2>nul
  mkdir bootstrap\cache 2>nul

  :: ให้สิทธิ์เขียนไฟล์แก่ผู้ใช้ IIS ทั้งหมด
  icacls "storage" /grant "IIS_IUSRS":(OI)(CI)M /T
  icacls "storage" /grant "IUSR":(OI)(CI)M /T
  icacls "bootstrap\cache" /grant "IIS_IUSRS":(OI)(CI)M /T
  icacls "bootstrap\cache" /grant "IUSR":(OI)(CI)M /T
  ```

---

### FAQ 2.2: หน้าจอค้าง หรือกดส่งต่อแล้วเกิด Error เขียน Cache/Session ไม่ได้
* **สาเหตุ:** เกิดจากการให้สิทธิ์ไม่ครอบคลุมโฟลเดอร์ย่อย `storage\framework\sessions` หรือ `storage\framework\cache`
* **วิธีแก้:** ใช้คำสั่ง `icacls` เดียวกับ FAQ 2.1 ซึ่งมีพารามิเตอร์ `/T` เพื่อถ่ายทอดสิทธิ์ (Inherit Permissions) ไปยังโฟลเดอร์ย่อยทั้งหมด

---

## 3. หมวดความปลอดภัยและคีย์ระบบ (App Key & Protection)

### FAQ 3.1: Error 500: `Illuminate\Encryption\MissingAppKeyException`
* **อาการ:** หน้าเว็บขึ้น Error สีแดง: `No application encryption key has been specified.`
* **สาเหตุ:** ในไฟล์ `.env` ค่าของ `APP_KEY=` ว่างเปล่าอยู่ ทำให้ระบบไม่สามารถเข้ารหัสรหัสผ่านหรือสร้าง Session สำหรับ Login ได้
* **วิธีแก้:**
  เปิด Command Prompt ในโฟลเดอร์โปรเจกต์แล้วรันคำสั่งสร้าง Key อัตโนมัติ:
  ```cmd
  cd C:\inetpub\wwwroot\flow-d
  php artisan key:generate
  ```

---

### FAQ 3.2: ป้องกันไม่ให้คนภายนอกพิมพ์ดาวน์โหลด `.env` หรือโฟลเดอร์ `.git` ได้อย่างไร
* **คำอธิบาย:** ระบบ Flow-D ได้ทำการคอนฟิกกฎความปลอดภัยไว้ในไฟล์ `public/web.config` และ `web.config` ของ Root โปรเจกต์ให้แล้ว ดังนี้:
  ```xml
  <security>
      <requestFiltering>
          <hiddenSegments>
              <add segment=".env" />
              <add segment=".git" />
          </hiddenSegments>
      </requestFiltering>
  </security>
  ```
  หากมีผู้ไม่หวังดีพยายามเข้าผ่าน `https://domain.com/flow-d/.env` ตัวเซิร์ฟเวอร์ IIS จะตอบกลับเป็น **`403 Forbidden: Access is denied.`** ทันที

---

## 4. หมวดฐานข้อมูล (MariaDB / MySQL / SQLite)

### FAQ 4.1: Error: `The server requested authentication method unknown to the client [auth_gssapi_client]`
* **อาการ:** หน้าเว็บแจ้งเตือน QueryException:
  ```text
  SQLSTATE[HY000] [2054] The server requested authentication method unknown to the client [auth_gssapi_client]
  (Connection: mariadb, Host: 127.0.0.1, Port: 3306, Database: laravel)
  ```
* **สาเหตุ:** บัญชี `root` ของ MariaDB บน Windows ถูกตั้งค่าให้ล็อกอินผ่าน Windows Domain Credentials (`auth_gssapi_client`) ซึ่ง PHP Driver ไม่รองรับการยืนยันตัวตนแบบนี้
* **วิธีแก้:**
  สร้างฐานข้อมูลและสร้าง User ใหม่ใน MariaDB โดยกำหนดให้ใช้รหัสผ่านแบบ Standard Password Authentication:
  1. เปิด MariaDB Console (หรือ HeidiSQL / Command Line):
     ```sql
     -- 1. สร้าง Database สำหรับ Flow-D
     CREATE DATABASE flow_d CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

     -- 2. สร้าง User ใหม่พร้อมรหัสผ่านปกติ (ไม่ใช้ auth_gssapi)
     CREATE USER 'flow_d_user'@'127.0.0.1' IDENTIFIED BY 'FlowD@Pass2026';
     GRANT ALL PRIVILEGES ON flow_d.* TO 'flow_d_user'@'127.0.0.1';
     FLUSH PRIVILEGES;
     ```
  2. แก้ไขไฟล์ `.env` ของ Flow-D:
     ```env
     DB_CONNECTION=mariadb
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=flow_d
     DB_USERNAME=flow_d_user
     DB_PASSWORD=FlowD@Pass2026
     ```
  3. รัน Migration เพื่อสร้างตาราง:
     ```cmd
     php artisan migrate --seed --force
     php artisan config:cache
     ```

---

### FAQ 4.2: ยังไม่ได้ติดตั้ง MariaDB หรือยังสร้างฐานข้อมูลไม่เสร็จ แต่อยากเปิดเว็บทดสอบทันที
* **วิธีแก้:** สลับมาใช้ **SQLite** ชั่วคราวได้ทันทีโดยไม่ต้องติดตั้ง Database Server เพิ่มเติม:
  1. แก้ไขไฟล์ `.env`:
     ```env
     DB_CONNECTION=sqlite
     ```
  2. สร้างไฟล์เปล่าสำหรับเก็บข้อมูล:
     ```cmd
     cd C:\inetpub\wwwroot\flow-d
     type nul > database\database.sqlite
     icacls "database" /grant "IIS_IUSRS":(OI)(CI)M /T
     icacls "database" /grant "IUSR":(OI)(CI)M /T
     ```
  3. รันสร้างตารางและข้อมูลเริ่มต้น:
     ```cmd
     php artisan migrate --seed --force
     ```

---

### FAQ 4.3: ภาษาไทยในฐานข้อมูลกลายเป็นเครื่องหมายคำถาม `???` หรือสี่เหลี่ยม
* **สาเหตุ:** การเข้ารหัส (Encoding) ของฐานข้อมูลหรือตารางไม่ได้เป็น `utf8mb4`
* **วิธีแก้:**
  ตรวจสอบว่าไฟล์ `.env` และการสร้างฐานข้อมูลใช้ Collation `utf8mb4_unicode_ci`:
  ```sql
  ALTER DATABASE flow_d CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
  ```

---

## 5. หมวดเวลาและระบบ (Timezone & Localization)

### FAQ 5.1: เวลาที่บันทึกรับหนังสือเข้าช้ากว่าเวลาจริง 7 ชั่วโมง (UTC vs Asia/Bangkok)
* **อาการ:** บันทึกหนังสือเวลา 15:00 น. แต่ระบบแสดงเวลาเป็น 08:00 น.
* **สาเหตุ:** ค่าเริ่มต้นของ Laravel คือ UTC (+00:00)
* **วิธีแก้:**
  1. ตรวจสอบไฟล์ `.env` ต้องมีบรรทัด:
     ```env
     APP_TIMEZONE=Asia/Bangkok
     ```
  2. ใน `config/app.php` ระบบ Flow-D ได้ตั้งค่าให้รองรับไว้อยู่แล้ว:
     ```php
     'timezone' => env('APP_TIMEZONE', 'Asia/Bangkok'),
     ```
  3. ล้างแคชคอนฟิกบน Server:
     ```cmd
     php artisan config:cache
     ```

---

## 6. หมวดบัญชีผู้ใช้เริ่มต้น (Default Credentials)

### FAQ 6.1: รหัสผ่านเริ่มต้นของผู้ดูแลระบบ (Admin) และเจ้าหน้าที่ (Staff) คืออะไร
ข้อมูลบัญชีเริ่มต้นที่ถูกสร้างจาก `php artisan migrate --seed`:

| บทบาท (Role) | ชื่อผู้ใช้ (Username) | รหัสผ่าน (Password) | สิทธิ์การเข้าถึง |
|---|---|---|---|
| **ผู้ดูแลระบบ (Admin)** | `admin` | `admin123` | จัดการทุกส่วน, ดูรายงาน, จัดการเส้นทาง/ผู้ใช้, Rollback |
| **เจ้าหน้าที่ (Staff)** | `staff` | `staff123` | รับหนังสือเข้า, ส่งต่อขั้นตอน, พิมพ์รายงาน |

> [!CAUTION]
> เมื่อนำระบบขึ้นใช้งานจริง กรุณาเข้าเมนูโปรไฟล์หรือระบบจัดการผู้ใช้เพื่อเปลี่ยนรหัสผ่านเริ่มต้นทันที

---

### FAQ 6.2: ลืมรหัสผ่านของ Admin บน Production Server ทำอย่างไร
* **วิธีแก้:**
  เปิด Command Prompt ที่ Server แล้วสั่งรีเซ็ตรหัสผ่านผ่าน Artisan Tinker ได้ทันที:
  ```cmd
  cd C:\inetpub\wwwroot\flow-d
  php artisan tinker
  ```
  พิมพ์คำสั่ง PHP ดังนี้ (เปลี่ยน `newpassword123` เป็นรหัสผ่านใหม่ที่ต้องการ):
  ```php
  $user = App\Models\User::where('username', 'admin')->first();
  $user->password = Hash::make('newpassword123');
  $user->save();
  exit;
  ```

---

## สรุปคำสั่งสำคัญสำหรับการอัปเดตระบบ (Quick Reference)

เมื่อมีการ `git pull` โค้ดใหม่มายัง Server ทุกครั้ง ควรดำเนินการตามขั้นตอนดังนี้:

```cmd
cd C:\inetpub\wwwroot\flow-d

:: 1. ดึงโค้ดล่าสุด
git pull

:: 2. รัน Migration (กรณีที่มีตารางใหม่)
php artisan migrate --force

:: 3. เคลียร์และคอมไพล์แคชประสิทธิภาพ
php artisan config:cache
php artisan route:cache
php artisan view:clear
```

