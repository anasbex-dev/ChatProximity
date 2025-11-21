<?php

namespace ChatProximity;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\world\World;
use pocketmine\utils\TextFormat;
use pocketmine\scheduler\ClosureTask;

class Main extends PluginBase implements Listener {

    // Basic settings
    private int $radius;
    private int $whisperRadius;
    private int $shoutRadius;
    private bool $hideSelf;
    private array $worldSettings = [];
    private array $format = [];
    private array $distanceTag = [];
    
    // Command & Permission settings
    private bool $enableToggleCommand;
    private string $togglePermission;
    private string $bypassPermission;
    private string $adminPermission;
    
    // Advanced features
    private bool $enableCrossWorld;
    private bool $showDistance;
    private bool $enableRanges;
    private array $chatRanges = [];
    private bool $enableAntiSpam;
    private int $messageCooldown;
    
    // Performance optimization
    private array $toggle = [];
    private array $playerCache = [];
    private int $cacheTime = 3;
    private int $lastCacheClear = 0;
    private array $lastMessageTime = [];
    
    // System
    private bool $loaded = false;
    private int $configVersion = 2;

    public function onEnable(): void {
        try {
            $this->saveDefaultConfig();
            $this->checkConfigVersion();
            
            $cfg = $this->getConfig();

            // Load basic config dengan validasi
            $this->radius = max(1, (int) $cfg->get("radius", 20));
            $this->whisperRadius = max(1, (int) $cfg->get("whisper-radius", 6));
            $this->shoutRadius = max(1, (int) $cfg->get("shout-radius", 40));
            $this->hideSelf = (bool) $cfg->get("hide-self", false);
            $this->worldSettings = $cfg->get("world-settings", []);
            $this->format = $cfg->get("format", []);
            $this->distanceTag = $cfg->get("distance-tag", []);
            
            // Load command & permission settings
            $this->enableToggleCommand = (bool) $cfg->get("enable-toggle-command", true);
            $this->togglePermission = (string) $cfg->get("toggle-permission", "chatproximity.toggle");
            $this->bypassPermission = (string) $cfg->get("bypass-permission", "chatproximity.bypass");
            $this->adminPermission = (string) $cfg->get("admin-permission", "chatproximity.admin");
            
            // Load advanced features
            $this->enableCrossWorld = (bool) $cfg->get("enable-cross-world", false);
            $this->showDistance = (bool) $cfg->get("show-distance", false);
            $this->enableRanges = (bool) $cfg->get("enable-chat-ranges", false);
            $this->chatRanges = $cfg->get("chat-ranges", []);
            $this->enableAntiSpam = (bool) $cfg->get("anti-spam.enabled", true);
            $this->messageCooldown = max(0, (int) $cfg->get("anti-spam.cooldown", 2));

            $this->getServer()->getPluginManager()->registerEvents($this, $this);
            
            // Auto-clear cache setiap 10 detik
            $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(): void {
                $this->playerCache = [];
                $this->lastCacheClear = time();
            }), 200); // 10 detik

            // Tandai plugin sudah siap
            $this->loaded = true;
            $this->getLogger()->info("ChatProximity v2.0 loaded successfully!");
            $this->getLogger()->info("Features: CrossWorld=" . ($this->enableCrossWorld ? "Yes" : "No") . 
                                   ", AntiSpam=" . ($this->enableAntiSpam ? "Yes" : "No") .
                                   ", ChatRanges=" . ($this->enableRanges ? "Yes" : "No"));
            $this->getLogger()->info("Permissions: ToggleCmd=" . ($this->enableToggleCommand ? "Yes" : "No") .
                                   ", TogglePerm=" . $this->togglePermission .
                                   ", BypassPerm=" . $this->bypassPermission);
            
        } catch (\Throwable $e) {
            $this->getLogger()->error("Failed to load ChatProximity: " . $e->getMessage());
            $this->getLogger()->debug($e->getTraceAsString());
        }
    }

    /** Toggle chat command dengan configurable permission */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if(strtolower($command->getName()) === "togglechat"){
            if(!$sender instanceof Player){
                $sender->sendMessage("§cIn-game only.");
                return true;
            }

            // Check if toggle command is enabled in config
            if(!$this->enableToggleCommand){
                $sender->sendMessage("§cThis command is disabled by server configuration.");
                return true;
            }

            // Use configurable permission
            if(!$sender->hasPermission($this->togglePermission)){
                $sender->sendMessage("§cYou don't have permission to use this command.");
                $this->getLogger()->debug("Player {$sender->getName()} denied access to togglechat - missing permission: {$this->togglePermission}");
                return true;
            }

            try {
                $name = $sender->getName();
                $currentState = $this->getToggleState($sender);
                $newState = !$currentState;
                
                $this->setToggleState($sender, $newState);

                $message = $newState 
                    ? "§a● Proximity chat enabled" 
                    : "§c● Proximity chat disabled";
                
                $sender->sendMessage($message);
                $this->getLogger()->debug("Player {$sender->getName()} toggled proximity chat: " . ($newState ? "ON" : "OFF"));

                return true;
            } catch (\Throwable $e) {
                $this->getLogger()->error("Error in togglechat command: " . $e->getMessage());
                $sender->sendMessage("§cAn error occurred while toggling chat.");
                return true;
            }
        }
        return false;
    }

    /** Handle chat dengan configurable permission - FIXED untuk PMMP 5.x */
    public function onPlayerChat(PlayerChatEvent $e): void {
        try {
            $p = $e->getPlayer();
            $name = $p->getName();
            $msg = $e->getMessage();

            // Pastikan plugin sudah load
            if(!$this->loaded){
                $p->sendMessage("§cChatProximity: Plugin is still loading...");
                $e->cancel();
                return;
            }

            // Validasi player dan world
            if(!$p->isOnline() || $p->isClosed()) {
                $e->cancel();
                return;
            }

            $world = $p->getWorld();
            if($world === null || !$world->isLoaded()) {
                $p->sendMessage("§cYour world is not loaded properly.");
                $e->cancel();
                return;
            }

            // Pastikan posisi player valid
            $pPos = $p->getPosition();
            if($pPos === null){
                $e->cancel();
                return;
            }

            // Toggle check
            if(!$this->getToggleState($p)) return;

            // Configurable permission bypass
            if($p->hasPermission($this->bypassPermission)) {
                $this->getLogger()->debug("Player {$p->getName()} bypassed proximity chat using permission: {$this->bypassPermission}");
                return;
            }

            // Anti-spam check
            if($this->enableAntiSpam && $this->checkSpam($p)) {
                $e->cancel();
                return;
            }

            // World settings
            $worldName = $world->getFolderName();
            $worldSetting = $this->worldSettings[$worldName] ?? ["enabled" => true, "radius" => $this->radius];
            if(!isset($worldSetting["enabled"]) || !$worldSetting["enabled"]) {
                $e->cancel();
                return;
            }
            $radiusDefault = $worldSetting["radius"] ?? $this->radius;

            // Deteksi tipe chat dan custom ranges
            $chatData = $this->detectChatType($msg, $radiusDefault);
            $radius = $chatData['radius'];
            $msg = $chatData['message'];
            $format = $chatData['format'];
            $chatType = $chatData['type'];

            // Dapatkan recipients
            if($this->enableCrossWorld) {
                $recipients = $this->getRecipientsCrossWorld($p, $pPos, $radius);
            } else {
                $recipients = $this->getRecipientsInRadius($p, $pPos, $radius, $world);
            }

            // Jika tidak ada yang dengar, minimal sender sendiri
            if(count($recipients) === 0) {
                $recipients[] = $p;
                $this->sendNoOneHearsYou($p);
            }

            // Format message dengan placeholder lengkap
            $formattedMessage = $this->formatMessage($p, $format, $msg, $recipients, $chatType);

            // ✅ FIX: Untuk PMMP 5.x - Kirim pesan manual ke recipients dan cancel event
            $this->sendMessageToRecipients($formattedMessage, $recipients, $p);
            
            // Cancel event asli agar tidak broadcast ke semua player
            $e->cancel();

            // Log chat activity untuk debugging
            $this->getLogger()->debug("Chat handled - Player: {$p->getName()}, Type: {$chatType}, Radius: {$radius}, Recipients: " . count($recipients));

        } catch (\Throwable $e) {
            $this->getLogger()->error("Error in chat handling: " . $e->getMessage());
            $this->getLogger()->debug($e->getTraceAsString());
        }
    }

    /**
     * ✅ NEW: Kirim pesan ke recipients secara manual (PMMP 5.x compatible)
     */
    private function sendMessageToRecipients(string $formattedMessage, array $recipients, Player $sender): void {
        foreach($recipients as $recipient) {
            if($recipient instanceof Player && $recipient->isOnline() && !$recipient->isClosed()) {
                $recipient->sendMessage($formattedMessage);
            }
        }
        
        // Juga log ke console untuk debugging
        $this->getLogger()->info(strip_tags($formattedMessage));
    }

    /**
     * Deteksi tipe chat (whisper, shout, custom ranges)
     */
    private function detectChatType(string $message, int $defaultRadius): array {
        $result = [
            'radius' => $defaultRadius,
            'message' => $message,
            'format' => $this->format["normal"] ?? "{player}: {msg}",
            'type' => 'normal'
        ];

        // Custom ranges detection
        if($this->enableRanges) {
            foreach($this->chatRanges as $prefix => $settings) {
                if(str_starts_with($message, $prefix . " ")) {
                    $result['radius'] = $settings['radius'] ?? $defaultRadius;
                    $result['message'] = substr($message, strlen($prefix) + 1);
                    $result['format'] = $settings['format'] ?? $this->format["normal"];
                    $result['type'] = $prefix;
                    return $result;
                }
            }
        }

        // Default types
        if(str_starts_with($message, "@w ")){
            $result['radius'] = $this->whisperRadius;
            $result['message'] = substr($message, 3);
            $result['format'] = $this->format["whisper"] ?? "§7{player} whispers: {msg}";
            $result['type'] = 'whisper';
        } elseif(str_starts_with($message, "@s ")){
            $result['radius'] = $this->shoutRadius;
            $result['message'] = substr($message, 3);
            $result['format'] = $this->format["shout"] ?? "§6{player} shouts: {msg}";
            $result['type'] = 'shout';
        }

        return $result;
    }

    /**
     * Format message dengan placeholder lengkap
     */
    private function formatMessage(Player $sender, string $format, string $message, array $recipients, string $chatType): string {
        $placeholders = [
            "{player}" => $sender->getName(),
            "{display_name}" => $sender->getDisplayName(),
            "{msg}" => $message,
            "{world}" => $sender->getWorld()->getFolderName(),
            "{x}" => (int) $sender->getPosition()->getX(),
            "{y}" => (int) $sender->getPosition()->getY(),
            "{z}" => (int) $sender->getPosition()->getZ(),
            "{time}" => date("H:i"),
            "{chat_type}" => $chatType,
            "{listeners}" => count($recipients)
        ];

        // Support for Pocketmine formatting codes
        $formatted = str_replace(array_keys($placeholders), array_values($placeholders), $format);
        $formatted = TextFormat::colorize($formatted);

        return $formatted;
    }

    /**
     * Mendapatkan daftar penerima dalam radius dengan CACHE
     */
    private function getRecipientsInRadius(Player $sender, $senderPos, int $radius, World $world): array {
        $cacheKey = $sender->getName() . ":" . $world->getFolderName() . ":" . $radius;
        
        // Check cache
        if(isset($this->playerCache[$cacheKey]) && time() - $this->lastCacheClear < $this->cacheTime) {
            return $this->playerCache[$cacheKey];
        }
        
        $recipients = [];
        $senderX = $senderPos->getX();
        $senderZ = $senderPos->getZ();
        
        try {
            $players = $world->getPlayers();
            
            foreach($players as $player) {
                if(!$this->isValidRecipient($sender, $player)) continue;

                $playerPos = $player->getPosition();
                if($playerPos === null) continue;

                // Optimized distance calculation
                $distance = $this->calculateQuickDistance($senderPos, $playerPos);
                if($distance <= $radius) {
                    $recipients[] = $player;
                }
            }
            
            // Store in cache
            $this->playerCache[$cacheKey] = $recipients;
            
        } catch (\Throwable $e) {
            $this->getLogger()->error("Error getting recipients: " . $e->getMessage());
        }

        return $recipients;
    }

    /**
     * Mendapatkan recipients cross-world
     */
    private function getRecipientsCrossWorld(Player $sender, $senderPos, int $radius): array {
        $recipients = [];
        
        try {
            foreach($this->getServer()->getWorldManager()->getWorlds() as $world) {
                foreach($world->getPlayers() as $player) {
                    if(!$this->isValidRecipient($sender, $player)) continue;

                    $playerPos = $player->getPosition();
                    if($playerPos === null) continue;

                    // Untuk cross-world, hanya hitung distance jika world sama
                    if($player->getWorld()->getFolderName() === $sender->getWorld()->getFolderName()) {
                        $distance = $this->calculateQuickDistance($senderPos, $playerPos);
                        if($distance <= $radius) {
                            $recipients[] = $player;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->getLogger()->error("Error in cross-world recipients: " . $e->getMessage());
        }

        return $recipients;
    }

    /**
     * Validasi recipient
     */
    private function isValidRecipient(Player $sender, Player $player): bool {
        if(!$player instanceof Player || !$player->isOnline() || $player->isClosed()) {
            return false;
        }

        // Skip jika player sama dan hideSelf aktif
        if($this->hideSelf && $player->getName() === $sender->getName()) {
            return false;
        }

        return true;
    }

    /**
     * Optimized distance calculation
     */
    private function calculateQuickDistance($pos1, $pos2): float {
        try {
            $dx = $pos1->getX() - $pos2->getX();
            $dz = $pos1->getZ() - $pos2->getZ();
            return sqrt($dx * $dx + $dz * $dz); // Horizontal distance only (lebih cepat)
        } catch (\Throwable $e) {
            return PHP_FLOAT_MAX; // Return nilai besar jika error
        }
    }

    /**
     * Anti-spam protection
     */
    private function checkSpam(Player $player): bool {
        if(!$this->enableAntiSpam) return false;
        
        $name = $player->getName();
        $currentTime = microtime(true);
        
        if(isset($this->lastMessageTime[$name])) {
            $timeDiff = $currentTime - $this->lastMessageTime[$name];
            if($timeDiff < $this->messageCooldown) {
                $remaining = $this->messageCooldown - $timeDiff;
                $player->sendMessage("§cPlease wait " . number_format($remaining, 1) . "s before sending another message");
                return true;
            }
        }
        
        $this->lastMessageTime[$name] = $currentTime;
        return false;
    }

    /**
     * Send no one hears you message
     */
    private function sendNoOneHearsYou(Player $player): void {
        $messages = [
            "§7(§8Nobody near hears you§7)",
            "§7(§8Your voice echoes in the void§7)",
            "§7(§8No one is around to listen§7)",
            "§7(§8Silence answers your call§7)"
        ];
        
        $player->sendMessage($messages[array_rand($messages)]);
    }

    /**
     * Configuration version check
     */
    private function checkConfigVersion(): void {
        $currentVersion = $this->getConfig()->get("config-version", 1);
        
        if($currentVersion < $this->configVersion) {
            $this->getLogger()->warning("Config version outdated! (Current: v{$currentVersion}, Latest: v{$this->configVersion})");
            $this->getLogger()->info("Backing up old config and generating new one...");
            
            // Backup old config
            if(file_exists($this->getDataFolder() . "config.yml")) {
                rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "config_backup_v{$currentVersion}.yml");
            }
            
            // Generate new config
            $this->saveResource("config.yml", true);
            $this->reloadConfig();
        }
    }

    /**
     * Safe config reload
     */
    public function reloadConfig(): void {
        try {
            parent::reloadConfig();
            $cfg = $this->getConfig();

            // Reload semua settings
            $this->radius = max(1, (int) $cfg->get("radius", 20));
            $this->whisperRadius = max(1, (int) $cfg->get("whisper-radius", 6));
            $this->shoutRadius = max(1, (int) $cfg->get("shout-radius", 40));
            $this->hideSelf = (bool) $cfg->get("hide-self", false);
            $this->worldSettings = $cfg->get("world-settings", []);
            $this->format = $cfg->get("format", []);
            $this->distanceTag = $cfg->get("distance-tag", []);
            
            // Reload command & permission settings
            $this->enableToggleCommand = (bool) $cfg->get("enable-toggle-command", true);
            $this->togglePermission = (string) $cfg->get("toggle-permission", "chatproximity.toggle");
            $this->bypassPermission = (string) $cfg->get("bypass-permission", "chatproximity.bypass");
            $this->adminPermission = (string) $cfg->get("admin-permission", "chatproximity.admin");
            
            // Advanced features
            $this->enableCrossWorld = (bool) $cfg->get("enable-cross-world", false);
            $this->showDistance = (bool) $cfg->get("show-distance", false);
            $this->enableRanges = (bool) $cfg->get("enable-chat-ranges", false);
            $this->chatRanges = $cfg->get("chat-ranges", []);
            $this->enableAntiSpam = (bool) $cfg->get("anti-spam.enabled", true);
            $this->messageCooldown = max(0, (int) $cfg->get("anti-spam.cooldown", 2));
            
            // Clear cache saat reload config
            $this->playerCache = [];
            
            $this->getLogger()->info("ChatProximity configuration reloaded successfully!");
            
        } catch (\Throwable $e) {
            $this->getLogger()->error("Failed to reload config: " . $e->getMessage());
        }
    }

    /**
     * Toggle state management
     */
    private function getToggleState(Player $player): bool {
        return $this->toggle[$player->getName()] ?? true;
    }

    private function setToggleState(Player $player, bool $state): void {
        $this->toggle[$player->getName()] = $state;
    }

    /**
     * API methods untuk plugin lain
     */
    public function getChatRadius(Player $player): int {
        $world = $player->getWorld()->getFolderName();
        $worldSetting = $this->worldSettings[$world] ?? ["enabled" => true, "radius" => $this->radius];
        return $worldSetting["radius"] ?? $this->radius;
    }

    public function setPlayerToggle(Player $player, bool $state): void {
        $this->setToggleState($player, $state);
    }

    public function getNearbyPlayers(Player $player, int $radius = null): array {
        $radius = $radius ?? $this->getChatRadius($player);
        return $this->getRecipientsInRadius($player, $player->getPosition(), $radius, $player->getWorld());
    }

    /**
     * Get permission settings (for other plugins)
     */
    public function getPermissionSettings(): array {
        return [
            'toggle_command' => $this->enableToggleCommand,
            'toggle_permission' => $this->togglePermission,
            'bypass_permission' => $this->bypassPermission,
            'admin_permission' => $this->adminPermission
        ];
    }
}