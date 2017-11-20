<?php

$envs = array(
  "BNZ_PROD_WG" => array(
    "dcshost" => "bnz-wg-prod-dcs-01",
    "dathost" => "bnz-wg-prod-dat-01",
    "nas" => "bnz-wg-prod-dat-01",
	"webserver" => "bnz-wg-prod-http-01",
    "oam" => "bnz-wg-prod-oam-01",
  ),
  "BNZ_PROD_AK" => array(
    "dcshost" => "bnz-ak-dr-dcs-01",
    "dathost" => "bnz-ak-dr-dat-01",
    "nas" => "bnz-ak-dr-dat-01",
	"webserver" => "bnz-ak-dr-http-01",
    "oam" => "bnz-ak-dr-oam-02",
  ),
  "TEST11" => array(
    "dcshost" => "vr2-wg-test-dcs-11",
    "dathost" => "vr2-wg-test-dat-11",
    "nas" => "vr2-wg-test-nas-11",
	"webserver" => "vr2-wg-test-http-11",
    "oam" => "vr2-wg-test-oam-11",
  ),
  "TEST12" => array(
    "dcshost" => "vr2-wg-test-dcs-12",
    "dathost" => "vr2-wg-test-dat-12",
    "nas" => "vr2-wg-test-nas-12",
    "webserver" => "vr2-wg-test-http-12",
    "oam" => "vr2-wg-test-oam-12",
  ),

  "DEV11" => array(
    "dcshost" => "vr2-wg-dev-dcs-11",
    "dathost" => "vr2-wg-dev-dat-11",
    "nas" => "vr2-wg-dev-nas-11",
    "webserver" => "vr2-wg-dev-http-11",
    "oam" => "vr2-wg-dev-oam-11",
    ),
  "PROD_MERGE" => array(
    "dcshost" => "vr2-wg-prod-dcs-11",
    "dathost" => "vr2-wg-prod-dat-11",
    "nas" => "vr2-wg-prod-nas-11",
	"dbname"	=> "vretrieve",
	"webserver" => "vr2-wg-prod-http-11",
	"oam" => "vr2-wg-prod-oam-11",
  ),
  "UAT-11" => array(
    "dcshost" => "vr2-wg-uat-dcs-11",
    "dathost" => "vr2-wg-uat-dat-11",
    "nas" => "vr2-wg-uat-nas-11",
	"dbname"	=> "vretrieve",
	"webserver" => "vr2-wg-uat-http-11",
	"oam" => "vr2-wg-uat-oam-11",
  )

);

// Array with appname as key, 
$vr_apps = array(
	"dcstomcat" => array(
	"host" => "dcshost",
	),
	"imtomcat" => array(
	"product" => "OpenAM Tomcat",
	"type" => "Identity Management Server",
    "host" => "oam",
  ),
	"datastore" => array(
	"product" => "OpenDS",
	"type" => "Identity Management Datastore",
    "host" => "oam",
  ),
	"webserver" => array(
	"product" => "Apache HTTPD",
	"type" => "Webserver",
	"host" => "webserver",
	),
	"database" =>  array(
	"product" => "Postgresql",
	"type" => "Database",
    "host" => "dathost",
	),
	"core" =>  array(
	"product" => "VR Core",
	"type" => "VR Core",
    "host" => "dcshost",
	),

);

?>
