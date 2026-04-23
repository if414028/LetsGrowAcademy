<p align="center">
  <a href="https://letsgrowacademy.id" target="_blank">
    <img src="https://letsgrowacademy.id/images/coway_logo.png" width="400" alt="Lets Grow Academy Logo">
  </a>
</p>

<p align="center">
  <strong>Sales Performance & Management Dashboard</strong><br>
  Sistem monitoring penjualan dan manajemen tim sales Coway
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11-red" alt="Laravel Version">
  <img src="https://img.shields.io/badge/PHP-8.2-blue" alt="PHP Version">
  <img src="https://img.shields.io/badge/Status-In%20Development-yellow" alt="Project Status">
</p>

---

## 📊 About Coway Sales Dashboard

**Coway Sales Dashboard** adalah aplikasi web internal yang digunakan untuk memantau performa penjualan, struktur tim sales, dan pencapaian target secara terpusat dan real-time.

Aplikasi ini dirancang untuk mendukung operasional **Sales, Health Manager, dan Management Coway** dalam pengambilan keputusan berbasis data.

---

## 🎯 Tujuan Sistem

- Monitoring performa penjualan per sales & tim
- Manajemen struktur sales (SM, HM, HP)
- Tracking sales order & instalasi unit
- Laporan penjualan yang akurat & terukur
- Kontes & reward berbasis target penjualan

---

## ✨ Fitur Utama

### 🔐 User & Role Management
- Admin
- Sales Manager (SM)
- Health Manager (HM)
- Health Planner (HP)

Menggunakan **Spatie Laravel Permission** untuk role & permission.

---

### 🧑‍💼 Manajemen Sales
- Hierarki sales (SM → HM → HP)
- Relasi referral & atasan
- Status aktif / non-aktif sales

---

### 🧾 Sales Order Management
- Input data customer
- Tanggal key-in & instalasi
- Unit terjual
- Metode pembayaran (CC / POA)
- Status CCP & recurring

---

### 🏆 Contest & Reward
- Pembuatan kontes berdasarkan periode
- Target penjualan unit
- Perhitungan pemenang otomatis
- Upload banner kontes
- Pembatasan peserta berdasarkan role

---

### 📈 Reporting & Dashboard
- Total penjualan
- Penjualan per periode
- Ranking sales
- Statistik instalasi unit

---

## 🛠️ Tech Stack

- **Backend**: Laravel 11
- **Database**: MySQL
- **Auth**: Laravel Breeze
- **Role & Permission**: Spatie Laravel Permission
- **Frontend**: Blade + Vite
- **Version Control**: Git & GitHub

---

## ⚙️ Installation

### 1. Clone repository
```bash
git clone https://github.com/if414028/LetsGrowAcademy.git
cd LetsGrowAcademy
```

### 2. Install dependency
```bash
composer install
npm install
```

### 3. Setup environment
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Migrasi database
```bash
php artisan migrate --seed
```

### 5. Jalankan Aplikasi
```bash
php artisan serve
npm run dev
```
Akses di:
```bash
http://127.0.0.1:8000
```

### 5. Jalankan Aplikasi
```bash
php artisan serve
npm run dev
```

## 📂 Struktur Project
```bash
app/
├── Http/
├── Models/
├── Services/
resources/
├── views/
├── js/
routes/
├── web.php
database/
├── migrations/
```

## 🔐 Environment Variables
Pastikan file .env tidak di-push ke repository.
Gunakan .env.example sebagai template.

## 🤝 Contributing
Kontribusi sangat terbuka ✨
Silakan:
- Fork repository
- Buat branch baru
- Pull request dengan deskripsi jelas

## 📄 License
Project ini menggunakan lisensi MIT.

## 👨‍💻 Developer
Dikembangkan oleh Nesher Technology 
Powered by Laravel Framework
