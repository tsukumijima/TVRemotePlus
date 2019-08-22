## Synopsis

Functions to control a Chromecast with PHP using a reverse engineered Castv2 protocol. Provides ability to control a Chromecast either locally or remotely from a server.

## Code Example

Custom Receiver - Launch App

```php
// Create Chromecast object and give IP and Port
$cc = new Chromecast("217.63.63.259","8009");

// Launch the Chromecast App
$cc->launch("87087D10");

// Wait for the application to be ready
$response = "";
while (!preg_match("/Application status is ready/s",$response)) {
        $response = $cc->getCastMessage();
}

// Connect to the Application
$cc->connect();

// Send the URL
$cc->sendMessage("urn:x-cast:com.chrisridings.piccastr","http://distribution.bbb3d.renderfarming.net/video/mp4/bbb_sunflower_1080p_30fps_normal.mp4");

// Keep the connection alive with heartbeat
while (1==1) {
        $cc->pingpong();
	sleep(10);
}
```

Custom Receiver - Connect to existing running app

```php
$cc = new Chromecast("217.63.63.259","8009");
$cc->cc_connect();
$cc->getStatus();
$cc->connect();
```

Or use the Default Media Player

```php
require_once("Chromecast.php");

// Create Chromecast object and give IP and Port
$cc = new Chromecast("217.63.63.259","8009");

$cc->DMP->play("https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4","BUFFERED","video/mp4",true,0);
$cc->DMP->UnMute();
$cc->DMP->SetVolume(1);
sleep(5);
$cc->DMP->pause();
print_r($cc->DMP->getStatus());
sleep(5);
$cc->DMP->restart();
sleep(5);
$cc->DMP->seek(100);
sleep(5);
$cc->DMP->SetVolume(0.5);
sleep(15);
$cc->DMP->SetVolume(1); // Turn the volume back up
$cc->DMP->Mute();
sleep(20);
$cc->DMP->UnMute();
sleep(5);
$cc->DMP->Stop();
```

Experimental - Scan for Chromecast devices using mdns

```php
require_once("Chromecast.php");
print_r(Chromecast::scan());
```

## NOTES

The default port a Chromecast uses is 8009.

If sending content to your home Chromecast from an internet server, you will probably need to enable port forwarding on your router. In which case, use the IP your ISP has assigned you and the port you've chosen to forward.

## TODO

This is only the functional beginnings of this project. For example: notable things yet to do are:

1. Handle binary payloads when encoding to protobuf
2. Protobuf decoding to message objects
3. Handle ping/pings properly

Feel free to help out!
