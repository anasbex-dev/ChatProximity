# ✉️ ChatProximity — Advanced Proximity Chat for PMMP

**ChatProximity** adalah plugin PocketMine-MP yang menghadirkan sistem chat berbasis jarak (proximity chat) dengan fitur lengkap namun tetap ringan. Cocok untuk server Survival, SMP, Roleplay, hingga server kecil yang ingin pengalaman chat lebih realistis.

---

## ✨ Features
- 🔊 **Proximity Chat** — pesan hanya terdengar oleh pemain dalam radius tertentu.  
- 🤫 **Whisper Mode** (`@w`) — jangkauan kecil, cocok untuk bisikan.  
- 📣 **Shout Mode** (`@s`) — jangkauan besar untuk teriak / panggilan jauh.  
- 🎛️ **Toggle System** — pemain bisa ON/OFF fitur proximity melalui `/togglechat`.  
- 🌍 **Per-World Settings** — radius & status bisa diatur tiap dunia.  
- 🎨 **Custom Chat Format** — format pesan dapat diatur bebas (warna, prefix, dll).  
- 🏷️ **Distance Indicator** — chat bisa menambah tag jika terlalu jauh.  
- 🔐 **Admin Bypass** — admin bisa chat global tanpa batas radius.  
- ⚙️ **Config Lengkap & Mudah Dipakai**  
- 🪶 **Super Ringan** — tidak memakai task berat, aman untuk RAM kecil.

---

## 📦 Installation
1. Download plugin ini (ChatProximity).
2. Extract / upload folder **ChatProximity** ke:

/plugins/

3. Jalankan ulang server PocketMine-MP.
4. Plugin otomatis membuat file `config.yml`.

---

## 📝 Commands

| Command | Deskripsi |
|--------|-----------|
| `/togglechat` | Enable/disable proximity chat untuk pemain |

---

## 🛑 Chat Modes

### **1. Normal Chat**
Radius default (misalnya 20 block) sesuai config.

### **2. Whisper**

@w <pesan>

Radius kecil (misalnya 6 block).

### **3. Shout**

@s <pesan>

Radius besar (misalnya 40 block).

---

## 🔐 Permissions

| Permission | Deskripsi | Default |
|------------|-----------|---------|
| `chatprox.bypass` | Admin/global chat tanpa radius | OP |
| `chatprox.toggle` | Izin memakai /togglechat | true |

---

## ⚙️ Config (config.yml)

Contoh config:

radius: 20 whisper-radius: 6 shout-radius: 40 hide-self: false

world-settings: default: enabled: true radius: 20

format: normal: "§e[Nearby] §f{player}: §7{msg}" whisper: "§b[Whisper] §f{player}: §7{msg}" shout: "§c[Shout] §f{player}: §7{msg}"

distance-tag: near: "" far: " §8(too far)"

---

## 🧩 Compatibility
- PocketMine-MP **5.x**
- Support multi-world
- Tidak bentrok dengan plugin chat lain yang tidak memodifikasi event recipients

---

## 📄 License
Plugin ini dirilis di bawah **Apache License 2.0**, yang memberikan kebebasan untuk:
- Menggunakan
- Memodifikasi
- Mendistribusikan
- Menggabungkan dalam proyek lain  

Dengan syarat mencantumkan kredit dan mengikuti aturan lisensi.

---

## 👑 Author
**ChatProximity** dibuat oleh **AnasBex**.

---

## ❤️ Support Project
Kalau suka plugin ini, jangan ragu untuk:
- Kasih bintang ⭐ di repo,
- Request fitur baru,
- Atau minta gw bikin plugin tambahan lainnya.

---
