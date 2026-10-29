# Changelog
All notable changes to the **Flow-D** project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.3.1] - 2026-10-29

### Changed
- **UI Uniformity**: ปรับขนาดความสูงของ Search Input, Route Select, และ Action Buttons ให้มีขนาดเท่ากันสม่ำเสมอที่ `h-11` (44px) ทั้งในหน้า Dashboard และ History
- **Navbar & Navigation Labels**: ปรับเปลี่ยนข้อความเมนูด้านบนจาก "Line Tracking" เป็น **"ค้นหาสถานะหนังสือ"** และปุ่มติดตามสถานะเป็น **"📱 หน้าจอติดตามสถานะ →"**
- **Dashboard Cleanliness**: นำข้อความแจ้งเตือนทางเทคนิคและกล่องกฎระเบียบ (Business Rules) ออกจากหน้า Dashboard ซ้าย เพื่อให้พื้นที่ทำงานสะอาดตาและโฟกัสที่การป้อนข้อมูล
- **Login Screen Polish**: นำกล่องข้อมูล Credential ทดสอบออกจากหน้าจอ Login เพื่อความเรียบร้อยก่อนขึ้นใช้งานจริง

---

## [1.3.0] - 2026-10-29

### Added
- **Executive Reports Module (`/reports`)**: ระบบรายงานสรุปผลการดำเนินงานและสถิติการหมุนเวียนเอกสาร
  - ตัวกรองช่วงเวลา: วันนี้ (Today), สัปดาห์นี้ (This Week), เดือนนี้ (This Month), ปีนี้ (This Year), และกำหนดช่วงวันที่เอง (Custom Date Range)
  - สรุปภาพรวม KPI: เอกสารทั้งหมด, เอกสารเสร็จสิ้น, เอกสารค้างดำเนินการ, อัตราความสำเร็จ (%) และเวลาเฉลี่ยในการประมวลผล
  - ตารางแจกแจงตามเส้นทาง (Route Breakdown Table): สถิติแยกตามประเภทงานพร้อม Progress Bar
  - ทะเบียนคุมการเคลื่อนไหวเอกสาร (Movement Ledger): แสดงรายละเอียดเลขที่หนังสือ, รอบการเดิน, ขั้นตอนปัจจุบัน, ผู้ถือเอกสาร และระยะเวลาค้าง
- **A4 Landscape Print & PDF Export**: ออกแบบ CSS `@media print` สำหรับพิมพ์รายงานหรือส่งออกเป็น PDF ขนาดกระดาษ A4 แนวนอน พร้อมช่องลงนามผู้จัดทำและผู้บริหาร
- **Report Feature Tests**: เพิ่มชุดทดสอบอัตโนมัติ `tests/Feature/ReportTest.php` ครอบคลุมการกรองข้อมูล สิทธิ์การเข้าถึง และการคำนวณสถิติ

---

## [1.2.0] - 2026-10-29

### Changed
- **Single Smart Action Button (Q1-B, Q2-A)**: ปรับปรุงปุ่มปฏิบัติการในหน้า Dashboard ให้เหลือเพียงปุ่มเดียว เพื่อป้องกันความสับสนและการกดข้ามขั้นตอน
  - ขั้นตอนระหว่างทาง: แสดงปุ่มสีน้ำเงิน `→ ส่งต่อ: [ชื่อขั้นตอนถัดไป]`
  - ขั้นตอนสุดท้าย: เปลี่ยนเป็นปุ่มสีเขียว `✓ ส่งต่อเสร็จสิ้น: [ชื่อขั้นตอนสุดท้าย]`
- **Step 1 Intake Normalization**: ปรับให้ทุก Route กำหนดให้ Step 1 เป็น `1. บันทึกรับหนังสือเข้า` โดยอัตโนมัติผ่าน `Route::normalizeSteps()` เพื่อความสอดคล้องของระบบ
- **Mobile Tracking Enhancements**: เพิ่มการแสดงผลข้อมูล `👤 ผู้รับหนังสือเข้า:` พร้อมวันที่และเวลาอย่างชัดเจนใน Summary Card และ Step 1 Timeline บนหน้าจอ Mobile / LINE LIFF

---

## [1.1.0] - 2026-10-29

### Added
- **Typo Correction Feature (1-C, 2-B, 3-A)**: ระบบแก้ไขเลขที่หนังสือที่บันทึกผิดพลาดได้ทันทีจากหน้า Dashboard พร้อมบันทึกประวัติการแก้ไขลง `activity_logs`
- **Void Intake Feature**: ฟังก์ชันสำหรับผู้ดูแลระบบ (Admin) ในการยกเลิกการรับหนังสือเข้า กรณีที่ยิงบาร์โค้ดซ้ำหรือรับผิดฉบับ
- **Activity Log Audit Trail**: บันทึกประวัติการทำธุรกรรมความปลอดภัย เช่น การแก้ไขเลข, การยกเลิกรับเข้า, และการถอยขั้นตอน พร้อม IP Address และ Device ID

---

## [1.0.0] - 2026-10-29

### Added
- **High-Velocity Dual-Pane Dashboard**: หน้าจอแยกสองฝั่ง ซ้ายบันทึกรับเข้า ขวารายการที่ต้องดำเนินการ รองรับคีย์บอร์ดชอร์ตคัต `Alt+N`, `/` และการยิงบาร์โค้ดแบบ 1-Click
- **Pessimistic Concurrency Locking**: ใช้ `lockForUpdate()` ป้องกัน Race Condition และการกดซ้ำซ้อน (Anti Double-Click)
- **Multi-Round Document Lifecycle**: รองรับการนำเอกสารเดิมกลับมาเดินรอบใหม่ (Round Re-entry) และการบันทึกสถานะตีกลับ (Returned)
- **Same-Day Admin Rollback**: สิทธิ์ผู้ดูแลระบบสามารถถอยขั้นตอนเอกสารกลับได้ในวันเดียวกันหากมีการส่งผิด
- **Mobile & LINE LIFF Tracking Companion**: หน้าจอติดตามสถานะหนังสือแบบเรียลไทม์ผ่านสมาร์ตโฟน โดยไม่ต้องเข้าสู่ระบบ
- **Device & Workstation Tracking**: ระบบติดตามอุปกรณ์ที่ใช้งานผ่าน UUID Cookie Device Token
