<?php
define('__ROOT__', dirname(dirname(__FILE__)));
define("__CONFIG_DIR__" , "/config");
define("__CORE_DIR__" , "/core");

// define tables
define("TBL_TENANT", "core_tenant");
// confoguration at app level, tenant_level, user level
define("TBL_CONFIGURATION" , "core_configuration");
define("TBL_CONFIGURATION_TYPE", "core_configuration_type");
define("TBL_CONFIGURATION_TYPE_OPTION", "core_configuration_type_option");
define("TBL_ROUTE", "core_dynamic_route");

define("TBL_TOKEN", "core_token");
define("TBL_LOGIN_HISTORY", "core_login_history");
define("TBL_ACCESS_RIGHTS", "access_rights");


// title, gender, marital status, country , collection refresh duration, collection field types, language types, counties
define("TBL_KEY", "key_entry");
define("TBL_KEY_TYPE", "key_type");
define("TBL_KEY_TYPE_ROLE" , "key_type_role");

define("TBL_LANGUAGE", "view_language");
define("TBL_TEMPLATE", "view_template");

define("TBL_PERSON", "person");
define("TBL_VENUE", "venue");
define("TBL_DEPARTMENT", "venue_department");
define("TBL_POSITION", "position");
define("TBL_ADDRESS", "address");

define("TBL_CHAT_HEADER","chat_header");
define("TBL_CHAT_CONVERSATION","chat_conversation");
define("TBL_ADDRESS_BOOK","chat_address_book");

// ------------------------------------------------------------------------------------------------------------------


define("KEY_TYPE_TITLES" , 1);
define("KEY_TYPE_GENDER" , 2);
define("KEY_TYPE_MARITAL_STATUS" , 3);
define("KEY_TYPE_COUNTRIES" , 4);
define("KEY_TYPE_QUALIFICATION", 5);
define("KEY_TYPE_SUBJECT" , 6);

define("FEATURE_ACCOUNT_LOGIN", 1);
define("FEATURE_FORGOT_PASSWORD", 2);
define("FEATURE_FORGOT_PASSWORD_RESPONSE" , 3);
define("FEATURE_ACCOUNT_CREATE", 4);
define("FEATURE_ACCOUNT_CREATE_SAVE", 5);
define("FEATURE_ACCOUNT_UPDATE", 6);
define("FEATURE_ACCOUNT_LIST", 7);
define("FEATURE_ACCOUNT_DELETE", 8);
define("FEATURE_ACCOUNT_SECURITY_GET", 9);
define("FEATURE_ACCOUNT_SECURITY_UPDATE" , 10);
define("FEATURE_GET_HTML_TEMPLATE" , 11);
define("FEATURE_LOCATION_LIST" , 12);
define("FEATURE_LOCATION_ADD" , 13);
define("FEATURE_LOCATION_UPDATE" , 14);
define("FEATURE_LOCATION_DELETE" , 15);
define("FEATURE_CHAT_LIST" , 16);
define("FEATURE_CHAT_LISTEN" , 17);
define("FEATURE_CHAT_SPEAK" , 18);
define("FEATURE_CHAT_ADDRESS_BOOK" , 19);
define("FEATURE_CHAT_ADDRESS_DOCUMENT" , 20);
define("FEATURE_COLLECTION_TYPES_GET" , 21);
//define("FEATURE_COLLECTION_TYPES_GET" , 22);
define("FEATURE_COLLECTION_TYPE_CREATE", 23);
define("FEATURE_COLLECTION_TYPE_UPDATE", 24);
define("FEATURE_COLLECTION_TYPE_DELETE", 25);
define("FEATURE_COLLECTION_RECORD_LIST" , 26);
define("FEATURE_COLLECTION_RECORD_GET" , 27);
define("FEATURE_COLLECTION_RECORD_ADD" , 27);
define("FEATURE_COLLECTION_RECORD_DELETE" , 28);
define("FEATURE_COLLECTION_RECORD_UPDATE" , 29);
//define("FEATURE_COLLECTION_RECORD_DELETE" , 30);

define("FEATURE_ACCOUNT_LOGOFF" , 31);


define("ACCESS_READ", 10);
define("ACCESS_WRITE", 20);
define("ACCESS_ADMIN", 30);

define("NOT_EQUAL",	1);
define("EQUAL", 2);
define("LESSTHAN", 3);
define("GREATERTHAN", 4);
define("LESSOREQUAL",	5);
define("GREATEROREQUAL", 6);
define("IN", 7);
define("NOTIN",8);

// ------------------------------------------------------------------------------------------------------------------

// {fieldname,type,caption,formtype,mandatory,validation type}
	$tables["core_tenant"] = array("id,int,,hidden,yes,intval", 
								   "tenant_name,tinytext,Tenant Name,text,yes,none", 
								   "status,tinyint,status,select,yes,none", 
								   "created,datetime,Created,hidden,yes,none",  
								   "address,text,Address,textarea,no,none", 
								   "email_address,tinytext,Email Address,text,yes,none", 
								   "contact_name,tinytext,Contact Name,text,yes,none");
								   
// {fieldname,type,caption,formtype,mandatory,validation type}	
	$tables["core_configuration"] = array("id,int,,hidden,yes,intval", 
										  "tenant_id,int,,hidden,yes,intval", 
										  "created,datetime,Created,hidden,yes,none", 
										  "status,tinyint,status,select,yes,none", 
										  "config_key,int,,hidden,yes,intval", 
										  "config_value,tinytext,Config Value,text,yes,none");									  
	
	$tables["core_configuration_type"] = array("id,int,,hidden,yes,intval,",
											   "created,datetime,Created,hidden,yes,none", 
											   "status,tinyint,status,select,yes,none", 
											   "config_key,int,,hidden,yes,intval", 
											   "default_value,tinytext,Email Address,text,yes,none");
											   
											   
	$tables["core_configuration_type_option"] = array("id,int,,hidden,yes,intval,", 
													  "configuration_type_id,intval,Option Key,tinytext,yes,none", 
													  "option_key,tinytext,Option Key,tinytext,yes,none", 
													  "option_value,tinytext,Option Value,text,yes,none");
													  
													  
	$tables["core_dynamic_route"] = array("id,int,,hidden,yes,intval,", 
										  "route,tinytext,Email Address,text,yes,none", 
										  "controller,tinytext,Email Address,text,yes,none", 
										  "method,tinytext,Email Address,text,yes,none", 
										  "parameters,tinytext,Email Address,text,yes,none");
										  
	$tables["core_token"] 		   = array("id,int,,hidden,yes,intval,", 
										  "tenant_id,int,,hidden,yes,intval,",
										  "login_id,int,,hidden,yes,intval,",
										  "created,datetime,,hidden,datetime,",
										  "status,shortint,,hidden,intval",
										  "token,tinytext,token string,text,yes,none");
	
	// id,
	$tables["core_access"]	= array();
	
	$tables["core_login_history"] = array("id,int,,hidden,yes,intval", 
										  "login_id,int,,hidden,yes,intval", 
										  "username,tinytext,Email Address,text,yes,none", 
										  "created,datetime,Created,hidden,yes,none", 
										  "result,tinytext,Result,text,yes,none");
	
	$tables["core_login"] = array("login_id,int,,hidden,yes,intval",
								  "created,datetime,,hidden,datetime,",
								  "status,shortint,,hidden,intval",
								  "username,tinytext,Email Address,text,yes,none");									  
										  

	$tables["key"] = array("id,int,,hidden,yes,intval", 
						   "status,tinyint,status,select,yes,none", 
						   "created,datetime,Created,hidden,yes,none", 
						   "closed_set_type_id,int,,hidden,yes,intval", 
						   "tenant_id,int,,hidden,yes,intval", 
						   "resolved_role_type_id,,", 
						   "role_id,int,,hidden,yes,intval", 
						   "data_key,tinytext,Data key,text,yes,none", 
						   "data_value,tinytext,Data Value,text,yes,none");
						   
						   
	$tables["key_type"] = array("id,int,,hidden,yes,intval", 
								"status,tinyint,status,select,yes,none", 
								"key_type_name,tinytext,Key type name,yes,none");
								
								
	$tables["key_type_role"] = array("id,int,,hidden,yes,intval", 
									 "tenant_id,int,,hidden,yes,intval", 
									 "key_type_id,int,,hidden,yes,intval", 
									 "resolved_role_type_id,int,,hidden,yes,intval");


	$tables["view_language"] = array("id,int,,hidden,yes,intval",
									 "tenant_id,int,,hidden,yes,intval",
									 "created,datetime,Created,hidden,yes,none",
									 "created_by,int,,hidden,yes,intval",
									 "status,tinyint,status,select,yes,none",
									 "action,string,,hidden,yes,intval",
									 "language,string,,hidden,yes,intval",
									 "variables,string,,hidden,yes,intval");
									 
/*	$tables["view_template"] = array("id,int,,hidden,yes,intval", 
									"tenant_id,int,,hidden,yes,intval", 
									"created,datetime,Created,hidden,yes,none", 
									"created_by,int,,hidden,yes,intval",, 
									"action,string,,hidden,yes,intval", 
									"language,tinytext,,hidden,yes,intval", 
									"template,text,,textarea,yes,none" );
*/									
								
	$tables["person"] = array("id,int,,hidden,yes,intval",
							  "created_by,int,,hidden,yes,intval",
							  "created,datetime,Created,hidden,yes,none",
							  "status,tinyint,status,select,yes,none",
							  "tenant_id,int,,hidden,yes,intval",
							  "login_id,int,,hidden,yes,intval",
							  "first_name,tinytext,text,yes,none",
							  "middle_name,tinytext,,text,yes,none",
							  "last_name,tinytext,,text,yes,none",
							  "title,int,,hidden,yes,intval",
							  "marital_status,int,,hidden,yes,intval",
							  "address_id,int,,hidden,yes,intval",
							  "mobile_number,tinytext,,text,yes,none",
							  "email_address,tinytext,,text,yes,none",
							  "role_type_id,int,,hidden,yes,intval",
							  "post_type_id,int,,hidden,yes,intval",
							  "default_venue,tinytext,,text,yes,none");

	$tables["venue"] = array("id,int,,hidden,yes,intval", 
							 "tenant_id,int,,hidden,yes,intval", 
							 "created,datetime,Created,hidden,yes,none", 
							 "created_by,int,,hidden,yes,intval", 
							 "status,int,,hidden,yes,intval", 
							 "venue_name,tinytext,,text,yes,none", 
							 "address_id,int,,hidden,yes,intval", 
							 "email_address,tinytext,,text,yes,none", 
							 "manager_id,int,,hidden,yes,intval");
							 
							 
	$tables["venue_department"] = array("id,int,,hidden,yes,intval", 
										"tenant_id,int,,hidden,yes,intval", 
										"created,datetime,Created,hidden,yes,none", 
										"created_by,int,,hidden,yes,intval",
										"status,tinyint,status,select,yes,none", 
										"venue_id,int,,select,yes,intval", 
										"department_name,tinytext,,text,yes,none");
										
										
	$tables["position"] = array("id,int,,hidden,yes,intval", 
								"tenant_id,int,,hidden,yes,intval", 
								"created,datetime,Created,hidden,yes,none", 
								"created_by,int,,hidden,yes,intval",
								"status,tinyint,status,select,yes,none", 
								"position_name,tinytext,,text,yes,none");
								
	$tables["address"] = array("id,int,,hidden,yes,intval",
							   "address_1,tinytext,,text,yes,none",
							   "address_2,tinytext,,text,yes,none",
							   "city,tinytext,,text,yes,none",
							   "county,tinytext,,text,yes,none",
							   "postcode,tinytext,,text,yes,none",
							   "created,datetime,Created,hidden,yes,none",
							   "remove_date,datetime,remove date,hidden,yes,none",
							   "status,tinyint,status,select,yes,none");

// Chat
	$tables["chat_header"] = array("id,int,,hidden,yes,intval", 
								   "created,datetime,Created,hidden,yes,none", 
								   "status,tinyint,status,select,yes,none", 
								   "speaker_id,int,,hidden,yes,intval", 
								   "listener_id,int,,hidden,yes,intval");
								   
	$tables["chat_conversation"] = array("id,int,,hidden,yes,intval", 
										 "conversation_id,int,,hidden,yes,intval", 
										 "created,datetime,Created,hidden,yes,none", 
										 "status,tinyint,status,select,yes,none", 
										 "chat,text,,hidden,yes,intval", 
										 "speaker_id,int,,hidden,yes,intval" );
	
	$tables["chat_address_book"] = array("id,int,,hidden,yes,intval", 
										 "tenant_id,int,,hidden,yes,intval", 
										 "person_id,int,,hidden,yes,intval", 
										 "other_person_id,int,,hidden,yes,intval");

// -----------------------------------------

// Meta tables....
$tables["meta_table"] = array("id,int,,hidden,yes,intval", 
							  "tenant_id,int,,hidden,yes,intval", 
							  "created,datetime,Created,hidden,yes,none", 
							  "created_by,int,,hidden,yes,intval",
							  "status,tinyint,status,select,yes,none", 
							  "table_name,tinytext,,text,yes,none");
							  
$tables["meta_table_field"] = array( "id,int,,hidden,yes,intval", 
									 "meta_table_id,int,,select,yes,intval", 
									 "field_name,tinytext,,text,yes,none", 
									 "field_type,tinytext,,text,yes,none", 
									 "caption,tinytext,,text,yes,none", 
									 "validation_type,tinytext,,text,yes,none", 
									 "default_value,tinytext,,text,yes,none", 
									 "link_field,tinytext,,text,yes,none", 
									 "order,int,,hidden,yes,intval", 
									 "mandatory,tinyint,,text,yes,none");

// Define collections...
$tables["collection_type"] = array("id,int,,hidden,yes,intval", 
								   "tenant_id,int,,hidden,yes,intval", 
								   "created,datetime,Created,hidden,yes,none", 
								   "created_by,int,,hidden,yes,intval", 
								   "status,tinyint,status,select,yes,none", 
								   "collection_type,tinyint,Collection Type,select,yes,none", 
								   "collection_name,text,Collection Name,select,yes,none", 
								   "period,int,,hidden,yes,intval");

$tables["collection_record"] = array("id,int,,hidden,yes,intval", 
									 "collection_type_id,int,,hidden,yes,intval", 
									 "tenant_id,int,,hidden,yes,intval", 
									 "venue_id,int,,select,yes,intval", 
									 "created,datetime,Created,hidden,yes,none", 
									 "created_by,int,,hidden,yes,intval", 
									 "status,tinyint,status,select,yes,none", 
									 "next_due,datetime,Next Due,hidden,yes,none", 
									 "latest,created_by,boolean,,hidden,yes,intval");									 
									 
$tables["collection_record_field"] = array("id,int,,hidden,yes,intval", 
										   "tenant_id,int,,hidden,yes,intval", 
										   "collection_type_id,int,,hidden,yes,intval", 
										   "status,tinyint,status,select,yes,none",  
										   "collection_typed_field_id,int,,hidden,yes,intval", 
										   "value,text,,");
										   
										   
$tables["collection_venue_assigned"] = array("id,int,,hidden,yes,intval", 
											 "tenant_id,int,,hidden,yes,intval", 
											 "collection_type_id,int,,hidden,yes,intval", 
											 "venue_id,int,,select,yes,intval");
											 

$tables["nodes"] = array("id,int,,hidden,yes,intval", 
						 "tenant_id,int,,hidden,yes,intval", 
						 "node_group_type,tinyint,Node Group Type,,hidden,yes,none", 
						 "parent_id,int,,hidden,yes,intval", 
						 "node_name,,tinytext,,text,yes,none");

// Documents
$tables["document_type"] = array("id,int,,hidden,yes,intval", 
								 "tenant_id,int,,hidden,yes,intval", 
								 "document_type_name,tinytext,Document Type Name,text,yes,none", 
								 "document_type_description,tinytext,Document Type Description,text,yes,none", 
								 "upload_path,tinytext,,text,yes,none",
								 "format_type,tinytext,,text,yes,none", 
								 "max_size,int,,text,yes,intval");
								 
								 
$tables["document"] = array("id,int,,hidden,yes,intval", 
							"tenant_id,int,,hidden,yes,intval", 
							"document_type_id,int,,hidden,yes,intval", 
							"created,datetime,Created,hidden,yes,none", 
							"created_by,int,,hidden,yes,intval", 
							"status,tinyint,status,select,yes,none", 
							"document_name,tinytext,,text,yes,none", 
							"document_server_name,tinytext,,text,yes,none");
											 
											 
											 
$tables["collection_type_field"] = array("id,int,,hidden,yes,intval", 
										 "tenant_id,int,,hidden,yes,intval", 
										 "field_type,tinyint,,text,yes,none", 
										 "caption,tinytext,,text,yes,none", 
										 "options,tinytext,,text,yes,none", 
										 "default_value,tinytext,,text,yes,none", 
										 "validation_type,tinytext,,text,yes,none",
										 "validation_data,tinytext,,text,yes,none");
										 											 
											 

// Document_selection
/*
$tables["document_selection"] = array("id,int,,hidden,yes,intval", 
									  "tenant_id,int,,hidden,yes,intval", 
									  "created,datetime,Created,hidden,yes,none", 
									  "created_by" , 
									  "status", 
									  "position_id", 
									  "selection_name" );


$tables["document_selection_element"] = array("id,int,,hidden,yes,intval", 
											  "document_selection_id", 
											  "document_type_id", 
											  "mandatory");
*/

// learning 
//$tables["learning_entry"] = array();
//$tables["learning_position"] = array();


// appointments
//$tables["appointment_slot"] = array();
//$tables[""] = array();




//---------------------------------------------------------------------------------------------------------------------------------------------------

