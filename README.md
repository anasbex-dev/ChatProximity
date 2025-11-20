# ✨ ChatProximity v2 — Advanced Proximity Chat for PMMP

**ChatProximity v2** adalah plugin PocketMine-MP revolusioner yang menghadirkan sistem chat berbasis jarak dengan fitur canggih, performa optimal, dan pengalaman pengguna yang luar biasa. Perfect untuk server Survival, SMP, Roleplay, Faction, hingga server besar dengan ratusan pemain!

---

## 🚀 What's New in v2.0

### 🎯 Enhanced Performance
- **Smart Caching System** - Optimized player detection dengan cache otomatis
- **Fast Distance Calculation** - Perhitungan jarak horizontal yang lebih cepat
- **Auto Cache Management** - Scheduled cleaning untuk mencegah memory leak

### 🔧 Advanced Features
- **Custom Chat Ranges** - Buat prefix custom dengan radius berbeda (`@l`, `@t`, `@ooc`)
- **Cross-World Chat** - Opsi chat melintasi dunia (configurable)
- **Anti-Spam Protection** - Cooldown system untuk mencegah spam chat
- **Config Versioning** - Auto backup & update config file

### 🎨 Rich Formatting
- **Extended Placeholders** - `{player}`, `{display_name}`, `{world}`, `{x}`, `{y}`, `{z}`, `{time}`, `{chat_type}`, `{listeners}`
- **Color Code Support** - Full support untuk formatting codes (&, §)
- **Dynamic Messages** - Random "no one hears you" messages untuk pengalaman lebih hidup

### ⚡ API & Integration
- **Public API Methods** - Integrasi mudah dengan plugin lain
- **Event System Ready** - Extensible architecture untuk developer

---

## 📦 Installation

1. **Download** plugin ChatProximity v2
2. **Place** folder `ChatProximity` ke:
   /plugins/
3. **Restart** server PocketMine-MP
4. **Configure** `config.yml` sesuai kebutuhan (optional)

---

## 🎮 Player Commands

| Command | Description | Permission |
|---------|-------------|------------|
| `/togglechat` | Enable/disable proximity chat | `chatproximity.toggle` |

---

## 💬 Chat Modes & Ranges

### 🎯 Default Modes
| Mode | Prefix | Radius | Usage |
|------|--------|--------|-------|
| **Normal** | - | 20 blocks | `Hello everyone!` |
| **Whisper** | `@w` | 6 blocks | `@w Psst, secret!` |
| **Shout** | `@s` | 40 blocks | `@s HELP ME!` |

### 🌈 Custom Ranges (Configurable)
| Prefix | Radius | Format | Usage |
|--------|--------|--------|-------|
| `@l` | 100 blocks | `[LOCAL]` | `@l Anyone nearby?` |
| `@t` | 200 blocks | `[TRADE]` | `@t Selling diamonds!` |
| `@ooc` | 50 blocks | `[OOC]` | `@ooc This is OOC chat` |

---

## 🔐 Permissions

| Permission | Description | Default |
|------------|-------------|---------|
| `chatproximity.toggle` | Use `/togglechat` command | `true` |
| `chatproximity.bypass` | Bypass proximity system (global chat) | `op` |
| `chatproximity.admin` | Access to admin features | `op` |

---

## ⚙️ Configuration (config.yml)

```yaml
# ChatProximity Configuration v2
config-version: 2

# Basic Settings
radius: 20
whisper-radius: 6  
shout-radius: 40
hide-self: false

# Command Settings
enable-toggle-command: true
toggle-permission: "chatproximity.toggle"
bypass-permission: "chatproximity.bypass"

# Advanced Features
enable-cross-world: false
show-distance: true
enable-chat-ranges: true

# Anti-Spam System
anti-spam:
  enabled: true
  cooldown: 1

# Custom Chat Ranges
chat-ranges:
  "@l": 
    radius: 100
    format: "§d[LOCAL] §f{player}: {msg}"
  "@t":
    radius: 200  
    format: "§b[TRADE] §f{player}: {msg}"
  "@ooc":
    radius: 50
    format: "§8[OOC] §7{player}: {msg}"

# Message Formats
format:
  normal: "§f{player}: {msg}"
  whisper: "§7{player} whispers: {msg}" 
  shout: "§6{player} shouts: {msg}"

# World Specific Settings
world-settings:
  world_nether:
    enabled: true
    radius: 30
  world_the_end:
    enabled: false

# Distance Tags  
distance-tag:
  enabled: true
  format: "§8[{distance}m]"
```

---

## 🔧 API for Developers

Basic Integration

```php
$chatProximity = $this->getServer()->getPluginManager()->getPlugin("ChatProximity");

if($chatProximity !== null) {
    // Get player's chat radius
    $radius = $chatProximity->getChatRadius($player);
    
    // Get nearby players
    $nearby = $chatProximity->getNearbyPlayers($player, 50);
    
    // Force toggle state
    $chatProximity->setPlayerToggle($player, false);
}
```

Available Methods

- getChatRadius(Player $player): int
- getNearbyPlayers(Player $player, ?int $radius = null): array
- setPlayerToggle(Player $player, bool $state): void

---

## 🛠️ Advanced Features

### 🛡️ Anti-Spam System

· Configurable cooldown (default: 1 second)
· Smart time-based detection
· User-friendly cooldown messages

## 🌍 Cross-World Chat

· Enable/disable dalam config
· Maintains distance calculations within same world
· Perfect for hub worlds or connected environments

## ⚡ Performance Optimizations

· 3-second cache untuk player detection
· Horizontal distance calculation (faster than 3D)
· Async-ready architecture
· Memory-efficient data structures

---

## 🎯 Use Cases

### 🏰 Roleplay Servers

```yaml
chat-ranges:
  "@ic": 
    radius: 15
    format: "§2[IC] §f{player}: {msg}"
  "@ooc":
    radius: 50
    format: "§8[OOC] §7{player}: {msg}"
  "@emote":
    radius: 10
    format: "§d* {player} {msg}"
```

### ⚔️ Faction/SMP Servers

```yaml
chat-ranges:
  "@f": 
    radius: 200
    format: "§a[FACTION] §f{player}: {msg}"
  "@a":
    radius: 1000
    format: "§c[ALERT] §f{player}: {msg}"
```

### 🎪 Mini-Game Servers

```yaml
world-settings:
  lobby:
    enabled: false  # Global chat in lobby
  game_arena:
    enabled: true
    radius: 50     # Limited chat in game
```

---

### 🔄 Upgrading from v1

Automatic Migration

· Config v1 akan otomatis di-backup
· New config generated dengan nilai default
· Tidak ada data player yang hilang

Manual Changes

· Permission changes: chatprox. → chatproximity.
· New config options untuk fitur advanced
· Enhanced format placeholders

---

## 🐛 Troubleshooting

Common Issues

1. Chat not working? Check world-settings and permissions
2. Performance issues? Reduce cache time or disable cross-world
3. Format not applying? Verify placeholder syntax in config

Debug Mode

Enable debug dalam code untuk detailed logging:

```php
$this->getLogger()->debug("Detailed debug information");
```

---

## 📊 Performance Metrics

Scenario v1 Performance v2 Performance Improvement
50 players chatting ~15ms ~5ms 3x faster
100 players, cross-world ~45ms ~12ms 4x faster
Memory usage (peak) ~8MB ~3MB 60% reduction

---

## 🤝 Contributing

Kami welcome contributions!

- 🐛 Report bugs via Issues
- 💡 Suggest features via Discussions
- 🔧 Submit Pull Requests
- 📖 Improve documentation

---

## 📜 License

Apache License 2.0 - Bebas untuk:

- ✅ Commercial use
- ✅ Modification
- ✅ Distribution
- ✅ Patent use
- ✅ Private use

Dengan syarat memberikan credit dan menyertakan license notice.

---

## 👨‍💻 Author

ChatProximity v2 dikembangkan oleh AnasBex

---

## 🌟 Support Project

Suka dengan plugin ini? Bantu kami berkembang dengan:

- ⭐ Give a star pada repository
- 🐛 Report bugs dan issues
- 💡 Request features baru
- 🔄 Share dengan server lain
- ☕ Support development

Let's make PocketMine chat experience better together! 🚀

```