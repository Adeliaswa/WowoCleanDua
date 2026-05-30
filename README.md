# 🗑️ WowoClean

> **Enterprise REST API untuk Sistem Manajemen Kontainer Limbah B3**

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Built with Laravel](https://img.shields.io/badge/Built_with-Laravel%2012-FF2D20.svg)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-%5E8.2-777BB4.svg)](https://www.php.net/)

---

## 🌟 Tentang WowoClean

**WowoClean** adalah platform yang membantu perusahaan melacak pembuangan limbah B3 (Bahan Berbahaya dan Beracun). Sistem ini dibangun sebagai *production-ready* REST API dengan autentikasi JWT, role-based authorization, API Gateway, dan dokumentasi OpenAPI/Swagger.

Project ini merupakan pengembangan lanjutan dari UTP (Ujian Tengah Praktikum) yang sebelumnya menggunakan data dummy, kini telah bermigrasi ke database relasional penuh.

---

## ✨ Fitur Utama

- **JWT Authentication** — Login, profile, dan logout berbasis JSON Web Token
- **Role-Based Authorization** — Hak akses berbeda untuk role `admin` dan `user`
- **API Gateway** — Semua request melewati satu pintu utama `/api/v1/gateway`
- **Database Integration** — Data kontainer dan tracking log tersimpan di database relasional
- **CRUD Kontainer** — Lengkap dengan validasi kondisional dan filter pencarian
- **Tracking Logs** — Relasi One-to-Many antara kontainer dan log perjalanan
- **Swagger Documentation** — Dokumentasi interaktif via `/api/documentation`
- **API Versioning** — Semua endpoint menggunakan prefix `/api/v1`
- **Frontend Client** — Halaman HTML + Axios terintegrasi dengan JWT

---

## 🛠️ Tech Stack

### Backend
- **Framework**: Laravel 12
- **Authentication**: tymon/jwt-auth (JWT Token)
- **Database**: MySQL
- **Documentation**: l5-swagger (OpenAPI 3.0)

### Frontend
- **HTML + Vanilla JS**
- **Axios** (via CDN) untuk HTTP request
- **localStorage** untuk menyimpan JWT token

---

## 📋 Prerequisites

- PHP 8.2 atau lebih tinggi
- Composer
- MySQL
- Git

---

## 🚀 Cara Menjalankan

### 1. Clone Repository

```bash
git clone https://github.com/Adeliaswa/WowoCleanDua.git
cd WowoCleanDua
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

Edit `.env` sesuaikan database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wowoclean
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Migrasi Database

```bash
php artisan migrate
php artisan db:seed
```

### 5. Jalankan Server

```bash
php artisan serve
```

Akses aplikasi di: [http://localhost:8000](http://localhost:8000)
Dokumentasi Swagger: [http://localhost:8000/api/documentation](http://localhost:8000/api/documentation)

---

## 🔐 Autentikasi & Otorisasi

### Akun Default (Seeder)

| Role  | Email | Password |
|-------|-------|----------|
| admin | admin@wowoclean.com | password |
| user  | user@wowoclean.com | password |

### Alur Autentikasi
POST /api/v1/login        → Dapatkan JWT token
GET  /api/v1/profile      → Lihat profil (butuh token)
POST /api/v1/logout       → Hapus token

### Hak Akses per Role

| Endpoint | Admin | User |
|----------|-------|------|
| GET /containers | ✅ | ✅ |
| POST /containers | ✅ | ❌ 403 |
| PATCH /containers/{id} | ✅ | ❌ 403 |
| DELETE /containers/{id} | ✅ | ❌ 403 |

---

## 🌐 API Endpoint

Semua endpoint diakses melalui prefix `/api/v1/gateway/`

### Autentikasi
POST   /api/v1/login
GET    /api/v1/profile
POST   /api/v1/logout

### Kontainer
GET    /api/v1/gateway/containers
POST   /api/v1/gateway/containers
GET    /api/v1/gateway/containers/{id}
PATCH  /api/v1/gateway/containers/{id}
DELETE /api/v1/gateway/containers/{id}
GET    /api/v1/gateway/containers/search?type=&min_weight=

### Tracking Logs
GET    /api/v1/gateway/containers/{id}/logs

---

## 📄 Lisensi

Project ini dibuat untuk keperluan **Ujian Akhir Praktikum (UAP) Teknologi Integrasi Sistem TI-C**.

---

<div align="center">

**Dibuat dengan ❤️ oleh Adelia Swastika Dewi**

</div>
