<?php 
$protocol=$_SERVER['SERVER_PORT']=='443'?'https':'http';
$host=explode(':',$_SERVER['HTTP_HOST']);
$host=$host[0];
define('BP_APP_HANDLER', $protocol.'://'.$host.$_SERVER['REQUEST_URI']);
header('Content-Type: text/html; charset=UTF-8');

if (!empty($_REQUEST['workflow_id'])) {
	if (empty($_REQUEST['auth'])) {
		die;
	}
	$event_token = $_REQUEST['event_token'];
	$auth = $_REQUEST['auth'];
	$ID = $_REQUEST['properties']['Company'];
	$deal_info = callB24Method($auth,'crm.deal.list',array(
		'order' => [
			'ID'=>'ASC'
		],
		'filter' => [
			'COMPANY_ID' => $ID,
			'CLOSED' => 'N'
			],
		'select' => [
			'ID',
			'ASSIGNED_BY_ID'
		]
		)
		);

	callB24Method($auth,'bizproc.event.send',array(
		"EVENT_TOKEN"=>$event_token,
		"RETURN_VALUES"=>array(
			'DEAL_TOTAL' => count($deal_info)
		),
		"LOG_MESSAGE"=>'OK'	
	));
}



function clean($value = "") {
    $value = trim($value);
    $value = stripslashes($value);
    $value = strip_tags($value);
    $value = htmlspecialchars($value);
    return $value;
}

function check_length($value = "", $min, $max) {
    $result = (mb_strlen($value) < $min || mb_strlen($value) > $max);
    return !$result;
}

function callB24Method(array $auth, $method, $params){
	$c=curl_init('https://'.$auth['domain'].'/rest/'.$method.'.json');
	$params["auth"]=$auth["access_token"];
	curl_setopt($c,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($c,CURLOPT_POST,true);
	curl_setopt($c,CURLOPT_POSTFIELDS,http_build_query($params));
	//AddMessage2Log($c, "demo_user_info");
	$response=curl_exec($c);
	//AddMessage2Log($response, "demo_user_info");
	$response=json_decode($response,true);
	return $response['result'];
}
?>


<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title></title>
</head>
<body>
	<h1>Двойные сделки</h1>

	<script src="//api.bitrix24.com/api/v1/"></script>
	<script src="https://code.jquery.com/jquery-latest.js"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js" integrity="sha384-B0UglyR+jN6CkvvICOB2joaf5I4l3gm9GU6Hc1og6Ls7i6U/mkkaduKaBhlAXv9k" crossorigin="anonymous"></script>


	<button onclick="installActivity1();">Добавить Активит</button>
	<button onclick="uninstallActivity1();">Удалить Активити</button>

<script>
	BX24.init(function()
	{

	});

	function installActivity1()
	{
		var params={
				'CODE':'duble_deals_info', //уникальный в рамках приложения код
				'HANDLER':'<?=BP_APP_HANDLER?>',
				'AUTH_USER_ID':1,
				'USE_SUBSCRIPTION':'Y', //Y - если бизнесс-процесс должен ждать ответа приложения, N - если не должен ждать
				'NAME':'Двойные сделки в компании',
				'DESCRIPTION':'Активи возвращает колличество задвоенных сделок в компании, БП будет стоять пока не придет ответ.',
				'PROPERTIES':{ //Входные данные для активити
					'Company':{
						'Name': 'ID Компании',
						'DESCRIPTION': 'Укажите ID Компании',
						'Type':'company',
						'Required':'Y',
						'Multiple':'N',
					},
				},
				'RETURN_PROPERTIES':{ //данные, которые активити будет возвращать бизнес-процессу
					'DEAL_TOTAL':{
						'Name':'Deal_Total',
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

	function uninstallActivity1(){
		var params={
			'CODE':'duble_deals_info'
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