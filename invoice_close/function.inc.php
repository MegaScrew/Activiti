<?php 
require_once 'crest.php';

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
* Class invoice
* @var $day - payment deadline in how many days
* @var $id - id of the deals to make an invoice for
*/
class invoice_close{
	private $id_deal;
	private $id_company;
	private $id_contact;
	private $price;
	private $inner;
	private $outer;
	private $title;
	private $city;
	private $invoice_id;
	private $invoice_account_number;
	private $invoice_order_topic;
	private $invoice_price;
	
	public function __construct(string $id_deal = '0'){
		$arData = [
			'find_deal' => [
				'method' => 'crm.deal.get',
				'params' => [ 'ID' => $id_deal, 'select' => ['ID', 'COMPANY_ID', 'CONTACT_ID', 'ASSIGNED_BY_ID', 'UF_CRM_1631861994', 'OPPORTUNITY']]
			],
			'get_company' => [
				'method' => 'crm.company.get',
				'params' => [ 'ID' => '$result[find_deal][COMPANY_ID]', 'select' => ['ID', 'TITLE', 'UF_CRM_1613731949', 'UF_CRM_1614603075', 'UF_CRM_1619766058', 'UF_CRM_1594794891', 'UF_CRM_1579359732798', 'UF_CRM_1579359748326']]
			],
			'get_invoice' => [
				'method' => 'crm.invoice.list',
				'params' => ['order' => ['ID' => 'DESC'], 'filter' => ['UF_DEAL_ID' => '$result[find_deal][ID]', 'UF_CONTACT_ID' => '$result[find_deal][CONTACT_ID]', 'PAYED' => 'N', 'PRICE' => '$result[find_deal][OPPORTUNITY]'], 'select' => ['ID', 'ACCOUNT_NUMBER', 'ORDER_TOPIC', 'PRICE']]
			]
		];

		$result = CRest::callBatch($arData);
		while($result['error']=="QUERY_LIMIT_EXCEEDED"){
		    sleep(1);
		    $result = CRest::callBatch($arData);
		    if ($result['error']<>"QUERY_LIMIT_EXCEEDED"){break;}
		}

		if ($result['result']['result_total']['get_invoice'] > 0) {

			$result = $result['result']['result'];

			$this->id_deal = $id_deal;
			$this->id_company = $result['find_deal']['COMPANY_ID'];
			$this->id_contact = $result['find_deal']['CONTACT_ID'];
			$this->price = $result['find_deal']['OPPORTUNITY'];
			$this->inner = $result['get_company']['UF_CRM_1594794891'];
			$this->outer = $result['get_company']['UF_CRM_1579359748326'];
			$this->title = $result['get_company']['TITLE'];
			$this->city = $result['get_company']['UF_CRM_1579359732798'];
			$this->invoice_id = $result['get_invoice']['0']['ID'];
			$this->invoice_account_number = $result['get_invoice']['ACCOUNT_NUMBER'];
			$this->invoice_order_topic = $result['get_invoice']['ORDER_TOPIC'];
			$this->invoice_price = $result['get_invoice']['PRICE'];
		// echo '<pre>';
			// 	print_r($result);
			// echo '</pre>';
		}
	}

	public function getIdCompany(){
		return $this->id_company;
	}

	public function getIdContact(){
		return $this->id_contact;
	}

	public function getIdDeal(){
		return $this->id_deal;
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

	public function getPrice(){
		return $this->price;
	}

	public function getInvoiceID(){
		return $this->invoice_id;
	}

	public function getInvoiceAccountNumber(){
		return $this->invoice_account_number;
	}

	public function getInvoiceOrderTopic(){
		return $this->invoice_order_topic;
	}

	public function getInvoicePrice(){
		return $this->invoice_price;
	}

}

?>