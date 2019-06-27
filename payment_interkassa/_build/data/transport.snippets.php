<?php
$snippets = array();

$snippets[1]= $modx->newObject('modSnippet');
$snippets[1]->fromArray(array(
    'id' => 1,
    'name' => 'interkassa',
    'description' => '',
    'static' => 0,
    'static_file' => '',
    'snippet' => "return require MODX_CORE_PATH . 'components/payment_interkassa/interkassa.inc.php';",
),'',true,true);
$properties = include $sources['data'].'properties/properties.interkassa.php';
$snippets[1]->setProperties($properties);
unset($properties);

return $snippets;