<?php
/*
* Платежный модуль Интеркасса (сниппет)
* Совместим с MODX REVO 2.5.0 (версии 2.х.х тоже должны быть совместимы, возможно с некоторыми поправками в коде)!
* Модуль требует наличие плагина ShopKeeper 3.0, 2.0 тоже будет работать, только нужно сменить переменные и вызовы $modx функций!
* Модуль разработан www.gateon.net
* www@smartbyte.pro
* version 1.0
*/
$modelpath = $modx->getOption('core_path') . 'components/shopkeeper3/model/';
$modx->addPackage( 'shopkeeper3', $modelpath );

$merchant_id = trim($modx->getOption('MERCHANT_ID',$scriptProperties,null));
$currency_code = trim($modx->getOption('CURRENCY_CODE',$scriptProperties,null));
if($currency_code == NULL){
	$currency_code = 'RUB';
}
$method_sign = $modx->getOption('TYPE_SIGNATURE',$scriptProperties,null);
$secret_key = $modx->getOption('SECRET_KEY',$scriptProperties,null);
$test_key = $modx->getOption('TEST_KEY',$scriptProperties,null);
$test_mode = $modx->getOption('TEST_MODE',$scriptProperties,null);
$action_url = 'https://sci.interkassa.com/';
$method = 'POST';

//Optional propities
$success_url = $modx->getOption('URL_SUCCES',$scriptProperties,null);
$fail_url = $modx->getOption('URL_FAIL',$scriptProperties,null);


if (isset($_REQUEST['payment']) && $_REQUEST['payment'] == 'interkassa') {
	$payment_form = $modx->getOption('PAYMENT_FORM', $scriptProperties, null);
	$modx->sendRedirect($payment_form);
}

if (isset($scriptProperties['action'])) {
	switch ($scriptProperties['action']) {
		case 'fail':

			$order_id = (isset($_REQUEST['ik_pm_no'])) ? $_REQUEST['ik_pm_no'] : null;
			if (is_null($order_id)) {
				if (isset($_SESSION['shk_order_id'])) {
					$order_id = $_SESSION['shk_order_id'];
				}
                else {
					$order_id = $_SESSION['shk_lastOrder']['id'];
				}
			}

			$order = $modx->getObject('shk_order', $order_id);
            if (!$order) {
                die('no shk_order object found');
            }

            $order->set('status', 5);
            $order->save();

			return '';
			break;
		case 'success':
			break;
		case 'callback':
			$order = $modx->getObject('shk_order', $_REQUEST['ik_pm_no']);

            if (!$order) {
                die("FAIL");
            }

			if (count($_REQUEST) && isset($_REQUEST['ik_co_id']) && $_REQUEST['ik_co_id'] == $merchant_id) {

				if(isset($_REQUEST['ik_pw_via']) && $_REQUEST['ik_pw_via'] == 'test_interkassa_test_xts'){
					$sekret = $test_key;
				} else {
					$sekret = $secret_key;
				}
				
	            $data = array();
		        foreach ($_REQUEST as $key => $value) {
		            if (!preg_match('/ik_/', $key)) continue;
		            $data[$key] = $value;
		        }

                $ik_sign = $data['ik_sign'];
                unset($data['ik_sign']);
                ksort($data, SORT_STRING);
                array_push($data, $sekret);
                $sign_str = implode(":", $data);

                if($method_sign == 'MD5'){
	            	$signature = base64_encode(md5($sign_str, true));
	            } else {
	            	$signature = base64_encode(hash('sha256', $sign_str, true));
	            }

                if ($ik_sign == $signature) {
                    $order->set('status',6);
                    $order->save();
                    die("SUCCESS");
                } else {
                    die("FAIL");
                }
			} else {
				die("FAIL");
			}
			break;
		case 'payment':
            
			$order_id = null;
			
			if (isset($_SESSION['shk_order_id'])) {
				$order_id = $_SESSION['shk_order_id'];
				$amount = number_format($_SESSION['shk_order_price'], 2, '.', '');
			}
            else {
				$order_id = $_SESSION['shk_lastOrder']['id'];
				$amount = number_format($_SESSION['shk_lastOrder']['price'], 2, '.', '');
			}

			if (!$order_id) {
				return "Заказ не найден.";
			}

            $order = $modx->getObject('shk_order', $order_id);
            if (!$order) {
                die('no shk_order object found');
            }

            $order->set('status', 1);
            $order->save();

            $args = array(
                'ik_am' => $amount,
                'ik_cur' => $currency_code,
                'ik_co_id' => $merchant_id,
                'ik_pm_no' => $order_id,
                'ik_desc' => "#$order_id"
            );
            
            ksort($args, SORT_STRING);
            $args['key'] = $secret_key;
            $sign_str = implode(":", $args);

            if($method_sign == 'MD5'){
            	$signature = base64_encode(md5($sign_str, true));
            } else {
            	$signature = base64_encode(hash('sha256', $sign_str, true));
            }

            unset($args['key']);
			$output  = "<img src=\"core/components/payment/ikgateway.jpg\"/>";
			$output .= "<form action='".$action_url."' method='".$method."'>";
			$output .= "<input type='hidden' name='ik_am' value='".$amount."'>";
			$output .= "<input type='hidden' name='ik_cur' value='".$currency_code."'>";
			$output .= "<input type='hidden' name='ik_co_id' value='".$merchant_id."'>";
			$output .= "<input type='hidden' name='ik_pm_no' value='".$order_id."'>";
			$output .= "<input type='hidden' name='ik_desc' value='#".$order_id."'>";
			$output .= "<input type='hidden' name='ik_sign' value='".$signature."'>";
			$output .= "<input type='submit' name='submit' value='Оплатить через Интеркассу'>";

			return $output;

			break;
		default:
			break;
	}
}
//Для отладки можно раскомментировать этот кусок, пользуйтесь:)
/*function wrlog($content){
	$file = '/log.txt';
	$doc = fopen($file, 'a');
	file_put_contents($file, PHP_EOL . $content, FILE_APPEND);	
	fclose($doc);
}*/