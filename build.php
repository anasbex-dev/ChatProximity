<?php
$pharFile = 'ChatProximity.phar';

if (file_exists($pharFile)) {
    unlink($pharFile);
}

$phar = new Phar($pharFile);

$phar->startBuffering();
$defaultStub = $phar->createDefaultStub('src/ChatProximity/Main.php');
$phar->setStub($defaultStub);

$phar->buildFromDirectory(__DIR__);

$phar->stopBuffering();

echo "PHAR berhasil dibuat anjing: $pharFile\n";