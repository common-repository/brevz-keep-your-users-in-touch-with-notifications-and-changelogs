<?php
	header("Service-Worker-Allowed: /");
	header("Content-Type: application/javascript");
  header("X-Robots-Tag: noindex");
?>
importScripts("https://sw.static.brevz.io/sw.min.js");
