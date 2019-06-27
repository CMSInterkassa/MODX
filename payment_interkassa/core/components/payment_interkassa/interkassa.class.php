<?php
class Interkassa
{
    public $actionURL = 'https://sci.interkassa.com/';

    const ikUrlSCI = 'https://sci.interkassa.com/';
    const ikUrlAPI = 'https://api.interkassa.com/v1/';

    public $config = array();

    public function __construct($modx, $prop = array())
    {
        $this->modx = $modx;

        $this -> config['secret_key'] = trim($prop['secret_key'])? trim($prop['secret_key']) : '';
        $this -> config['test_key'] = trim($prop['test_key'])? trim($prop['test_key']) : '';
        $this -> config['test_mode'] = $prop['test_mode'];
        $this -> config['api_enable'] = $prop['api_enable'];
        $this -> config['id_cashbox'] = trim($prop['id_cashbox'])? trim($prop['id_cashbox']) : '';
        $this -> config['api_id'] = trim($prop['api_id'])? trim($prop['api_id']) : '';
        $this -> config['api_key'] = trim($prop['api_key'])? trim($prop['api_key']) : '';
        $this -> config['currency'] = trim($prop['currency'])? trim($prop['currency']) : '';

        $this -> config['page_callback'] = MODX_SITE_URL . $this -> modx -> makeUrl(trim($prop['page_callback']));
        $this -> config['page_success'] = MODX_SITE_URL . $this -> modx -> makeUrl(trim($prop['page_success']));
        $this -> config['page_fail'] = MODX_SITE_URL . $this -> modx -> makeUrl(trim($prop['page_fail']));
    }

    public function getDataForm($order)
    {
        $FormData = array();
        $FormData['ik_am'] = self::formatPrice($order['orderPrice']);
        $FormData['ik_pm_no'] = $order['orderId'];
        $FormData['ik_co_id'] = $this -> config['id_cashbox'];
        $FormData['ik_desc'] = "Payment order " . $order['id'];
        $FormData['ik_cur'] = $this -> config['currency'];


        $FormData['ik_ia_u'] = $this -> config['page_callback'];
        $FormData['ik_suc_u'] = $this -> config['page_success'];
        $FormData['ik_fal_u'] = $this -> config['page_fail'];
        $FormData['ik_pnd_u'] = $this -> config['page_success'];

        if($FormData['ik_cur'] == 'RUR')
            $FormData['ik_cur'] = 'RUB';

        if ($this -> config['test_mode']) {
            $FormData['ik_pw_via'] = 'test_interkassa_test_xts';
        }

        $FormData['ik_sign'] = self::IkSignFormation($FormData, $this -> config['secret_key']);

        return $FormData;
    }

    public static function formatPrice($price)
    {
        return number_format( floatval($price), 2, '.', '');
    }

    public static function IkSignFormation($data, $secret_key)
    {
        if (!empty($data['ik_sign'])) unset($data['ik_sign']);

        $dataSet = array();
        foreach ($data as $key => $value) {
            if (!preg_match('/ik_/', $key)) continue;
            $dataSet[$key] = $value;
        }

        ksort($dataSet, SORT_STRING);
        array_push($dataSet, $secret_key);
        $arg = implode(':', $dataSet);
        $ik_sign = base64_encode(md5($arg, true));

        return $ik_sign;
    }

    public static function getAnswerFromAPI($data)
    {
        $ch = curl_init(self::ikUrlSCI);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        return $result;
    }

    public function getPaymentSystems()
    {
        $username = $this -> config['api_id'];
        $password = $this -> config['api_key'];
        $remote_url = self::ikUrlAPI . 'paysystem-input-payway?checkoutId=' . $this -> config['id_cashbox'];

        $businessAcc = $this->getIkBusinessAcc($username, $password);

        $ikHeaders = [];
        $ikHeaders[] = "Authorization: Basic " . base64_encode("$username:$password");
        if(!empty($businessAcc)) {
            $ikHeaders[] = "Ik-Api-Account-Id: " . $businessAcc;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remote_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $ikHeaders);
        $response = curl_exec($ch);

        if(empty($response))
            return '<strong style="color:red;">Error!!! System response empty!</strong>';

        $json_data = json_decode($response);
        if ($json_data->status != 'error') {
            $payment_systems = array();
            if(!empty($json_data->data)){
                foreach ($json_data->data as $ps => $info) {
                    $payment_system = $info->ser;
                    if (!array_key_exists($payment_system, $payment_systems)) {
                        $payment_systems[$payment_system] = array();
                        foreach ($info->name as $name) {
                            if ($name->l == 'en') {
                                $payment_systems[$payment_system]['title'] = ucfirst($name->v);
                            }
                            $payment_systems[$payment_system]['name'][$name->l] = $name->v;
                        }
                    }
                    $payment_systems[$payment_system]['currency'][strtoupper($info->curAls)] = $info->als;
                }
            }

            return !empty($payment_systems)? $payment_systems : '<strong style="color:red;">API connection error or system response empty!</strong>';
        } else {
            if(!empty($json_data->message))
                return '<strong style="color:red;">API connection error!<br>' . $json_data->message . '</strong>';
            else
                return '<strong style="color:red;">API connection error or system response empty!</strong>';
        }
    }

    public function getIkBusinessAcc($username = '', $password = '')
    {
        $tmpLocationFile = __DIR__ . '/tmpLocalStorageBusinessAcc.ini';
        $dataBusinessAcc = function_exists('file_get_contents')? file_get_contents($tmpLocationFile) : '{}';
        $dataBusinessAcc = json_decode($dataBusinessAcc, 1);
        $businessAcc = is_string($dataBusinessAcc['businessAcc'])? trim($dataBusinessAcc['businessAcc']) : '';
        if(empty($businessAcc) || sha1($username . $password) !== $dataBusinessAcc['hash']) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, self::ikUrlAPI . 'account');
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_HTTPHEADER, ["Authorization: Basic " . base64_encode("$username:$password")]);
            $response = curl_exec($curl);

            if (!empty($response['data'])) {
                foreach ($response['data'] as $id => $data) {
                    if ($data['tp'] == 'b') {
                        $businessAcc = $id;
                        break;
                    }
                }
            }

            if(function_exists('file_put_contents')){
                $updData = [
                    'businessAcc' => $businessAcc,
                    'hash' => sha1($username . $password)
                ];
                file_put_contents($tmpLocationFile, json_encode($updData, JSON_PRETTY_PRINT));
            }

            return $businessAcc;
        }

        return $businessAcc;
    }

    public function checkIP(){
        $ip_stack = array(
            'ip_begin'=>'151.80.190.97',
            'ip_end'=>'151.80.190.104'
        );

        $ip = ip2long($_SERVER['REMOTE_ADDR'])? ip2long($_SERVER['REMOTE_ADDR']) : !ip2long($_SERVER['REMOTE_ADDR']);

        if(($ip >= ip2long($ip_stack['ip_begin'])) && ($ip <= ip2long($ip_stack['ip_end']))){
            return true;
        }
        return false;
    }
}