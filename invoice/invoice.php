<?php 
$protocol=$_SERVER['SERVER_PORT']=='443'?'https':'http';
$host=explode(':',$_SERVER['HTTP_HOST']);
$host=$host[0];
define('BP_APP_HANDLER', $protocol.'://'.$host.$_SERVER['REQUEST_URI']);
header('Content-Type: text/html; charset=UTF-8');

require_once 'function.inc.php';

// $str = 'Start' ;

//     $params = Array(
//         'USER_ID' => 876,
//          // 'MESSAGE' => 'xxx');
//     	'MESSAGE' => $str);

//     $result = CRest::call('im.message.add', $params);

if (!empty($_REQUEST['workflow_id'])) {
	if (empty($_REQUEST['auth'])) {
		die;
	}

	$event_token = $_REQUEST['event_token'];
	
	$auth = $_REQUEST['auth'];
	$id_deal = $_REQUEST['properties']['Deal'];
	$day = $_REQUEST['properties']['Day'];

	$my_invoice = new invoice($day, $id_deal);

	$arData = [
		'add_invoice' => [
			'method' => 'crm.invoice.add',
			'params' => [ 
				'ID' => $my_invoice->getIdCompany(), 
				'fields' => [
					'UF_CONTACT_ID' => $my_invoice->getIdContact(),
					'UF_COMPANY_ID' => $my_invoice->getIdCompany(),
	                'UF_MYCOMPANY_ID' => $my_invoice->getIdMyCompany(),
					'UF_DEAL_ID' => $my_invoice->getIdDeal(),                        
	                'ORDER_TOPIC' => $my_invoice->getOrderTopic(),
			        'STATUS_ID' => 'N',
			        'PAY_SYSTEM_ID' => 2,
			        'PERSON_TYPE_ID' => 1,
			        'DATE_PAY_BEFORE' => $my_invoice->getDatePayBefore(),
			        'USER_DESCRIPTION' => $my_invoice->getUserDescription(),
			        'UF_CRM_1631880804' => $my_invoice->getQRcode(),
			        'RESPONSIBLE_ID' => $my_invoice->getIdResponsible(),
			            'PRODUCT_ROWS' => [
			                '0' => [
				                'PRODUCT_ID' => 209260,
				                'QUANTITY' => $my_invoice->getQuantity(),
				                'PRICE' => $my_invoice->getPrice(),
				                'MEASURE_CODE' => '166',       
				                'MEASURE_NAME' => 'кг',
				                'PRODUCT_NAME' => 'Пищевые отходы 5 класса опасности'
			                ]
	                	]
	            ]
	        ]
		]
	];

	$result = CRest::callBatch($arData);
	while($result['error']=="QUERY_LIMIT_EXCEEDED"){
	    sleep(1);
	    $result = CRest::callBatch($arData);
	    if ($result['error']<>"QUERY_LIMIT_EXCEEDED"){break;}
	}

	$params = [
        'EVENT_TOKEN'  => $event_token,
        'RETURN_VALUES'=> [
			'INVOICE_NUMBER' => $result['result']['result']['add_invoice'],
		],
		'LOG_MESSAGE'=>'OK'	
    ];

    $result = CRest::call('bizproc.event.send', $params); 
    while($result['error']=="QUERY_LIMIT_EXCEEDED"){
        sleep(1);
        $result = CRest::callBatch($arData);
        if ($result['error']<>"QUERY_LIMIT_EXCEEDED"){break;}
    } 
}

$result = CRest::installApp();
if($result['rest_only'] === false):?>

<!DOCTYPE html>
<html lang="ru">
<head>
	<head>
		<meta charset="UTF-8">
		<title></title>
		<script src="//api.bitrix24.com/api/v1/"></script>
		<script src="https://code.jquery.com/jquery-latest.js"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js" integrity="sha384-B0UglyR+jN6CkvvICOB2joaf5I4l3gm9GU6Hc1og6Ls7i6U/mkkaduKaBhlAXv9k" crossorigin="anonymous"></script>
		<?php if($result['install'] == true):?>
			<script>
				BX24.init(function(){
					BX24.installFinish();
				});
			</script>
		<?php endif;?>
	</head>
	<body>
		<?php if($result['install'] == true):?>
			installation has been finished
		<?php else:?>
			installation error
		<?php endif;?>
<?php endif;?>

	<h1>Счета</h1>
	<button onclick="installActivity();">Добавить Активит</button>
	<button onclick="uninstallActivity();">Удалить Активити</button>

<script>

	function installActivity(){

		var params={
				'CODE':'create_invoice', //уникальный в рамках приложения код
				'HANDLER':'<?=BP_APP_HANDLER?>',
				'AUTH_USER_ID':1644,
				'USE_SUBSCRIPTION':'Y', //Y - если бизнесс-процесс должен ждать ответа приложения, N - если не должен ждать
				'NAME':'Счета',
				'DESCRIPTION':'Активи формирует Счет, БП будет стоять пока не придет ответ.',
				'PROPERTIES':{ //Входные данные для активити
					'Deal':{
						'Name': 'ID Сделки',
						'DESCRIPTION': 'Укажите ID Сделки',
						'Type':'string',
						'Required':'Y',
						'Multiple':'N',
					},
					'Day':{
						'Name': 'Дней',
						'DESCRIPTION': 'Укажите сколько дней дается на оплату (1, 2, 3...99)',
						'Type':'integer',
						'Required':'Y',
						'Multiple':'N',
					},
				},
				'RETURN_PROPERTIES':{ //данные, которые активити будет возвращать бизнес-процессу
					'INVOICE_NUMBER':{
						'Name':'Invoice_Number',
						'Type':'string',
						'Required':'N',
						'Multiple':'N',
					},
				}
			}

		BX24.callMethod(
			'bizproc.activity.add',
			params,
			function(result)
			{
				if (result.error())
					alert('Error: '+result.error());
				else
					alert('Installation successfully completed');
				}
		);
	}

	function uninstallActivity(){
		var params={
			'CODE':'create_invoice'
		}

		BX24.callMethod(
			'bizproc.activity.delete',
			params,
			function (result)
			{
				if (result.error())
					alert('Error: '+result.error());
				else
					alert('Uninstallathion successfully completed');
			}
		);
	}
</script>
</body>
</html>