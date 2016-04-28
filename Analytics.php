<?
/**
 * @author Roman Dehtyarev
 */
class Analytics extends Base
{
	var $sPrefix="analytics";
	//-----------------------------------------------------------------------------------------------
	public function __construct()
	{
		Auth::NeedAuth('manager');
		Repository::InitDatabase('analytics_login', false);		
		
		Resource::Get()->Add('/js/jquery.jqplot.js');
		Resource::Get()->Add('/js/jqplot.dateAxisRenderer.js');
		
		Resource::Get()->Add('/css/jquery.jqplot.css',1);
	}
	//-----------------------------------------------------------------------------------------------
	public function Index()
	{
		$this->ShowLoginAnalytics();
	}
	//-----------------------------------------------------------------------------------------------
	public function ShowLoginAnalytics(){
		Base::$aTopPageTemplate=array('panel/tab_analytics.tpl'=>'analytics_login');
		
		if (Base::$aRequest['is_post']) 
		{
			$sWhere = "WHERE 1=1 ";
			if(Base::$aRequest['export_date'])
				$sWhere .= " AND date > '".Base::$aRequest['export'] ['date_from']."'
				AND date < '".Base::$aRequest['export'] ['date_to']."'";

			$sSql = "SELECT * FROM analytics_login ".$sWhere;
				
			$aHeader=array(
				'A'=>array("value"=>'date', 'autosize'=>true),
				'B'=>array("value"=>'count', 'autosize'=>true),
			);

			$this->CreateExcel($aHeader, $sSql, 'analytics_login.xls');
		}

		$aData = Db::GetAll("SELECT * FROM analytics_login ORDER BY date desc");
		Base::$tpl->assign('aDiagramData', $aData);
		Base::$tpl->assign('sMounthAgo',date("m-d-Y",strtotime('-1 month')));
		Base::$tpl->assign('sDiagramHeader',Language::GetMessage('Login diagram'));
		
		Base::$sText .= Base::$tpl->fetch('analytics/diagram.tpl');
		
		$oTable = new Table();
		$oTable->sWidth='100%';
		$oTable->sType="array";
		$oTable->aDataFoTable=$aData;
		$oTable->aColumn['date']=array('sTitle'=>'Date');
		$oTable->aColumn['count']=array('sTitle'=>'Logins');
		$oTable->sDataTemplate='analytics/row_login_analytics.tpl';
		$oTable->iRowPerPage=50;
		$oTable->bStepperVisible=true;
		Base::$sText.=$oTable->getTable('Login analytics');
		
		$aData=array(
		'sHeader'=>"method=post",
		'sContent'=>Base::$tpl->fetch('analytics/form_export_login_analytics.tpl'),
		'sSubmitButton'=>'Get login analytics',
		'sSubmitAction'=>'analytics_login',
		'sError'=>$sError,
		);
		$oForm=new Form($aData);

		Base::$sText.=$oForm->getForm();	
		
	}
	//-----------------------------------------------------------------------------------------------
	public function ShowVinAnalytics(){
		Base::$aTopPageTemplate=array('panel/tab_analytics.tpl'=>'analytics_vin');
		
		if (Base::$aRequest['is_post']) 
		{
			$sWhere = "WHERE 1=1 ";
			if(Base::$aRequest['export_date'])
				$sWhere .= " AND date > '".Base::$aRequest['export'] ['date_from']."'
				AND date < '".Base::$aRequest['export'] ['date_to']."'";
			
			switch (Base::$aRequest['type'])
			{
				case 'days':
					$sSql = "SELECT SUM(count) as count, date FROM analytics_vin ".$sWhere." GROUP BY date ORDER BY date desc";
					$aHeader=array(
						'A'=>array("value"=>'date', 'autosize'=>true),
						'B'=>array("value"=>'count', 'autosize'=>true),
					);
					break;
					
				case 'users':
					$sSql = "SELECT SUM(count) as count, user FROM analytics_vin ".$sWhere." GROUP BY user ORDER BY user desc";
					$aHeader=array(
						'A'=>array("value"=>'user', 'autosize'=>true),
						'B'=>array("value"=>'count', 'autosize'=>true),
					);
					break;
					
				case 'vins':
					$sSql = "SELECT SUM(count) as count, vin FROM analytics_vin ".$sWhere." GROUP BY vin ORDER BY vin desc";
					$aHeader=array(
						'A'=>array("value"=>'vin', 'autosize'=>true),
						'B'=>array("value"=>'count', 'autosize'=>true),
					);
					break;
			
				default:
					$sSql = "SELECT SUM(count) as count, date FROM analytics_vin ".$sWhere." GROUP BY date ORDER BY date desc";
					$aHeader=array(
						'A'=>array("value"=>'date', 'autosize'=>true),
						'B'=>array("value"=>'count', 'autosize'=>true),
					);
					break;
			}

			$this->CreateExcel($aHeader, $sSql, 'analytics_vin_'.Base::$aRequest['type'].'.xls');
		}
				
		
		$aData = Db::GetAll("SELECT SUM(count) as count, date FROM analytics_vin GROUP BY date ORDER BY date desc");
		Base::$tpl->assign('aDiagramData', $aData);
		Base::$tpl->assign('sMounthAgo',date("m-d-Y",strtotime('-1 month')));
		Base::$tpl->assign('sDiagramHeader',Language::GetMessage('VIN diagram'));
		
		Base::$sText .= Base::$tpl->fetch('analytics/diagram.tpl');
		
		//days
		$oTable = new Table();
		$oTable->sWidth='100%';
		$oTable->sSql="SELECT SUM(count) as count, date FROM analytics_vin GROUP BY date ORDER BY date desc";
		$oTable->aColumn['date']=array('sTitle'=>'Date');
		$oTable->aColumn['count']=array('sTitle'=>'Requests');
		$oTable->sDataTemplate='analytics/row_analytics.tpl';
		$oTable->iRowPerPage=50;
		$oTable->bStepperVisible=true;
		Base::$tpl->assign('sTableDays',$oTable->getTable('VIN analytics'));
		//users
		unset($oTable->aColumn);
		$oTable->sSql="SELECT SUM(count) as count, user FROM analytics_vin GROUP BY user ORDER BY user desc";
		$oTable->aColumn['user']=array('sTitle'=>'User');
		$oTable->aColumn['count']=array('sTitle'=>'Requests');
		Base::$tpl->assign('sTableUsers',$oTable->getTable('VIN analytics'));
		//vins
		unset($oTable->aColumn);
		$oTable->sSql="SELECT SUM(count) as count, vin FROM analytics_vin GROUP BY vin ORDER BY vin desc";
		$oTable->aColumn['vin']=array('sTitle'=>'Vin');
		$oTable->aColumn['count']=array('sTitle'=>'Requests');
		Base::$tpl->assign('sTableVins',$oTable->getTable('VIN analytics'));
		
		Base::$sText.=Base::$tpl->fetch("analytics/vin_tables.tpl");
		
		$aData=array(
		'sHeader'=>"method=post",
		'sContent'=>Base::$tpl->fetch('analytics/form_export_vin_analytics.tpl'),
		'sSubmitButton'=>'Get vin analytics',
		'sSubmitAction'=>'analytics_vin',
		'sError'=>$sError,
		);
		$oForm=new Form($aData);

		Base::$sText.=$oForm->getForm();
	}
	//-----------------------------------------------------------------------------------------------
	public function ShowOrdersAnalytics(){
		Base::$aTopPageTemplate=array('panel/tab_analytics.tpl'=>'analytics_orders');
		
		if (Base::$aRequest['is_post']) 
		{
			$sWhere = "WHERE 1=1 ";
			if(Base::$aRequest['export_date'])
				$sWhere .= " AND date >= '".Base::$aRequest['export'] ['date_from']."'
				AND date <= '".Base::$aRequest['export'] ['date_to']."'";

			switch (Base::$aRequest['type'])
			{
				case 'days':
					$sSql = "SELECT SUM(count) as count, date FROM analytics_order ".$sWhere." GROUP BY date ORDER BY date desc";
					$aHeader=array(
						'A'=>array("value"=>'date', 'autosize'=>true),
						'B'=>array("value"=>'count', 'autosize'=>true),
					);
					break;
					
				case 'users':
					$sSql = "SELECT SUM(ass.count) as count , u.login as user, uc.name as name, uc.id_1c as id_1c , um.login as manager 
					FROM analytics_order as ass 
					LEFT JOIN user as u on u.id = ass.id_user
					LEFT JOIN user_customer as uc ON uc.id_user = u.id 
					LEFT JOIN user as um on um.id = uc.id_manager
					 ".$sWhere." and ass.id_user<>0 GROUP BY user ORDER BY user desc";
					$aHeader=array(
						'A'=>array("value"=>'user', 'autosize'=>true),
						'B'=>array("value"=>'name', 'autosize'=>true),
						'C'=>array("value"=>'id_1c', 'autosize'=>true),
						'D'=>array("value"=>'manager', 'autosize'=>true),
						'E'=>array("value"=>'count', 'autosize'=>true),
					);
					break;
					
				default:
					$sSql = "SELECT SUM(count) as count, date FROM analytics_order ".$sWhere." GROUP BY date ORDER BY date desc";
					$aHeader=array(
						'A'=>array("value"=>'date', 'autosize'=>true),
						'B'=>array("value"=>'count', 'autosize'=>true),
					);
					break;
			}	
				

			$this->CreateExcel($aHeader, $sSql, 'analytics_order.xls');
			
		}
		
		$aData = Db::GetAll("SELECT SUM(count) as count, date FROM analytics_order GROUP BY date ORDER BY date desc");
		Base::$tpl->assign('aDiagramData', $aData);
		Base::$tpl->assign('sMounthAgo',date("m-d-Y",strtotime('-1 month')));
		Base::$tpl->assign('sDiagramHeader',Language::GetMessage('Orders diagram'));
		
		Base::$sText .= Base::$tpl->fetch('analytics/diagram.tpl');
					
		$oTable = new Table();
		$oTable->sWidth='100%';
		$oTable->aDataFoTable = $aData;
		$oTable->sType = 'array';
		//$oTable->sSql="SELECT * FROM analytics_order GROUP BY date ORDER BY date desc";
		$oTable->aColumn['date']=array('sTitle'=>'Date');
		$oTable->aColumn['count']=array('sTitle'=>'Orders');
		$oTable->sDataTemplate='analytics/row_analytics.tpl';
		$oTable->iRowPerPage=50;
		$oTable->bStepperVisible=true;
		Base::$tpl->assign('sTableDays',$oTable->getTable('Orders analytics'));
		
		unset($oTable->aColumn);
		$oTable->aDataFoTable=Db::GetAll("SELECT SUM(count) as count, id_user FROM analytics_order WHERE id_user <> 0 GROUP BY id_user ORDER BY count desc");
		$oTable->aColumn['id_1c']=array('sTitle'=>'id 1c');
		$oTable->aColumn['name']=array('sTitle'=>'Name');
		$oTable->aColumn['user']=array('sTitle'=>'User');
		$oTable->aColumn['manager']=array('sTitle'=>'Manager');
		$oTable->aColumn['count']=array('sTitle'=>'Requests');
		$oTable->aCallback=array($this,'CallParseUserId');
		Base::$tpl->assign('sTableUsers',$oTable->getTable('Search analytics'));

		Base::$sText.=Base::$tpl->fetch("analytics/search_tables.tpl");
		
		$aData=array(
		'sHeader'=>"method=post",
		'sContent'=>Base::$tpl->fetch('analytics/form_export_order_analytics.tpl'),
		'sSubmitButton'=>'Get order analytics',
		'sSubmitAction'=>'analytics_orders',
		'sError'=>$sError,
		);
		$oForm=new Form($aData);

		Base::$sText.=$oForm->getForm();
	}
	//-----------------------------------------------------------------------------------------------
	public function ShowSearchAnalytics(){
		Base::$aTopPageTemplate=array('panel/tab_analytics.tpl'=>'analytics_search');
		if (Base::$aRequest['is_post']) 
		{
			$sWhere = "WHERE 1=1 ";
			if(Base::$aRequest['export_date'])
				$sWhere .= " AND date >= '".Base::$aRequest['export'] ['date_from']."'
				AND date <= '".Base::$aRequest['export'] ['date_to']."'";
			
			switch (Base::$aRequest['type'])
			{
				case 'days':
					$sSql = "SELECT SUM(count) as count, date FROM analytics_search ".$sWhere." GROUP BY date ORDER BY date desc";
					$aHeader=array(
						'A'=>array("value"=>'date', 'autosize'=>true),
						'B'=>array("value"=>'count', 'autosize'=>true),
					);
					break;
					
				case 'users':
					$sSql = "SELECT SUM(ass.count) as count , if(ass.user='','неавторизированные', ass.user) as user, uc.name as name, uc.id_1c as id_1c , um.login as manager 
					FROM analytics_search as ass 
					LEFT JOIN user as u on u.login = ass.user
					LEFT JOIN user_customer as uc ON uc.id_user = u.id 
					LEFT JOIN user as um on um.id = uc.id_manager
					 ".$sWhere." GROUP BY user ORDER BY user desc";
					$aHeader=array(
						'A'=>array("value"=>'user', 'autosize'=>true),
						'B'=>array("value"=>'name', 'autosize'=>true),
						'C'=>array("value"=>'id_1c', 'autosize'=>true),
						'D'=>array("value"=>'manager', 'autosize'=>true),
						'E'=>array("value"=>'count', 'autosize'=>true),
					);
					break;
				
				case 'codes':
					$sSql = "SELECT SUM(count) as count, code FROM analytics_search ".$sWhere." GROUP BY code ORDER BY count desc";
					$aHeader=array(
						'A'=>array("value"=>'code', 'autosize'=>true),
						'B'=>array("value"=>'count', 'autosize'=>true),
					);
					break;
			
				default:
					$sSql = "SELECT SUM(count) as count, date FROM analytics_search ".$sWhere." GROUP BY date ORDER BY date desc";
					$aHeader=array(
						'A'=>array("value"=>'date', 'autosize'=>true),
						'B'=>array("value"=>'count', 'autosize'=>true),
					);
					break;
			}

			$this->CreateExcel($aHeader, $sSql, 'analytics_search_'.Base::$aRequest['type'].'.xls');
		}
				
		$aData = Db::GetAll("SELECT SUM(count) as count, date FROM analytics_search GROUP BY date ORDER BY date desc");
		Base::$tpl->assign('aDiagramData', $aData);
		Base::$tpl->assign('sMounthAgo',date("m-d-Y",strtotime('-1 month')));
		Base::$tpl->assign('sDiagramHeader',Language::GetMessage('Search diagram'));
		
		Base::$sText .= Base::$tpl->fetch('analytics/diagram.tpl');
		
		//days
		$oTable = new Table();
		$oTable->sWidth='100%';
		$oTable->sType='array';
		$oTable->aDataFoTable=Db::GetAll("SELECT SUM(count) as count, date FROM analytics_search GROUP BY date ORDER BY date desc");
		$oTable->aColumn['date']=array('sTitle'=>'Date');
		$oTable->aColumn['count']=array('sTitle'=>'Requests');
		$oTable->sDataTemplate='analytics/row_analytics.tpl';
		$oTable->iRowPerPage=30;
		$oTable->bStepperVisible=true;
		Base::$tpl->assign('sTableDays',$oTable->getTable('Search analytics'));
		//users
		unset($oTable->aColumn);
		$oTable->aDataFoTable=Db::GetAll("SELECT SUM(count) as count, code FROM analytics_search GROUP BY code ORDER BY count desc");
		$oTable->aColumn['code']=array('sTitle'=>'Codes');
		$oTable->aColumn['count']=array('sTitle'=>'Requests');
		Base::$tpl->assign('sTableCodes',$oTable->getTable('Search analytics'));
		unset($oTable->aColumn);
		$oTable->aDataFoTable=Db::GetAll("SELECT SUM(count) as count, user FROM analytics_search GROUP BY user ORDER BY count desc");
		$oTable->aColumn['id_1c']=array('sTitle'=>'id 1c');
		$oTable->aColumn['name']=array('sTitle'=>'Name');
		$oTable->aColumn['user']=array('sTitle'=>'User');
		$oTable->aColumn['manager']=array('sTitle'=>'Manager');
		$oTable->aColumn['count']=array('sTitle'=>'Requests');
		$oTable->aCallback=array($this,'CallParseUser');
		Base::$tpl->assign('sTableUsers',$oTable->getTable('Search analytics'));
		//Debug::PrintPre($oTable->iAllRow);
		
		Base::$sText.=Base::$tpl->fetch("analytics/search_tables.tpl");
		
		$aData=array(
		'sHeader'=>"method=post",
		'sContent'=>Base::$tpl->fetch('analytics/form_export_search_analytics.tpl'),
		'sSubmitButton'=>'Get search analytics',
		'sSubmitAction'=>'analytics_search',
		'sError'=>$sError,
		);
		$oForm=new Form($aData);

		Base::$sText.=$oForm->getForm();
	}
	//-----------------------------------------------------------------------------------------------
	public function CallParseUser(&$aItem)
	{
		if ($aItem) {
			foreach($aItem as $key => $aValue) {
				if($aItem[$key]['user']){
					$aUser = Db::GetRow("SELECT uc.name, uc.id_1c, uc.id_manager FROM user_customer as uc INNER JOIN user as u ON u.id = uc.id_user WHERE u.login like '".$aItem[$key]['user']."'");
					$aItem[$key]['name'] = $aUser['name'];
					$aItem[$key]['id_1c'] = $aUser['id_1c'];
					if($aUser['id_manager']){
						$aItem[$key]['manager'] = Db::GetOne("SELECT u.login FROM user_manager as um
						INNER JOIN user as u on u.id = um.id_user WHERE um.id_user = ".$aUser['id_manager']);
						//Debug::PrintPre($aItem[$key]['manager']);
					}
				}
			//	$aItem[$key]['name']= 'asd';
			}
		}
	}
	//-----------------------------------------------------------------------------------------------
	public function CallParseUserId(&$aItem)
	{
		if ($aItem) {
			foreach($aItem as $key => $aValue) {
				if($aItem[$key]['id_user']){
					$aUser = Db::GetRow("SELECT uc.name, uc.id_1c, u.login, uc.id_manager FROM user_customer as uc INNER JOIN user as u on u.id = uc.id_user WHERE uc.id_user = ".$aItem[$key]['id_user']);
					$aItem[$key]['name'] = $aUser['name'];
					$aItem[$key]['id_1c'] = $aUser['id_1c'];
					$aItem[$key]['user'] = $aUser['login'];
					if($aUser['id_manager']){
						$aItem[$key]['manager'] = Db::GetOne("SELECT u.login FROM user_manager as um
						INNER JOIN user as u on u.id = um.id_user WHERE um.id_user = ".$aUser['id_manager']);
						//Debug::PrintPre($aItem[$key]['manager']);
					}
					
				}
			//	$aItem[$key]['name']= 'asd';
			}
		}
	}
	//-----------------------------------------------------------------------------------------------
	public function AddLoginAnalytics()
	{
		//Вся логика в уникальной дате и onсduplicate
		Db::Execute("INSERT INTO `analytics_login`(`date`, `count`) VALUES (CURRENT_DATE,1) ON DUPLICATE KEY UPDATE count=count+1");
	}
	//-----------------------------------------------------------------------------------------------
	public function AddVinAnalytics($sUserId, $sVin)
	{
		//Вся логика в уникальной дате и onсduplicate
		if($sUserId)
			$sUserLogin = Db::GetOne("SELECT login FROM user WHERE id = ".$sUserId);
		if($sUserLogin)
			Db::Execute("INSERT INTO `analytics_vin`(`user`, `vin`, `count`, `date`) VALUES ('".$sUserLogin."','".$sVin."',1,CURRENT_DATE) ON DUPLICATE KEY UPDATE count=count+1");
	}
	//-----------------------------------------------------------------------------------------------
	public function AddOrdersAnalytics($iUserId, $sDate = NULL)
	{
		if(!$iUserId)
			$iUserId = Auth::$aUser['id'];
			
		if(!$sDate)
			$sDate = 'CURRENT_DATE';
		
		//Вся логика в уникальной дате и onсduplicate
		Db::Execute("INSERT INTO `analytics_order`(`date`, `count`, `id_user`) VALUES (".$sDate.",1, '".$iUserId."') ON DUPLICATE KEY UPDATE count=count+1");
	}
	//-----------------------------------------------------------------------------------------------
	public function AddSearchAnalytics($sUserId, $sCode)
	{
		if($sUserId)
			$sUserLogin = Db::GetOne("SELECT login FROM user WHERE id = ".$sUserId);
		
		Db::Execute("INSERT INTO `analytics_search`(`user`, `code`, `count`, `date`) VALUES ('".$sUserLogin."','".$sCode."',1,CURRENT_DATE) ON DUPLICATE KEY UPDATE count=count");
	}
	//-----------------------------------------------------------------------------------------------
	public function CreateExcel($aHeader, $sSql, $sFileName)
	{
		$oExcel = new Excel();

		$oExcel->SetHeaderValue($aHeader,1);
		$oExcel->SetAutoSize($aHeader);
		$oExcel->DuplicateStyleArray("A1:E1");
		
		$aData =Db::GetAll($sSql);
		//Debug::PrintPre($aData);
		if ($aData) {
			$i=$j=2;

			foreach ($aData as $aValue) {
				foreach($aHeader as $sKey => $aValueKey)
					$oExcel->setCellValue($sKey.$i, $aValue[$aValueKey['value']]);
				$i++;
			}

			$sFullFileName='/imgbank/temp_upload/'.$sFileName;
			$oExcel->WriterExcel5(SERVER_PATH.$sFullFileName);

			Base::Redirect($sFullFileName);
			
		}else{
			Base::$sText .= "<span style='color:red'>".Language::GetText('no data for that period')."</span>";
		}
	}
	//-----------------------------------------------------------------------------------------------
	public function addOldOrdersAnalytics(){
		$aCartPackages = Db::GetAll("SELECT * FROM cart_package WHERE order_status not like 'refused'");
		foreach($aCartPackages as $aPackage){
			$sDate = date('Y-m-d',strtotime($aPackage['post_date']));
			Analytics::AddOrdersAnalytics($aPackage['id_user'], "'".$sDate."'");
		}
	}

}
?>