<?php
  $cache_expire = 31536000;
  header("Pragma: public");
  header("Cache-Control: maxage=".$cache_expire);
  header('Expires: '.gmdate('D, d M Y H:i:s', time()+$cache_expire).' GMT');
?>
<script src="//connect.facebook.net/es_ES/all.js"></script>