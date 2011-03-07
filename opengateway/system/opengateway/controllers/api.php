<?php

class API extends Controller {

	function api()
	{
		parent::Controller();	
	}
	
	function index()
	{
		// grab the request
		$request = trim(file_get_contents('php://input'));

		// Log the request - don't log the request so we don't store CC information
		//$this->log_model->LogRequest($request);
		
		// find out if the request is valid XML
		$xml = @simplexml_load_string($request);
		
		// if it is not valid XML...
		if(!$xml) {
			die($this->response->Error(1000));
		}
		
		// Make an array out of the XML
		$this->load->library('arraytoxml');
		$params = $this->arraytoxml->toArray($xml);
		
		// get the api ID and secret key
		$api_id = $params['authentication']['api_id'];
		$secret_key = $params['authentication']['secret_key'];
		
		// authenticate the api ID
		$this->load->model('authentication_model', 'auth');
		
		$client = $this->auth->Authenticate($api_id, $secret_key);
		
		// did they authenticate?
		if(!$client) {
			die($this->response->Error(1001));
		}
		
		$client_id = $client->client_id;	
		
		// Get the request type
		if(!isset($params['type'])) {
					
			die($this->response->Error(1002));
		}
		$request_type = $params['type'];
		
		// Make sure the first letter is capitalized
		$request_type = ucfirst($request_type);
		
		// Make sure a proper format was passed
		if(isset($params['format'])) {
			$format = $params['format'];
			if(!in_array($format, array('xml', 'json', 'php'))) {
				echo $this->response->Error(1006);
				die();
			}
		} else {
			$format = 'xml';
		}
		
		// validate the request type
		$this->load->model('request_type_model', 'request_type');
		$request_type_model = $this->request_type->ValidateRequestType($request_type);
		
		if (!$request_type_model and !method_exists($this,$request_type)) {
			die($this->response->Error(1002));
		}
		
		// Load the correct model and method
		// Is this method part of this API controller?
		if (method_exists($this,$request_type)) {
			
			$response = $this->$request_type($client_id, $params);
		}
		else {
			$this->load->model($request_type_model);
			$response = $this->$request_type_model->$request_type($client_id, $params);
		}
		
		// handle errors that didn't just kill the code
		if ($response == FALSE) {
			die($this->response->Error(1009));
		}
		
		// Echo the response
		echo $this->response->FormatResponse($response, $format);		
	}
	
	function Charge($client_id, $params) {
		$this->load->model('gateway_model');
		
		// Make sure it came from a secure connection if SSL is active
		if (empty($_SERVER["HTTPS"]) and $this->config->item('ssl_active') == TRUE) {
			die($this->response->Error(1010));
		}
		
		// we don't check the gateway here, because the GetGatewayDetails function will attempt
		// to find the default gateway
		
		// take XML params and put them in variables
		$credit_card = isset($params['credit_card']) ? $params['credit_card'] : array();
		$customer_id = isset($params['customer_id']) ? $params['customer_id'] : FALSE;
		$customer = isset($params['customer']) ? $params['customer'] : FALSE;
		$amount = isset($params['amount']) ? $params['amount'] : FALSE;
		$gateway_id = isset($params['gateway_id']) ? $params['gateway_id'] : FALSE;
		$customer_ip = isset($params['customer_ip_address']) ? $params['customer_ip_address'] : FALSE;
		$return_url = isset($params['return_url']) ? $params['return_url'] : FALSE;
		$cancel_url = isset($params['cancel_url']) ? $params['cancel_url'] : FALSE;
		$coupon = isset($params['coupon']) ? $params['coupon'] : FALSE;
		
		return $this->gateway_model->Charge($client_id, $gateway_id, $amount, $credit_card, $customer_id, $customer, $customer_ip, $return_url, $cancel_url, $coupon);
	}
	
	function Recur($client_id, $params) {
		$this->load->model('gateway_model');
		
		// Make sure it came from a secure connection if SSL is active
		if (empty($_SERVER["HTTPS"]) and $this->config->item('ssl_active') == TRUE) {
			die($this->response->Error(1010));
		}
		
		// we don't check the gateway here, because the GetGatewayDetails function will attempt
		// to find the default gateway
		
		// take XML params and put them in variables
		$credit_card = isset($params['credit_card']) ? $params['credit_card'] : array();
		$customer_id = isset($params['customer_id']) ? $params['customer_id'] : FALSE;
		$customer = isset($params['customer']) ? $params['customer'] : FALSE;
		$amount = isset($params['amount']) ? $params['amount'] : FALSE;
		$gateway_id = isset($params['gateway_id']) ? $params['gateway_id'] : FALSE;
		$customer_ip = isset($params['customer_ip_address']) ? $params['customer_ip_address'] : FALSE;
		$recur = isset($params['recur']) ? $params['recur'] : FALSE;
		$return_url = isset($params['return_url']) ? $params['return_url'] : FALSE;
		$cancel_url = isset($params['cancel_url']) ? $params['cancel_url'] : FALSE;
		$renew = isset($params['renew']) ? $params['renew'] : FALSE;
		$coupon = isset($params['coupon']) ? $params['coupon'] : FALSE;
		
		return $this->gateway_model->Recur($client_id, $gateway_id, $amount, $credit_card, $customer_id, $customer, $customer_ip, $recur, $return_url, $cancel_url, $renew, $coupon);
	}
	
	function Refund ($client_id, $params) {
		$this->load->model('gateway_model');
		
		if ($this->gateway_model->Refund($client_id, $params['charge_id'])) {
			return $this->response->TransactionResponse(50, array());
		}
		else {
			return $this->response->TransactionResponse(51, array());
		}
	}
	
	function UpdateCreditCard ($client_id, $params) {
		// Make sure it came from a secure connection if SSL is active
		if (empty($_SERVER["HTTPS"]) and $this->config->item('ssl_active') == TRUE) {
			die($this->response->Error(1010));
		}
		
		$gateway_id = (isset($params['gateway_id'])) ? $params['gateway_id'] : FALSE;
		$credit_card = (isset($params['credit_card'])) ? $params['credit_card'] : FALSE;
		$new_plan_id = (isset($params['plan_id'])) ? $params['plan_id'] : FALSE;
		
		if (!isset($params['recurring_id']) or empty($params['recurring_id'])) {
			die($this->response->Error(6002));
		}
		
		$this->load->model('gateway_model');
		
		return $this->gateway_model->UpdateCreditCard($client_id, $params['recurring_id'], $credit_card, $gateway_id, $new_plan_id);
	}
	
	function DeletePlan($client_id, $params)
	{
		$this->load->model('plan_model');
		
		if ($this->plan_model->DeletePlan($client_id, $params['plan_id'])) {
			return $this->response->TransactionResponse(502, array());
		} else {
			return FALSE;
		}
	}
	
	function GetPlans($client_id, $params)
	{
		$this->load->model('plan_model');
		
		if (!isset($params['limit']) or $params['limit'] > $this->config->item('query_result_default_limit')) {
			$params['limit'] = $this->config->item('query_result_default_limit');
		}
		
		$data = array();
		if ($plans = $this->plan_model->GetPlans($client_id, $params)) {
			unset($params['limit']);
			$data['results'] = count($plans);
			$data['total_results'] = count($this->plan_model->GetPlans($client_id, $params));
			
			while (list(,$plan) = each($plans)) {
				$data['plans']['plan'][] = $plan;
			}
		}
		else {
			$data['results'] = 0;
			$data['total_results'] = 0;
		}
		
		return $data;
	}
	
	function GetPlan($client_id, $params)
	{
		$this->load->model('plan_model');
		
		if ($plan = $this->plan_model->GetPlan($client_id, $params['plan_id'])) {
			$data = array();
			$data['plan'] = $plan;
			
			return $data;
		}
		else {
			return FALSE;
		}
	}
	
	function UpdatePlan($client_id, $params)
	{
		$this->load->model('plan_model');
		
		if ($this->plan_model->UpdatePlan($client_id, $params['plan_id'], $params)) {
			return $this->response->TransactionResponse(501, array());		
		}
		else {
			return FALSE;
		}
	}
	
	function NewPlan($client_id, $params)
	{
		$this->load->model('plan_model');
		
		if ($insert_id = $this->plan_model->NewPlan($client_id, $params)) {
			$response_array = array();
			$response_array['plan_id'] = $insert_id; 
			$response = $this->response->TransactionResponse(500, $response_array);
			
			return $response;
		}
		else {
			return FALSE;
		}
	}
	
	function ChangeRecurringPlan($client_id,$params) {
		$this->load->model('recurring_model');
		
		if (!isset($params['plan_id'])) {
			die($this->response->Error(6006));
		}
		elseif (!isset($params['recurring_id'])) {
			die($this->response->Error(6002));
		}
		
		if ($this->recurring_model->ChangeRecurringPlan($client_id,$params['recurring_id'],$params['plan_id'])) 
		{
			return $this->response->TransactionResponse(103, array());
		}
		else {
			return FALSE;
		}
	}
	
	function NewGateway($client_id, $params)
	{
		$this->load->model('gateway_model');
		
		if ($insert_id = $this->gateway_model->NewGateway($client_id, $params)) {
			$response_array = array();
			$response_array['gateway_id'] = $insert_id; 
			$response = $this->response->TransactionResponse(400, $response_array);
			
			return $response;
		}
		else {
			return FALSE;
		}
	}
	
	function MakeDefaultGateway($client_id, $params)
	{
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('MakeDefaultGateway', $params);
		
		$this->load->model('gateway_model');
		
		if ($this->gateway_model->MakeDefaultGateway($client_id, $params['gateway_id'])) {
			$response = $this->response->TransactionResponse(403, $response_array);
			
			return $response;
		}
		else {
			return FALSE;
		}
	}
	
	function UpdateGateway($client_id, $params)
	{
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('MakeDefaultGateway', $params);
		
		$this->load->model('gateway_model');
		if ($this->gateway_model->UpdateGateway($client_id, $params)) {
			$response = $this->response->TransactionResponse(401,array());
			
			return $response;
		}
		else {
			return FALSE;
		}
	}
	
	function DeleteGateway($client_id, $params)
	{
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('DeleteGateway', $params);
		
		$this->load->model('gateway_model');
		if ($this->gateway_model->DeleteGateway($client_id, $params['gateway_id'])) {
			// End all the subscriptions.
			$this->load->model('recurring_model');
			$data = $this->recurring_model->CancelRecurringByGateway($client_id, $params['gateway_id']);

			$response = $this->response->TransactionResponse(402,array());
			
			return $response;
		}
		else {
			return FALSE;
		}
	}
	
	function GetRecurring($client_id, $params)
	{
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('GetRecurring', $params);
	
		$this->load->model('recurring_model');
		if (!$recurring = $this->recurring_model->GetRecurring($client_id, $params['recurring_id'])) {
			 die($this->response->Error(6002));
		} else {
			$data = array();
			$data['recurring'] = $recurring;
			return $data;
		}
	}
	
	function GetRecurrings($client_id, $params)
	{
		$this->load->model('recurring_model');
		
		if (!isset($params['limit']) or $params['limit'] > $this->config->item('query_result_default_limit')) {
			$params['limit'] = $this->config->item('query_result_default_limit');
		}
		
		$data = array();
		if ($recurrings = $this->recurring_model->GetRecurrings($client_id, $params)) {
			unset($params['limit']);
			$data['results'] = count($recurrings);
			$data['total_results'] = count($this->recurring_model->GetRecurrings($client_id, $params));
			
			while (list(,$recurring) = each($recurrings)) {
				$data['recurrings']['recurring'][] = $recurring;
			}
		}
		else {
			$data['results'] = 0;
			$data['total_results'] = 0;
		}
		
		return $data;
	}
	
	function UpdateRecurring($client_id, $params)
	{
		if (isset($params['plan_id'])) {
			 die($this->response->Error(6006));
		}
		
		if(!isset($params['recurring_id'])) {
			 die($this->response->Error(6002));
		}
	
		$this->load->model('recurring_model');
		if ($this->recurring_model->UpdateRecurring($client_id, $params)) {
			$response = $this->response->TransactionResponse(102,array());
			
			return $response;
		}
		else {
			die($this->response->Error(6005));
		}
	}
	
	function CancelRecurring($client_id, $params)
	{
		if (!isset($params['recurring_id'])) {
			die($this->response->Error(6002));
		}
		
		$this->load->model('recurring_model');
		
		if ($this->recurring_model->CancelRecurring($client_id, $params['recurring_id'])) {
			return $this->response->TransactionResponse(101,array());
		}
		else {
			die($this->response->Error(5014));
		}
	}
	
	function NewCustomer($client_id, $params)
	{
		$this->load->model('customer_model');
		
		if ($customer_id = $this->customer_model->NewCustomer($client_id, $params)) {
			$response = array('customer_id' => $customer_id);
			
			return $this->response->TransactionResponse(200, $response);
		}
		else {
			return FALSE;
		}	
	}
	
	function UpdateCustomer($client_id, $params)
	{
		if(!isset($params['customer_id'])) {
			die($this->response->Error(6001));
		}
		
		$this->load->model('customer_model');
		
		if ($this->customer_model->UpdateCustomer($client_id, $params['customer_id'], $params)) {
			return $this->response->TransactionResponse(201);
		}
		else {
			return FALSE;
		}
	}
	
	function DeleteCustomer($client_id, $params)
	{
		if(!isset($params['customer_id'])) {
			die($this->response->Error(6001));
		}
		
		$this->load->model('customer_model');
		
		if ($this->customer_model->DeleteCustomer($client_id, $params['customer_id'])) {
			return $this->response->TransactionResponse(202);
		}
		else {
			return FALSE;
		}	
	}
	
	function GetCustomers($client_id, $params)
	{
		$this->load->model('customer_model');
	
		if (!isset($params['limit']) or $params['limit'] > $this->config->item('query_result_default_limit')) {
			$params['limit'] = $this->config->item('query_result_default_limit');
		}
	
		
		$data = array();
		if ($customers = $this->customer_model->GetCustomers($client_id, $params)) {
			unset($params['limit']);
			$data['results'] = count($customers);
			$data['total_results'] = count($this->customer_model->GetCustomers($client_id, $params));
			
			while (list(,$customer) = each($customers)) {
				// sort through plans, first
				if (isset($customer['plans']) and is_array($customer['plans'])) {
					$customer_plans = $customer['plans'];
					unset($customer['plans']);
					while (list(,$plan) = each($customer_plans)) {
						$customer['plans']['plan'][] = $plan;
					}
				}
				else {
					unset($customer['plans']);
				}
				
				$data['customers']['customer'][] = $customer;
			}
		}
		else {
			$data['results'] = 0;
			$data['total_results'] = 0;
		}
		
		return $data;
	}
	
	function GetCustomer($client_id, $params)
	{
		// Get the customer id
		if(!isset($params['customer_id'])) {
			die($this->response->Error(4000));
		}
		
		$this->load->model('customer_model');
		
		$data = array();
		if ($customer = $this->customer_model->GetCustomer($client_id, $params['customer_id'])) {	
			// sort through plans, first
			$customer_plans = isset($customer['plans']) ? $customer['plans'] : '';
			unset($customer['plans']);
			if (is_array($customer_plans)) {
				while (list(, $plan) = each($customer_plans)) {
					$customer['plans']['plan'][] = $plan;
				}
			}
			
			$data['customer'] = $customer;
			
			return $data;
		}
		else {
			return FALSE;
		}
	}
	
	function GetCharges($client_id, $params)
	{
		$this->load->model('charge_model');
		
		if (!isset($params['limit']) or $params['limit'] > $this->config->item('query_result_default_limit')) {
			$params['limit'] = $this->config->item('query_result_default_limit');
		}
		
		$data = array();
		if ($charges = $this->charge_model->GetCharges($client_id, $params)) {
			unset($params['limit']);
			$data['results'] = count($charges);
			$data['total_results'] = count($this->charge_model->GetCharges($client_id, $params));
			
			while (list(,$charge) = each($charges)) {
				$data['charges']['charge'][] = $charge;
			}
		}
		else {
			$data['results'] = 0;
			$data['total_results'] = 0;
		}
		
		return $data;
	}
	
	function GetCharge($client_id, $params)
	{
		// Get the charge ID
		if(!isset($params['charge_id'])) {
			die($this->response->Error(6000));
		}
		
		$this->load->model('charge_model');
		
		$data = array();
		if ($charge = $this->charge_model->GetCharge($client_id, $params['charge_id'])) {	
			$data['charge'] = $charge;
			
			return $data;
		}
		else {
			return FALSE;
		}
	}
	
	function GetLatestCharge($client_id, $params)
	{
		if(!isset($params['customer_id'])) {
			die($this->response->Error(6001));
		}
		
		$this->load->model('charge_model');
		
		$data = array();
		if ($charge = $this->charge_model->GetLatestCharge($client_id, $params['customer_id'])) {	
			$data['charge'] = $charge;
			
			return $data;
		}
		else {
			return FALSE;
		}
	}
	
	function NewClient($client_id, $params)
	{
		$this->load->model('client_model');
		
		if ($client = $this->client_model->NewClient($client_id, $params)) {
			$response = $this->response->TransactionResponse(300,$client);
			
			return $response;
		}
		else {
			return FALSE;
		}
	}
	
	function UpdateAccount($client_id, $params)
	{
		$this->load->model('client_model');
		
		$params['client_id'] = $client_id;
		return $this->UpdateClient($client_id, $client_id, $params);
	}
	
	function UpdateClient($client_id, $params)
	{
		$this->load->model('client_model');
		
		if (!isset($params['client_id']) or !is_numeric($params['client_id'])) {
			die($this->response->Error(1004));
		}
		
		if ($this->client_model->UpdateClient($client_id, $params['client_id'], $params)) {
			return $this->response->TransactionResponse(301,array());
		}
		else {
			return FALSE;
		}
	}
	
	function SuspendClient($client_id, $params)
	{
		$this->load->model('client_model');
		
		if ($this->client_model->SuspendClient($client_id, $params['client_id'])) {
			return $this->response->TransactionResponse(302,array());
		}
		else {
			die($this->response->Error(2004));
		}
	}
	
	function UnsuspendClient($client_id, $params)
	{
		$this->load->model('client_model');
		
		if ($this->client_model->UnsuspendClient($client_id, $params['client_id'])) {
			return $this->response->TransactionResponse(303,array());
		}
		else {
			die($this->response->Error(2004));
		}
	}
	
	function DeleteClient($client_id, $params)
	{
		$this->load->model('client_model');
		
		if ($this->client_model->DeleteClient($client_id, $params['client_id'])) {
			return $this->response->TransactionResponse(304,array());
		}
		else {
			die($this->response->Error(2004));
		}
	}
	
	function NewEmail($client_id, $params)
	{
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('NewEmail', $params);
		
		// Get the email trigger id
		$this->load->model('email_model');
		$trigger_id = $this->email_model->GetTriggerId($params['trigger']);
		
		if(!$trigger_id) {
			die($this->response->Error(8000));
		}
		
		// throw an error if the email body had HTML and caused weird XML parsing into an array
		if (is_array($params['email_body'])) {
			die($this->response->Error(8002));
		}
		
		$this->load->model('email_model');
		$email_id = $this->email_model->SaveEmail($client_id, $trigger_id, $params);
		
		$response_array = array('email_id' => $email_id);
		return $this->response->TransactionResponse(600, $response_array);
	}
	
	function UpdateEmail($client_id, $params)
	{
		// Get the email id
		if(!isset($params['email_id'])) {
			die($this->response->Error(8001));
		}
		
		// Validate the required fields
		$this->load->library('field_validation');
		$this->field_validation->ValidateRequiredFields('UpdateEmail', $params);
		
		// Get the email trigger id
		if(isset($params['trigger'])) {
			$this->load->model('email_model');
			$trigger_id = $this->email_model->GetTriggerId($params['trigger']);
			
			if(!$trigger_id) {
				die($this->response->Error(8000));
			}
		} else {
			$trigger_id = FALSE;
		}
		
		// throw an error if the email body had HTML and caused weird XML parsing into an array
		if(is_array($params['email_body'])) {
			die($this->response->Error(8002));
		}
		
		$this->load->model('email_model');
		$email_id = $this->email_model->UpdateEmail($client_id, $params['email_id'], $params, $trigger_id);
		
		return $this->response->TransactionResponse(601, array());
	}
	
	function DeleteEmail($client_id, $params)
	{
		// Get the email id
		if(!isset($params['email_id'])) {
			die($this->response->Error(8001));
		}
		
		$this->load->model('email_model');
		$this->email_model->DeleteEmail($client_id, $params['email_id']);
		
		return $this->response->TransactionResponse(602, array());
	}
	
	function GetEmail($client_id, $params)
	{
		if(!$params['email_id']) {
			die($this->response->Error(8000));
		}
		
		$this->load->model('email_model');
		
		if ($response = $this->email_model->GetEmail($client_id,$params['email_id'])) {
			$data['email'] = $response;
			return $data;
		}
		else {
			return FALSE;
		}	
	}
	
	function GetEmails($client_id, $params)
	{
		$this->load->model('email_model');
		
		if (!isset($params['limit']) or $params['limit'] > $this->config->item('query_result_default_limit')) {
			$params['limit'] = $this->config->item('query_result_default_limit');
		}
		
		$data = array();
		if ($emails = $this->email_model->GetEmails($client_id, $params)) {
			unset($params['limit']);
			$data['results'] = count($emails);
			$data['total_results'] = count($this->email_model->GetEmails($client_id, $params));
			
			while (list(,$email) = each($emails)) {
				$data['emails']['email'][] = $email;
			}
		}
		else {
			$data['results'] = 0;
			$data['total_results'] = 0;
		}
		
		return $data;
	}
	
	function GetEmailVariables($client_id, $params)
	{
		// Get the email trigger id
		if(isset($params['trigger'])) {
			$this->load->model('email_model');
			$trigger_id = $this->email_model->GetTriggerId($params['trigger']);
		} else {
			$trigger_id = FALSE;
		}
		
		if(!$trigger_id) {
			die($this->response->Error(8000));
		}
		
		$this->load->model('email_model');
		
		if ($response = $this->email_model->GetEmailVariables($trigger_id)) {
			foreach ($response as $array) {
				$return['variables']['variable'] = $array;
			}
			return $return;
		}
		else {
			return FALSE;
		}
	}
	
	function TestConnection($client_id, $params)
	{
		// Make sure the gateway is actually theirs
		$this->load->model('gateway_model');
		$gateway = $this->gateway_model->GetGatewayDetails($client_id, $params['gateway_id']);
		
		if(!$gateway) {
			die($this->response->Error(3000));
		}
		
		// Load the proper library
		$gateway_name = $gateway['name'];
		$this->load->library('payment/'.$gateway_name);
		$response = $this->$gateway_name->TestConnection($client_id, $gateway);
		
		if($response) {
			$response = $this->response->TransactionResponse('00');
		} else {			

			$response = $this->response->TransactionResponse('01');
		}
		
		return $response;
		
		
	}
	
	function GetClients($client_id, $params) 
	{
		$this->load->model('client_model');
		
		if (!isset($params['limit']) or $params['limit'] > $this->config->item('query_result_default_limit')) {
			$params['limit'] = $this->config->item('query_result_default_limit');
		}
		
		$data = array();
		if ($clients = $this->client_model->GetClients($client_id, $params)) {
			unset($params['limit']);
			$data['results'] = count($clients);
			$data['total_results'] = count($this->client_model->GetClients($client_id, $params));
			
			while (list(,$client) = each($clients)) {
				$data['clients']['client'][] = $client;
			}
		}
		else {
			$data['results'] = 0;
			$data['total_results'] = 0;
		}
		
		return $data;
		
	}
	
	function GetClient($client_id, $params) 
	{
		if(!$params['client_id']) {
			die($this->response->Error(3002));
		}
		
		$this->load->model('client_model');
		
		if ($response = $this->client_model->GetClient($client_id,$params['client_id'])) {
			$data['client'] = $response;
			return $data;
		}
		else {
			return FALSE;
		}	
		
		return $data;
	}
	
	function GetGateways($client_id, $params)
	{
		$this->load->model('gateway_model');
		
		if (!isset($params['limit']) or $params['limit'] > $this->config->item('query_result_default_limit')) {
			$params['limit'] = $this->config->item('query_result_default_limit');
		}
		
		$data = array();
		if ($gateways = $this->gateway_model->GetGateways($client_id, $params)) {
			unset($params['limit']);
			$data['results'] = count($gateways);
			$data['total_results'] = count($this->gateway_model->GetGateways($client_id, $params));
			
			while (list(,$gateway) = each($gateways)) {
				$data['gateways']['gateway'][] = $gateway;
			}
		}
		else {
			$data['results'] = 0;
			$data['total_results'] = 0;
		}
		
		return $data;
	}
	
	function GetGateway($client_id, $params) 
	{
		if(!$params['gateway_id']) {
			die($this->response->Error(3001));
		}
		
		$this->load->model('gateway_model');
		
		if ($response = $this->gateway_model->GetGatewayDetails($client_id,$params['gateway_id'])) {
			$data['gateway'] = $response;
			return $data;
		}
		else {
			return FALSE;
		}	
		
		return $data;
	}
	
	/**
	* Coupon validate
	*
	* @param string $coupon
	* @param string $plan_id (optional)
	* @param string $amount (required if no plan_id)
	*
	* @return status (valid/invalid), amount
	*/
	function CouponValidate ($client_id, $params) {
		if (!$params['coupon']) {
			die($this->response->Error(1004));
		}
		
		$coupon = $params['coupon'];
		
		$plan_id = (isset($params['plan_id'])) ? $params['plan_id'] : FALSE;
		
		if (isset($params['amount'])) {
			$amount = (float)$params['amount'];
		}
		elseif (!empty($plan_id)) {
			$this->load->model('plan_model');
			$plan = $this->plan_model->GetPlan($client_id, $plan_id);
			$amount = $plan['amount'];
		}
		else {
			$amount = FALSE;
		}
		
		// coupon check
		if (!empty($coupon)) {
			$this->load->model('coupon_model');
			$coupon = $this->coupon_model->get_coupons($client_id, array('coupon_code' => $coupon));
			$coupon = $coupon[0];
			// set customer_limit == FALSE to not do a customer check
			$coupon['customer_limit'] = FALSE;
		
			if (!empty($coupon) and $this->coupon_model->is_eligible($coupon, $plan_id, FALSE, FALSE)) {
				if (empty($plan_id)) {
					$amount = $this->coupon_model->adjust_amount($amount, $coupon['type_id'], $coupon['reduction_type'], $coupon['reduction_amt']);
				}
				else {
					$recur_amount = $amount;
					$this->coupon_model->subscription_adjust_amount($amount, $recur_amount, $coupon['type_id'], $coupon['reduction_type'], $coupon['reduction_amt']);
				}
				
				$coupon_id = $coupon['id'];
			}
			else {
				$coupon_id = FALSE;
			}
		}
		else {
			$coupon_id = FALSE;
		}
		
		if (empty($coupon_id)) {
			return array('status' => 'invalid');
		}
		else {
			if ($amount !== FALSE) {
				return array('status' => 'valid', 'amount' => $amount);
			}
			else {
				return array('status' => 'valid');
			}
		}
	}
}



/* End of file gateway.php */
/* Location: ./system/opengateway/controllers/gateway.php */