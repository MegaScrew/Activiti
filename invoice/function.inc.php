<?php 
require_once 'crest.php';
require_once '../../utils/phpqrcode/qrlib.php';

/**
*  Returns the date in ISO8601 format
* @var $day integer, specifies how many days to add to the current date
* @return date format ISO8601
*/
function dateISO(int $day = 0){
	if ($day == 0) {
		$date = time();
	}else{
		$date = strtotime('+'.$day.' day');
	}
	return date(DateTime::ISO8601, $date);
}

/**
* The function returns the generated qr code
* @var $id_company - id company
* @var $rq_company_name - name my company
* @var $rq_acc_num - $rq_acc_num
* @var $rq_bank_name - $rq_bank_name
* @var $rq_bik - $rq_bik
* @var $rq_cor_acc_num - $rq_cor_acc_num
* @var $rq_inn - $rq_inn
* @var $inner - inner number
* @var $outer - outer number
* @var $title - title company
* @var $city - city company
* @var $sum - sum invoice
* @return array[file name, png file in base64_encode format]
*/
function getQRCode(string $id_company, string $rq_company_name, string $rq_acc_num, string $rq_bank_name, string $rq_bik, string $rq_cor_acc_num, string $rq_inn, string $inner, string $outer, string $title, string $city, string $sum) {
	
	//формируем строку для генерирования QR code в которую подставляем название магазина, номер магазина и сумму к оплате 
	$strQRCode = 'ST00012|Name='.$rq_company_name.'|PersonalAcc='.$rq_acc_num.'|BankName='.$rq_bank_name.'|BIC='.$rq_bik.'|CorrespAcc='.$rq_cor_acc_num.'|PayeeINN='.$rq_inn.'|KPP=|persAcc='.$inner.'|LASTNAME=|payerAddress='.$city.'|Purpose='.$inner.' ('.$outer.') '.$title.'|Sum='.$sum.'00';
	
	//html PNG location prefix путь где находится директория для размещения готового QR code
	$PNG_WEB_DIR = '../../temp/';
	//формирование имени файла с QR code
	//$filename = 'qrcode'.'-'.$company_data['result']['TITLE'].'-'.date("d-m-Y").'.png';
	$filename = 'qrcode'.'-'.$id_company.'-'.date("d-m-Y").'.png'; //тут экранированное имя файла
	$fullfilename = $PNG_WEB_DIR.$filename;
	//генерируем сам QR code который сохраняеться на сервере       
	QRcode::png($strQRCode, $fullfilename, 'L', 4, 2);

	//кодируем QR code в base64 что бы запихать в битрикс
	$qrfile = array('fileData'=>array($filename, base64_encode(file_get_contents($fullfilename))));
	unlink($fullfilename);
return $qrfile;
}

/**
* Class invoice
* @var $day - payment deadline in how many days
* @var $id - id of the deals to make an invoice for
*/
class invoice{
	private $id_deal;
	private $id_company;
	private $id_contact;
	private $id_responsible;
	private $id_mycompany;
	private $id_requisite;
	private $id_bankdetail;
	private $order_topic;
	private $date_pay_before;
	private $user_description;
	private $quantity;
	private $price;
	private $sum;
	private $inner;
	private $outer;
	private $title;
	private $city;
	private $rq_company_name;
	private $rq_company_full_name;
	private $rq_inn;
	private $rq_bank_name;
	private $rq_bank_addr;
	private $rq_bik;
	private $rq_acc_num;
	private $rq_cor_acc_num;
	private $qrcode;
	
	public function __construct(int $day = 0, string $id){
		$arData = [
			'find_deal' => [
				'method' => 'crm.deal.get',
				'params' => [ 'ID' => $id, 'select' => ['ID', 'COMPANY_ID', 'CONTACT_ID', 'ASSIGNED_BY_ID', 'UF_CRM_1631861994']]
			],
			'get_company' => [
				'method' => 'crm.company.get',
				'params' => [ 'ID' => '$result[find_deal][COMPANY_ID]', 'select' => ['ID', 'TITLE', 'UF_CRM_1613731949', 'UF_CRM_1614603075', 'UF_CRM_1619766058', 'UF_CRM_1594794891', 'UF_CRM_1579359732798', 'UF_CRM_1579359748326']]
			],
			'get_my_company' => [
				'method' => 'crm.company.get',
				'params' => [ 'ID' => '$result[find_deal][UF_CRM_1631861994]']
			],
			'get_my_company_requisite' => [
				'method' => 'crm.requisite.list',
				'params' => ['order' => ['DATE_CREATE' => 'ASC'], 'filter' => ['ENTITY_TYPE_ID' => 4, 'ENTITY_ID' => '$result[get_my_company][ID]', 'PRESET_ID' => 3], 'select' => ['ID', 'ENTITY_ID', 'RQ_COMPANY_NAME', 'RQ_COMPANY_FULL_NAME', 'RQ_INN']]
			],
			'get_my_company_requisite_bankdetail' => [
				'method' => 'crm.requisite.bankdetail.list',
				'params' => ['order' => ['DATE_CREATE' => 'ASC'], 'filter' => ['COUNTRY_ID' => 1, 'ENTITY_ID' => '$result[get_my_company_requisite][0][ID]'], 'select' => ['ID', 'ENTITY_ID', 'RQ_BANK_NAME', 'RQ_BANK_ADDR', 'RQ_BIK', 'RQ_ACC_NUM', 'RQ_COR_ACC_NUM']]
			]
		];

		$result = CRest::callBatch($arData);
		while($result['error']=="QUERY_LIMIT_EXCEEDED"){
		    sleep(1);
		    $result = CRest::callBatch($arData);
		    if ($result['error']<>"QUERY_LIMIT_EXCEEDED"){break;}
		}

		if (count($result['result']['result_error']) == 0) {

			$result = $result['result']['result'];

			$this->id_deal = $id;
			$this->id_company = $result['find_deal']['COMPANY_ID'];
			$this->id_contact = $result['find_deal']['CONTACT_ID'];
			$this->id_responsible = $result['find_deal']['ASSIGNED_BY_ID'];
			$this->id_mycompany = $result['find_deal']['UF_CRM_1631861994'];
			$this->id_requisite = $result['get_my_company_requisite']['0']['ID'];
			$this->id_bankdetail = $result['get_my_company_requisite_bankdetail']['0']['ID'];
			$this->order_topic = $result['get_company']['UF_CRM_1594794891'] .' счёт за период '. $result['get_company']['UF_CRM_1619766058'];
			$this->date_pay_before = dateISO($day);
			$this->user_description = 'Отгрузка в период '. $result['get_company']['UF_CRM_1619766058'] .' на общий вес: '. $result['get_company']['UF_CRM_1614603075'] .' кг.';
			$this->quantity = $result['get_company']['UF_CRM_1614603075'];
			$this->price = $result['get_company']['UF_CRM_1613731949'];
			$this->sum = $this->quantity * $this->price;
			$this->inner = $result['get_company']['UF_CRM_1594794891'];
			$this->outer = $result['get_company']['UF_CRM_1579359748326'];
			$this->title = $result['get_company']['TITLE'];
			$this->city = $result['get_company']['UF_CRM_1579359732798'];;
			$this->rq_company_name = $result['get_my_company_requisite']['0']['RQ_COMPANY_NAME'];
			$this->rq_company_full_name = $result['get_my_company_requisite']['0']['RQ_COMPANY_FULL_NAME'];
			$this->rq_inn = $result['get_my_company_requisite']['0']['RQ_INN'];
			$this->rq_bank_name = $result['get_my_company_requisite_bankdetail']['0']['RQ_BANK_NAME'];
			$this->rq_bank_addr = $result['get_my_company_requisite_bankdetail']['0']['RQ_BANK_ADDR'];
			$this->rq_bik = $result['get_my_company_requisite_bankdetail']['0']['RQ_BIK'];
			$this->rq_acc_num = $result['get_my_company_requisite_bankdetail']['0']['RQ_ACC_NUM'];
			$this->rq_cor_acc_num = $result['get_my_company_requisite_bankdetail']['0']['RQ_COR_ACC_NUM'];
			$this->qrcode = getQRCode($this->getIdCompany(), $this->getRqCompanyName(), $this->getRqAccNum(), $this->getRqBankName(), $this->getRqBik(), $this->getRqCorAccNum(), $this->getRqInn(), $this->getInner(), $this->getOuter(), $this->getTitle(), $this->getCity(), $this->getSum());
			// echo '<pre>';
			// 	print_r($result);
			// echo '</pre>';
		}
	}

	public function getQRcode(){
		return $this->qrcode;
	}

	public function getIdCompany(){
		return $this->id_company;
	}

	public function getIdContact(){
		return $this->id_contact;
	}

	public function getIdMyCompany(){
		return $this->id_mycompany;
	}

	public function getIdDeal(){
		return $this->id_deal;
	}

	public function getOrderTopic(){
		return $this->order_topic;
	}

	public function getDatePayBefore(){
		return $this->date_pay_before;
	}

	public function getUserDescription(){
		return $this->user_description;
	}

	public function getQuantity(){
		return $this->quantity;
	}

	public function getPrice(){
		return $this->price;
	}

	public function getRqCompanyName(){
		return $this->rq_company_name;
	}

	public function getRqAccNum(){
		return $this->rq_acc_num;
	}

	public function getRqBankName(){
		return $this->rq_bank_name;
	}

	public function getRqBik(){
		return $this->rq_bik;
	}

	public function getRqCorAccNum(){
		return $this->rq_cor_acc_num;
	}

	public function getRqInn(){
		return $this->rq_inn;
	}

	public function getInner(){
		return $this->inner;
	}

	public function getOuter(){
		return $this->outer;
	}

	public function getTitle(){
		return $this->title;
	}

	public function getCity(){
		return $this->city;
	}

	public function getSum(){
		return $this->sum;
	}

	public function getIdResponsible(){
		return $this->id_responsible;
	}
}

?>