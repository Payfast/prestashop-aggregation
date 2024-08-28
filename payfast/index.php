<?php

/*
 * index.php
 *
 * Copyright (c) 2024 Payfast (Pty) Ltd
 *
 * @author     App Inlet
 * @version    1.2.3
 * @date       2024/08/21
 *
 * @link       https://payfast.io/integration/plugins/prestashop/
 */

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

header("Location: ../");
exit;
