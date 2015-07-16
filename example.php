<?php

require 'GoogleTranslate.php';

$translate = new GoogleTranslater;
echo $translate->translateText('Translate me');