<?php

namespace ChatProximity;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;

class Main extends PluginBase implements Listener {

    private int $radius;
    private int $whisperRadius;
    private int $shoutRadius;
    private bool $hideSelf;
    private array $toggle = [];
    private array $worldSettings = [];
    private array $format = [];
    private array $distanceTag = [];

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $cfg = $this->getConfig();

        $this->radius = (int) $cfg->get("radius", 20);
        $this->whisperRadius = (int) $cfg->get("whisper-radius", 6);
        $this->shoutRadius = (int) $cfg->get("shout-radius", 40);
        $this->hideSelf = (bool) $cfg->get("hide-self", false);
        $this->worldSettings = $cfg->get("world-settings", []);
        $this->format = $cfg->get("format", []);
        $this->distanceTag = $cfg->get("distance-tag", []);

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onCommandPreprocess(PlayerCommandPreprocessEvent $e): void {
        $msg = strtolower($e->getMessage());
        $p = $e->getPlayer();

        if ($msg === "/togglechat") {
            if (!$p->hasPermission("chatprox.toggle")) {
                $p->sendMessage("§cYou don't have permission.");
                $e->cancel();
                return;
            }

            if (!isset($this->toggle[$p->getName()])) $this->toggle[$p->getName()] = true;
            $this->toggle[$p->getName()] = !$this->toggle[$p->getName()];

            $p->sendMessage($this->toggle[$p->getName()]
                ? "§aProximity chat enabled"
                : "§cProximity chat disabled"
            );
            $e->cancel();
        }
    }

    public function onPlayerChat(PlayerChatEvent $e): void {
        $p = $e->getPlayer();
        $name = $p->getName();
        $msg = $e->getMessage();

        // Toggle array safe
        if (!isset($this->toggle[$name])) $this->toggle[$name] = true;
        if ($this->toggle[$name] === false) return;

        // Bypass permission
        if ($p->hasPermission("chatprox.bypass")) return;

        // World settings safe
        $world = $p->getWorld()->getDisplayName();
        $worldSetting = $this->worldSettings[$world] ?? ["enabled" => true, "radius" => $this->radius];
        if (!$worldSetting["enabled"]) return;
        $radiusDefault = $worldSetting["radius"] ?? $this->radius;

        // Detect whisper & shout
        $radius = $radiusDefault;
        $format = $this->format["normal"] ?? "{player}: {msg}";

        if (str_starts_with($msg, "@w ")) {
            $radius = $this->whisperRadius;
            $msg = substr($msg, 3);
            $format = $this->format["whisper"] ?? "{player}: {msg}";
        } elseif (str_starts_with($msg, "@s ")) {
            $radius = $this->shoutRadius;
            $msg = substr($msg, 3);
            $format = $this->format["shout"] ?? "{player}: {msg}";
        }

        $recipients = [];

        foreach ($p->getWorld()->getPlayers() as $pl) {
            if (!$pl->isOnline()) continue;
            $dist = $pl->distance($p);
            if ($dist <= $radius) {
                if ($this->hideSelf && $pl->getName() === $name) continue;
                $recipients[] = $pl;
            }
        }

        // Ensure recipients safe
        if (count($recipients) === 0) $recipients[] = $p;

        // Replace format
        $format = str_replace(["{player}", "{msg}"], [$name, $msg], $format);

        $e->setFormat($format);
        $e->setRecipients($recipients);

        // Optional message if nobody else hears
        if (count($recipients) === 1 && $recipients[0] === $p) {
            $p->sendMessage("§7(§8Nobody near hears you§7)");
        }
    }
}