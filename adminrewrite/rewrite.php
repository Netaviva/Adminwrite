<?php

require_once('constant.php');
require_once('rewrite.class.php');
$oRewrite = new Linko_Rewrite;
$sTarget = $oRewrite->rewrite();
require_once($sTarget);

?>