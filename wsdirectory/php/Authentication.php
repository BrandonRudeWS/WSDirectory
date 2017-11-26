<?
	/***
	* class apiCCB
	*
	* @author Jeremiah D. McKinstry <jdmckinstry@gmail.com>
	* @date Wednesday, August 14, 2013
	* @version 1.0.0
	* @see https://cdn6.ccbchurch.com/2/documents/pwt_implement.pdf
	*
	* @copyright This class is free: you can redistribute it and/or modify
	* 		it under the terms of the GNU General Public License, either
	* 		version 3 of the License, or (at your option) any later version.
	*
	* 		This class is distributed in the hope that it will be useful,
	* 		but WITHOUT ANY WARRANTY; without even the implied warranty of
	* 		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	* 		GNU General Public License for more details.
	*
	* 		To receive a copy of the GNU General Public License,
	* 		please see <http://www.gnu.org/licenses/>.
	*
	* 		This class provides the ability to interact with the CCB Post Service API.
	*
	* A few notes:
	*
	* - Be sure to set custom values for the Constants: ACCOUNT_ID & ACCOUNT_PASS
	*   - However, if you intend to call the class using the Constructor Parameters
	*     to set these fields, this is not necessary, simply recomended for ease of use.
	*
	* - this class has not been fully tested
	*
	* - this class is written in PHP 5 and may not be compatable with previous versions.
	*/
	class apiCCB {
		/*	User Fields	*/
		protected $userID;
		protected $userName;
		protected $userPass;
		protected $userType;
		protected $profileName;

		/*	CCB API Fields	*/
		protected $apiAccountID;
		protected $apiAccountPass;
		protected $apiQuery;
		protected $araGroupFinder;
		protected $araLookupServices;
		protected $araOnlineGiving;

		/*	Date Fields	*/
		protected $dateStart;
		protected $dateEnd;

		/*	CCB response output	*/
		protected $response;
		protected $responseArray;
		protected $responseError;
		protected $responseXML;

		/*	constants	*/
		const POST_URL = 'https://westsideajc.ccbchurch.com/api.php';
		/*	IMPORTANT! Please set the following to your own custom Account API information	*/
		const ACCOUNT_ID = 'api@westsideajc.org';
		const ACCOUNT_PASS = 'y8pOzdML83HK1L';

		/*	methods:Constructor|Destructor	*/
		/**
		 * __construct()
		 *
		 * Constructor method, sets the account ID, account Password, and provides options to check a user login upon creation.
		 * These will default to the constant values if not specified.
		 * Also sets default values for: $responseArray, $responseError, $araGroupFinder, $araLookupService, $araOnlineGiving
		 *
		 * @example	$ccb = new apiCCB();
		 * @example	$ccb = new apiCCB('example@name.com', '3x@m413');
		 * @example	$ccb = new apiCCB(NULL, NULL, 'myAccountID', 'myAccountPass');
		 * @example	$ccb = new apiCCB('example@name.com', '3x@m413', 'myAccountID', 'myAccountPass');
		 *
		 * @param	STRING	$userName	User name to check for login status
		 * @param	STRING	$userPass	User password to check for login status
		 * @param	STRING	$accountID	API Account ID assigned to developer. Cannot be NULL!
		 * @param	STRING	$accountPass	API Account Password assigned to developer. Cannot be NULL!
		 */
		public function __construct($userName=NULL, $userPass=NULL, $accountID=self::ACCOUNT_ID, $accountPass=self::ACCOUNT_PASS) {
			$this->setAPIAccountID($accountID); $this->setAPIAccountPass($accountPass);
			$this->responseArray = array(); $this->responseError = '';
			$this->setAraGroupFinder(); $this->setAraLookupServices(); $this->setAraOnlineGiving();
			if (!empty($userName) && !empty($userPass)) $this->checkUserLogin($userName, $userPass);
		}

		public function __destruct() {

		}

		/*	methods:public	*/
		/**
		 * calendarListing()
		 *
		 * The Calendar Listing service will take a range of two dates as input and produce a listing of all calendar events occurring in the given date range.
		 * If the two dates are identical, all events for the given day will be returned.
		 *
		 * @example	$ccb->calendarListing();
		 * @example	$ccb->calendarListing(new DateTime('2012-12-25'));
		 *
		 * @param	STRING $dateStart	The date to begin calendar search on
		 * @param	STRING $dateEnd		The date to end calendar search on
		 *
		 * @return	BOOLEAN
		 */
		public function calendarListing($dateStart=NULL, $dateEnd=NULL) {	//	array( 'srv' => 'meet_day_detail', 'meet_day_id' => 1 )
			$this->setDates(!empty($dateStart) ? $dateStart : new DateTime('1979-01-01'), !empty($dateEnd) ? $dateEnd : new DateTime());
			$a = array( 'srv' => 'public_calendar_listing', 'date_start' => $this->getDateStart() );
			if (!empty($dateEnd)) $a['date_end'] = $this->getDateEnd();
			return $this->lookupService($a);
		}
		/**
		 * checkUserLogin()
		 *
		 * Attempts to login a user based on POST, GET, or passed in Parameters.
		 *
		 * @example	$ccb->checkUserLogin('example@name.com', '3x@m413');
		 *
		 * @param	STRING	$user	The user name to log in.
		 * @param	STRING	$pass	The user's password to log in with.
		 * @param	STRING	$setSession	Wether or not to attempt setting _SESSION['ccb_full_name'] to the individual's full profile name as gathered from 'individual_profile_from_id'.
		 *
		 * @return	BOOLEAN | INT(user_id)
		 */
		public function checkUserLogin($user=NULL, $pass=NULL, $setSession=FALSE) {
			$this->setUserName($user);
			$this->setUserPass($pass);
      echo 'success';
			if (!empty($this->userName) && !empty($this->userPass)) {
				$request = array( 'srv' => 'individual_id_from_login_password', 'login' => $user, 'password' => $pass );
				$this->fetchResults($request);
				if (!empty($this->response)) {
					$xml = $this->getResponse(TRUE);
					$item_count = (string) $xml->response->items['count'][0];
					if ($item_count > 0) {
						$this->setUserID((string) $xml->response->items->item->item_id);
						$this->setUserType((string) $xml->response->items->item->item_id['type']);
						$this->setProfileName();

						if ($this->getProfileName()) {
							if ($setSession) $_SESSION['ccb_full_name'] = $this->getProfileName();
							return $this->getUserID();
						}
					}
				}
			}
			return FALSE;
		}


		/**
		 * customFieldListing()
		 *
		 * The Custom Field Label Listing service gets a listing of all custom field labels defined in the Church Community Builder system.
		 *
		 * @example	$ccb->customFieldListing();
		 *
		 * @return	BOOLEAN
		 */
		public function customFieldListing() {
			$a = array( 'srv' => 'custom_field_labels' );
			return $this->lookupService($a);
		}

		/**
		 * getAllList()
		 *
		 * Returns an array of all available service list.
		 *
		 * @example	$araList = $ccb->getAllList();
		 *
		 * @return ARRAY
		 */
		public function getAllList() {
			$r = array();
			if (!empty($this->userName) && !empty($this->userPass)) {
				foreach ($this->araLookupServices as $k => $v) {
					if (strstr($v, '_list')) {
						$this->lookupService($v);
						$r[$v] = $this->getResponseArray();
					}
				}
			}
			return $r;
		}

		/**
		 * groupFinder()
		 *
		 * The Group Finder service allows you to pass in a set of criteria and gets a listing of the groups that best match the criteria.
		 *
		 * @example	$ccb->groupFinder();
		 * @example	$ccb->groupFinder(array( 'area_id' => 1 ));
		 *
		 * @param ARRAY	$options	Optional Parameters, See Array at $this->getAraGroupFinder
		 */
		public function groupFinder($options=array()) {
			$a = array( 'srv' => 'group_search' );
			if (!empty($options)) foreach ($options as $k=>$v) $a[$k] = $v;
			return $this->lookupService($a);
		}

		/**
		 * insertGift()
		 *
		 * Used to add log of customer gifting money to church.
		 * For a list of Optional Parameters, please see: $this->getAraOnlineGiving();
		 *
		 * @example	$ccb->insertGift(8, 1, '12.85');
		 * @example	$ccb->insertGift(8, 1, '12.85', array( 'merchant_transaction_id' => '11111' ));
		 *
		 * @see	https://cdn6.ccbchurch.com/2/documents/pwt_implement.pdf	-pages:14, 15	-title: Online Giving
		 *
		 * @param	STRING	$catID	coa_category_id
		 * @param	STRING	$individualID	individual_id
		 * @param	STRING	$amount	amount
		 * @param	STRING	$optional	optional parameters allowed by CCB API
		 *
		 * @return	BOOLEAN
		 */
		public function insertGift($catID, $individualID, $amount, $optional=array()) {
			if (isset($catID) && !empty($individualID) && !empty($amount)) {
				$a = array( 'srv' => 'online_giving_insert_gift', 'coa_category_id' => $catID, 'individual_id' => $individualID, 'amount' => $amount );
				if (!empty($optional)) foreach ($optional as $k=>$v) $a[$k] = $v;
				return $this->lookupService($a);
			}
			return FALSE;
		}

		/**
		 * lookupService()
		 *
		 * Attempts to Look Up a Service using the CCB API.
		 *
		 * @example	$ccb->lookupService('meet_day_list');
		 * @example $ccb->lookupService(array( 'meet_day_list' ));
		 * @example $ccb->lookupService(array( 'meet_day_detail', 'meet_day_id' => 1 ));
		 * @example $ccb->lookupService(array( 'srv' => 'meet_day_detail', 'meet_day_id' => 1 ));
		 * @example	$ccb->lookupService('meet_day_detail&meet_day_id=1');
		 *
		 * @see	https://cdn6.ccbchurch.com/2/documents/pwt_implement.pdf	-page:16	-title:Lookup Table Services
		 *
		 * @param	STRING|ARRAY	$service	The service name you would like to look up.
		 */
		public function lookupService($service, $altPost=FALSE) {
			if (is_string($service) && strstr($service, '&')) {
				$tmp = preg_split('/\&/', $service);
				$service = array();
				foreach ($tmp as $k => $v) {
					if (strstr($v, '=')) {
						$t = preg_split('/\=/', $v);
						$service[$t[0]] = $t[1];
					}
					else {
						$service['srv'] = $v;
					}
				}
			}
			if (is_array($service)) {
				$request = array();
				foreach ($service as $k => $v) {
					if ($k === 'service' || $k === 0) $request['srv'] = $v;
					else $request[$k] = $v;
				}
			}
			else {
				$request = array( 'srv' => $service );
			}
			$this->fetchResults($request, $altPost);
			return empty($this->responseError);
		}

		/**
		 * mobileCarrierListing()
		 *
		 * The Mobile Carrier Listing service gets a listing of all mobile carriers defined in the Church Community Builder system along with the email domain used by the carrier
		 *
		 * @example	$ccb->mobileCarrierListing();
		 *
		 * @return	BOOLEAN
		 */
		public function mobileCarrierListing() {
			$a = array( 'srv' => 'mobile_carrier_list' );
			return $this->lookupService($a);
		}

		/**
		 * updateCustomFieldLabels()
		 *
		 * TODO: Test This!
		 *
		 * The Update Custom Field Labels service will accept form-encoded data representing changes to the custom ﬁeld labels and
		 * update the labels in the Church Community Builder system. NOTE: It is possible to set custom ﬁeld labels for custom ﬁelds
		 * that the package you subscribe to does not allow you to use. The ﬁelds available to be set are udf_text_##_label and
		 * udf_text_##_admin, where ## can be 1-12; udf_date_##_label and udf_date_##_admin, where ## can be 1-6;
		 * udf_pulldown_##_label and udf_pulldown_##_admin, where ## can be 1-6; udf_group_pulldown_##_label and
		 * udf_group_pulldown_##_admin, where ## can be 1-3; udf_resource_pulldown_1_label and udf_resource_pulldown_1_admin.
		 *
		 * @example	$ccb->updateCustomFieldLabels(array( 'udf_text_1_label' => 'Custom Field First', 'udf_text_1_admin' => TRUE ));
		 *
		 * @return	BOOLEAN
		 */
		public function updateCustomFieldLabels($fields) {
			if (!empty($fields)) {
				$pat = '/(udf_text_)([2-9]|1[0-2]?)(_label|_admin)|(udf_date_)[1-6](_label|_admin)|(udf_pulldown_)[1-6](_label|_admin)|(udf_group_pulldown_)[1-3](_label|_admin)|(udf_resource_pulldown_)[1-3](_label|_admin)/';
				foreach ($fields as $k=>$v) {
					if (!preg_match($pat, $k)) unset($fields[$k]);
				}
				if (!empty($fields)) {

					$url = self::POST_URL . '?srv=update_custom_ﬁeld_labels';
					$this->setApiQuery('?' . http_build_query($fields));
					$browser = $this->getBrowser();

					$ch = curl_init();

					curl_setopt($ch, CURLOPT_URL, $url); // Set the URL
					curl_setopt($ch, CURLOPT_USERAGENT, $browser); // Cosmetic
					curl_setopt($ch, CURLOPT_USERPWD, $this->apiAccountID.':'.$this->apiAccountPass);	//	Ser User Name and Password for API
					curl_setopt($ch, CURLOPT_POST, 1); // set -d curl setting
					curl_setopt($ch, CURLOPT_POSTFIELDS, ltrim($this->getApiQuery(), '?')); // set post fields
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // If not set, curl prints output to the browser

					$this->setResponse(curl_exec($ch));
					if (curl_errno($ch)) {
						$this->response = ''; $this->responseArray = '';
						$this->setResponseError(curl_error($ch)); return FALSE;
					}
					curl_close($ch);
					return TRUE;
				}
			}
			return FALSE;
		}

		/*	methods:properties get|set	*/
		public function getAPIAccountID() {
			return $this->apiAccountID;
		}
		protected function setAPIAccountID($value) {
			$this->apiAccountID = $value;
		}

		public function getAPIAccountPass() {
			return $this->apiAccountPass;
		}
		protected function setAPIAccountPass($value) {
			$this->apiAccountPass = $value;
		}

		public function getAraGroupFinder() {
			return $this->araGroupFinder;
		}
		protected function setAraGroupFinder() {
			$this->araGroupFinder = array ( 'area_id', 'childcare', 'meet_day_id', 'meet_time_id', 'department_id', 'type_id', 'udf_pulldown_1_id', 'udf_pulldown_2_id', 'udf_pulldown_3_id', 'limit_records_start', 'limit_records_per_page', 'order_by_1', 'order_by_2', 'order_by_3', 'order_by_1_sort', 'order_by_2_sort', 'order_by_3_sort' );
		}

		public function getAraLookupServices() {
			return $this->araLookupServices;
		}
		protected function setAraLookupServices() {
			$this->araLookupServices = array ( 'ability_list', 'ability_detail', 'activity_list', 'activity_detail', 'age_bracket_list', 'age_bracket_detail', 'area_list', 'area_detail', 'church_service_list', 'church_service_detail', 'department_list', 'department_detail', 'event_grouping_list', 'event_grouping_detail', 'gift_list', 'gift_detail', 'how_joined_church_list', 'how_joined_church_detail', 'how_they_heard_list', 'how_they_heard_detail', 'meet_day_list', 'meet_day_detail', 'meet_time_list', 'meet_time_detail', 'membership_type_list', 'membership_type_detail', 'music_style_list', 'music_style_detail', 'passion_list', 'passion_detail', 'reason_left_church_list', 'reason_left_church_detail', 'school_list', 'school_detail', 'significant_event_list', 'significant_event_detail', 'spiritual_maturity_list', 'spiritual_maturity_detail', 'style_list', 'style_detail', 'transaction_grouping_list', 'transaction_grouping_detail', 'udf_grp_pulldown_1_list', 'udf_grp_pulldown_1_detail', 'udf_grp_pulldown_2_list', 'udf_grp_pulldown_2_detail', 'udf_grp_pulldown_3_list', 'udf_grp_pulldown_3_detail', 'udf_ind_pulldown_1_list', 'udf_ind_pulldown_1_detail', 'udf_ind_pulldown_2_list', 'udf_ind_pulldown_2_detail', 'udf_ind_pulldown_3_list', 'udf_ind_pulldown_3_detail', 'udf_ind_pulldown_4_list', 'udf_ind_pulldown_4_detail', 'udf_ind_pulldown_5_list', 'udf_ind_pulldown_5_detail', 'udf_ind_pulldown_6_list', 'udf_ind_pulldown_6_detail', 'udf_resource_pulldown_1_list', 'udf_resource_pulldown_1_detail' );
		}

		public function getAraOnlineGiving() {
			return $this->araOnlineGiving;
		}
		protected function setAraOnlineGiving() {
			$this->araOnlineGiving = array ( 'merchant_transaction_id', 'merchant_authorization_code', 'merchant_notes', 'first_name', 'last_name', 'street_address', 'city', 'state', 'zip', 'email', 'phone', 'payment_method_type' );
		}

		public function getApiQuery() {	//	always returns LAST query STRING
			return $this->apiQuery;
		}
		protected function setApiQuery($qry) {
			$this->apiQuery = $qry;
		}

		public function getDateEnd() {
			return $this->dateEnd;
		}
		protected function setDateEnd($date) {
			if ($date instanceof DateTime) $date = $date->format('Y-m-d');
			$this->dateEnd = $date;
		}

		public function getDates() {
			return array( 'start' => $this->dateStart, 'end' => $this->dateEnd );
		}
		protected function setDates($start, $end) {
			$this->setDateStart($start);
			$this->setDateEnd($end);
		}

		public function getDateStart() {
			return $this->dateStart;
		}
		protected function setDateStart($date) {
			if ($date instanceof DateTime) $date = $date->format('Y-m-d');
			$this->dateStart = $date;
		}

		public function getProfileName() {
			return $this->profileName;
		}
		protected function setProfileName() {
			if (!empty($this->userID)) {
				$request = array( 'srv' => 'individual_profile_from_id', 'individual_id' => $this->getUserID() );
				$this->fetchResults($request);
				if (!empty($this->response)) {
					$xml = $this->getResponse(TRUE);
					if (!empty($xml)) {
						$individual_count = (string) $xml->response->individuals['count'][0];
						if ($individual_count > 0) $this->profileName = (string) $xml->response->individuals->individual->full_name;
					}
				}
			}
		}

		public function getResponse($asXML=FALSE) {
			return $asXML ? $this->responseXML : $this->response;
		}
		public function getResponseArray() {
			return $this->responseArray;
		}
		protected function setResponse($response) {
			$this->response = $response;
			if (!empty($response) && is_string($response)) {
				try {
					$xml = new SimpleXMLElement($this->response);
					$this->responseXML = $xml;
					$this->responseArray = json_decode(json_encode($xml),1);
					$this->responseError = '';
					if (isset($this->responseArray["response"])) {
						if (isset($this->responseArray["response"]["error"])) {
							if (count($this->responseArray["response"]["error"])) $this->setResponseError($this->responseArray["response"]["error"]);
						}
					}
					elseif (isset($this->responseArray["error"])) {
						if (count($this->responseArray["error"])) $this->setResponseError($this->responseArray["error"]);
					}
				}
				catch (Exception $e) {
					$this->responseError = $e;
					return NULL;
				}
			}
		}

		public function getResponseError() {
			return $this->responseError;
		}
		public function setResponseError($error) {
			$this->responseError = $error;
		}

		public function getUserID() {
			return $this->userID;
		}
		protected function setUserID($userID) {
			$this->userID = $userID;
		}

		public function getUserName() {
			return $this->userName;
		}
		protected function setUserName($userName=NULL) {
			if (empty($userName)) {
				if (isset($_POST['ccb_username'])) $userName = $_POST['ccb_username'];
				elseif (isset($_GET['ccb_username'])) $userName = $_GET['ccb_username'];
			}
			$this->userName = $userName;
		}

		public function getUserPass() {
			return $this->userPass;
		}
		protected function setUserPass($userPass=NULL) {
			if (empty($userPass)) {
				if (isset($_POST['ccb_password'])) $userPass = $_POST['ccb_password'];
				elseif (isset($_GET['ccb_password'])) $userPass = $_GET['ccb_password'];
			}
			$this->userPass = $userPass;
		}

		public function getUserType() {
			return $this->userType;
		}
		protected function setUserType($userType=NULL) {
			$this->userType = $userType;
		}

		/*	methods:protected	*/
		protected final function fetchResults($request, $altPost=FALSE) {
			if (!empty($request)) {
				if (is_array($request)) $request = '?' . http_build_query($request);
				if (is_string($request)) {
					$this->setApiQuery($request);
					$browser = $this->getBrowser();
					$ch = curl_init();
					if (!$altPost) {
						curl_setopt($ch, CURLOPT_URL, self::POST_URL . $this->getApiQuery()); // Set the URL
						curl_setopt($ch, CURLOPT_USERAGENT, $browser); // Cosmetic
						curl_setopt($ch, CURLOPT_USERPWD, $this->apiAccountID.':'.$this->apiAccountPass);	//	Ser User Name and Password for API
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // If not set, curl prints output to the browser
						curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
					}
					else {
						curl_setopt($ch, CURLOPT_URL, self::POST_URL); // Set the URL
						curl_setopt($ch, CURLOPT_USERAGENT, $browser); // Cosmetic
						curl_setopt($ch, CURLOPT_USERPWD, $this->apiAccountID.':'.$this->apiAccountPass);	//	Ser User Name and Password for API
						curl_setopt($ch, CURLOPT_POST, 1); // set -d curl setting
						curl_setopt($ch, CURLOPT_POSTFIELDS, ltrim($this->getApiQuery(), '?')); // set post fields
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // If not set, curl prints output to the browser
					}
					$this->setResponse(curl_exec($ch));
					if (curl_errno($ch)) {
						$this->response = ''; $this->responseArray = '';
						$this->setResponseError(curl_error($ch));
						return FALSE;
					}
					curl_close($ch);
				}
			}
		}

		/*	methods:helpers	*/
		protected function getBrowser($asArray=FALSE) {
			$u_agent = $_SERVER['HTTP_USER_AGENT'];
			$bname = 'Unknown';
			$platform = 'Unknown';
			$version= "";

			//First get the platform?
			if (preg_match('/linux/i', $u_agent)) {
				$platform = 'linux';
			}
			elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
				$platform = 'mac';
			}
			elseif (preg_match('/windows|win32/i', $u_agent)) {
				$platform = 'windows';
			}

			// Next get the name of the useragent yes seperately and for good reason
			if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) {
				$bname = 'Internet Explorer';
				$ub = "MSIE";
			}
			elseif(preg_match('/Firefox/i',$u_agent)) {
				$bname = 'Mozilla Firefox';
				$ub = "Firefox";
			}
			elseif(preg_match('/Chrome/i',$u_agent)) {
				$bname = 'Google Chrome';
				$ub = "Chrome";
			}
			elseif(preg_match('/Safari/i',$u_agent)) {
				$bname = 'Apple Safari';
				$ub = "Safari";
			}
			elseif(preg_match('/Opera/i',$u_agent)) {
				$bname = 'Opera';
				$ub = "Opera";
			}
			elseif(preg_match('/Netscape/i',$u_agent)) {
				$bname = 'Netscape';
				$ub = "Netscape";
			}

			// finally get the correct version number
			$known = array('Version', $ub, 'other');
			$pattern = '#(?<browser>' . join('|', $known) .
			')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
			if (!preg_match_all($pattern, $u_agent, $matches)) {
				// we have no matching number just continue
			}

			// see how many we have
			$i = count($matches['browser']);
			if ($i != 1) {
				//we will have two since we are not using 'other' argument yet
				//see if version is before or after the name
				if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
					$version= $matches['version'][0];
				}
				else {
					$version= $matches['version'][1];
				}
			}
			else {
				$version= $matches['version'][0];
			}

			// check if we have a number
			if ($version==null || $version=="") {$version="?";}

			$a = array(
				'userAgent' => $u_agent,
				'name'      => $bname,
				'version'   => $version,
				'platform'  => $platform,
				'pattern'    => $pattern
			);

			$s = $a['name'] . '/' .$a['version'] . ' (platform: ' . $a['platform'] . ')';

			return $asArray ?  $a : $s;
		}

	}

?>
