<?php
/*
* Платежный модуль Интеркасса (сниппет)
* Совместим с MODX REVO 2.5.0 (версии 2.х.х тоже должны быть совместимы, возможно с некоторыми поправками в коде)!
* Модуль требует наличие плагина ShopKeeper 3.0, 2.0 тоже будет работать, только нужно сменить переменные и вызовы $modx функций!
* version 2.0
*/

if (isset($_REQUEST['payment']) && $_REQUEST['payment'] == 'interkassa') {
	$payment_form = MODX_SITE_URL . $modx -> makeUrl( $modx->getOption('page_paymentForm', $scriptProperties, null) );
	$modx->sendRedirect($payment_form);
}

if (isset($_REQUEST['paysys'])) {
	require_once $modx->getOption('core_path') . "components/payment_interkassa/interkassa.class.php";
	$payment = new Interkassa($modx, $scriptProperties);

	$request = $_REQUEST;
	if (isset($request['ik_act']) && $request['ik_act'] == 'process'){
		$request['ik_sign'] = Interkassa::IkSignFormation($request, $payment -> config['secret_key']);
		$data = Interkassa::getAnswerFromAPI($request);
	}
	else
		$data = Interkassa::IkSignFormation($request, $payment -> config['secret_key']);

	ob_clean();
	echo $data;
	exit;
}

if (isset($scriptProperties['action'])) {
	require_once $modx->getOption('core_path') . "components/payment_interkassa/interkassa.class.php";

	$payment = new Interkassa($modx, $scriptProperties);


	switch ($scriptProperties['action']) {
		case 'fail':
			$modelpath = $modx->getOption('core_path') . 'components/shopkeeper3/model/';
			$modx->addPackage( 'shopkeeper3', $modelpath );

			$order_id = (isset($_REQUEST['ik_pm_no'])) ? $_REQUEST['ik_pm_no'] : null;
			if (is_null($order_id)) {
				if (isset($_SESSION['shk_order_id'])) {
					$order_id = $_SESSION['shk_order_id'];
				}
                else {
					$order_id = $_SESSION['shk_lastOrder']['id'];
				}
			}

			$modx->updateCollection('shk_order', array('status' => 5), array('id' => $order_id));

			return '';
			break;
		case 'success':
			break;
		case 'callback':
			$modelpath = $modx->getOption('core_path') . 'components/shopkeeper3/model/';
			$modx->addPackage( 'shopkeeper3', $modelpath );

			$order = $modx->getObject('shk_order', $_REQUEST['ik_pm_no']);

            if (!$order) die("FAIL");

			if ($payment->checkIP() && count($_REQUEST) && isset($_REQUEST['ik_co_id']) && $_REQUEST['ik_co_id'] == $payment -> config['id_cashbox']) {

				$secret_key = $payment -> config['secret_key'];
				if (isset($_REQUEST['ik_pw_via']) && $_REQUEST['ik_pw_via'] == 'test_interkassa_test_xts')
					$secret_key = $payment -> config['test_key'];

				$signature = Interkassa::IkSignFormation($_REQUEST, $secret_key);

				if ($_REQUEST['ik_sign'] == $signature) {
					$modx->updateCollection('shk_order', array('status' => 6), array('id' => $_REQUEST['ik_pm_no']));

					die("SUCCESS");
                } else {
                    die("FAIL");
                }
			} else {
				die("FAIL");
			}
			break;
		case 'payment':

			$modelpath = $modx->getOption('core_path') . 'components/shopkeeper3/model/';
			$modx->addPackage( 'shopkeeper3', $modelpath );

			$values['orderId'] = $_SESSION['shk_lastOrder']['id'];
			$values['orderPrice'] = $_SESSION['shk_lastOrder']['price'];

			if (!$values['orderId']) {
				return "Заказ не найден.";
			}
			$order = $modx->getObject('shk_order', $values['orderId']);

			if($order->status == 6) return;

			$tpl_dir = $modx->getOption('core_path') . 'components/payment_interkassa/template/';
			$smarty = $modx->getService('smarty','smarty.modSmarty');
			$smarty->caching = false;
			$smarty->setTemplateDir( $tpl_dir );

			$hidden_fields = $payment -> getDataForm($values);
			$smarty->assign('interkassa', $payment);
			$smarty->assign('hidden_fields', $hidden_fields);
			$smarty->assign('path_modal_tpl', $tpl_dir . 'modal_ps.tpl');

			return $smarty->fetch('payment.tpl');

			break;
		default:
			break;
	}
}