<?php
$properties = array(
    array(
        'name' => 'page_paymentForm',
        'desc' => 'id страницы формы оплаты',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
    ),array(
        'name' => 'page_callback',
        'desc' => 'id страницы приёма отвера сервера оплаты',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
    ),array(
        'name' => 'page_success',
        'desc' => 'id страницы успешной оплаты',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
    ),array(
        'name' => 'page_fail',
        'desc' => 'id страницы неуспешной оплаты',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
    ),array(
        'name' => 'test_mode',
        'desc' => 'Тестовый режим оплаты',
        'type' => 'combo-boolean',
        'options' => '',
        'value' => '1',
    ),array(
        'name' => 'secret_key',
        'desc' => 'Секретный ключ в системе Interkassa',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
    ),array(
        'name' => 'test_key',
        'desc' => 'Тестовый ключ в системе Interkassa',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
    ),array(
        'name' => 'currency',
        'desc' => 'Валюта магазина(3-х буквенный код валюты)',
        'type' => 'textfield',
        'options' => '',
        'value' => 'RUB',
    ),array(
        'name' => 'id_cashbox',
        'desc' => 'Идентификатор кассы',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
    ),array(
        'name' => 'api_enable',
        'desc' => 'Использовать API',
        'type' => 'combo-boolean',
        'options' => '',
        'value' => '1',
    ),array(
        'name' => 'api_id',
        'desc' => 'Идентификатор API в системе Interkassa',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
    ),array(
        'name' => 'api_key',
        'desc' => 'Ключ API в системе Interkassa',
        'type' => 'textfield',
        'options' => '',
        'value' => '',
    )
);

return $properties;