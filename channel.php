<?php
/**
 * channel.php
 *
 * User: robert.reimi@gmail.com
 * Date: 2/5/13
 *
 */

  $cache_expire = 60*60*24*365;
  header("Pragma: public");
  header("Cache-Control: maxage=".$cache_expire);
  header('Expires: '.gmdate('D, d M Y H:i:s', time()+$cache_expire).' GMT');
?>

<script src="//connect.facebook.net/es_ES/all.js"></script>