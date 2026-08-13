<?php
$s='Ø®Ø±ÛŒØ¯';
foreach (['Windows-1252','ISO-8859-1'] as $enc) {
  $bytes = mb_convert_encoding($s, $enc, 'UTF-8');
  echo $enc, ': ', $bytes, "\n";
}
