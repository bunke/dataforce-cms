<?php

addLog('', -1, 0);
session_destroy();

$msg = $word[$ALANG]['exit_ok'] ?? 'Ви успішно вийшли із системи!';
echo "<body style=\"background: url('_layout/images/back-pattern.png') repeat scroll 0 0 rgba(0,0,0,0);\">
<img src=\"img/exit.jpg\" border=\"0\"/><br/>
<strong style=\"font-family:arial;font-size:21px;\">" . $msg . "</strong>
<script>setTimeout(function(){document.location='login.php';}, 1000);</script>
</body>";
