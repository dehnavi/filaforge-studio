<?php
$autoload=getenv('FILAFORGE_TEST_AUTOLOAD') ?: dirname(__DIR__).'/vendor/autoload.php';
$loader=require $autoload;
$loader->addPsr4('FilaForge\\Studio\\',dirname(__DIR__).'/src',true);
$loader->addPsr4('FilaForge\\Studio\\Tests\\',__DIR__,true);
