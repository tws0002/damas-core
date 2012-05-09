<?php
/**
 * SOAP web service of DAMAS software (damas-software.org)
 *
 * Copyright 2005-2012 Remy Lalanne
 *
 * This file is part of damas-core.
 *
 * damas-core is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * damas-core is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with damas-core.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
session_start();

include_once "service.php";
include_once "../php/data_model_1.xml.php";

damas_service::init_http();
damas_service::accessGranted();
damas_service::allowed( "model::" . arg("cmd") );

header('Content-type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
echo "<!-- generated by " . $_SERVER['SCRIPT_NAME'] . " -->\n";
if( arg("xsl") )
	echo '<?xml-stylesheet type="text/xsl" href="' . arg("xsl") . '"?>' . "\n";
echo '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">' . "\n";
echo "\t<soap:Header>\n";

$start_time = microtime();
$err = $ERR_NOERROR;
$ret = false;
$cmd = arg("cmd");

switch( $cmd )
{
	case "createNode":
		if( is_null( arg('id') ) || is_null( arg('type') ) ){
			$err = $ERR_COMMAND; break;
		}
		$id = model::createNode( arg("id"), arg("type") );
		if( !$id ) $err = $ERR_NODE_CREATE;
		if( $id )
			$ret = model_xml::node( $id, 1, $NODE_TAG | $NODE_PRM );
		else
			$ret = false;
		break;
	case "duplicate":
		if( is_null( arg('id') ) ){
			$err = $ERR_COMMAND; break;
		}
		$id = model::copyBranch( arg("id"), false );
		if( !$id) $err = $ERR_NODE_CREATE;
		if( $id )
			$ret = model_xml::node( $id, 1, $NODE_TAG | $NODE_PRM );
		else
			$ret = false;
		break;
	case "removeNode":
		if( is_null( arg('id') ) ){
			$err = $ERR_COMMAND; break;
		}
		$ret = model::removeNode( arg("id") );
		if( !$ret ) $err = $ERR_NODE_DELETE;
		break;
	case "setKey":
		if( is_null( arg('id') ) || is_null( arg('name') ) || is_null( arg('value') ) ){
			$err = $ERR_COMMAND; break;
		}
		$ret = model::setKey( arg("id"), arg("name"), arg("value") );
		if( !$ret ) $err = $ERR_NODE_UPDATE;
		break;
	case "removeKey":
		if( is_null( arg('id') ) || is_null( arg('name') ) ){
			$err = $ERR_COMMAND; break;
		}
		$ret = model::removeKey( arg("id"), arg("name") );
		if( !$ret) $err = $ERR_NODE_UPDATE;
		break;
	case "move":
		if( is_null( arg('id') ) || is_null( arg('target') ) ){
			$err = $ERR_COMMAND; break;
		}
		$ret = model::move( arg("id"), arg("target") );
		if( !$ret) $err = $ERR_NODE_MOVE;
		break;
	case "tag":
		if( is_null( arg('id') ) || is_null( arg('name') ) ){
			$err = $ERR_COMMAND; break;
		}
		$ret = model::tag( arg("id"), arg("name") );
		if( !$ret ) $err = $ERR_NODE_UPDATE;
		break;
	case "untag":
		if( is_null( arg('id') ) || is_null( arg('name') ) ){
			$err = $ERR_COMMAND; break;
		}
		$ret = model::untag( arg("id"), arg("name") );
		if( !$ret) $err = $ERR_NODE_UPDATE;
		break;
	case "link":
		if( is_null( arg('src') ) || is_null( arg('tgt') ) )
		{
			$err = $ERR_COMMAND; break;
		}
		$ret = model::link( arg("src"), arg("tgt") );
		if( !$ret) $err = $ERR_NODE_UPDATE;
		break;
	case "unlink":
		if( is_null( arg('id') ) ){
			$err = $ERR_COMMAND; break;
		}
		$ret = model::unlink( arg("id") );
		if( !$ret) $err = $ERR_NODE_UPDATE;
		break;
	case "setType":
		if( is_null( arg('id') ) || is_null( arg('type') ) ){
			$err = $ERR_COMMAND; break;
		}
		$ret = model::setType( arg("id"), arg("type") );
		if( !$ret) $err = $ERR_NODE_UPDATE;
		break;
	case "setTags":
		if( is_null( arg('id') ) || is_null( arg('tags') ) ){
			$err = $ERR_COMMAND; break;
		}
		$ret = model::setTags( arg("id"), arg("tags") );
		if( !$ret ) $err = $ERR_NODE_UPDATE;
		break;
	case "setKeys":
		if( is_null( arg('id') ) || is_null( arg('old') ) || is_null ( arg('new') ) ){
			$err = $ERR_COMMAND; break;
		}
		$ret = model::setKeys( arg("id"), arg("old"), arg("new") );
		if( !$ret ) $err = $ERR_NODE_UPDATE;
		break;
/*
	case "clean":
		$ret = model::clean();
		if( !$ret ) $err = $ERR_NODE_UPDATE;
		break;
*
	/**
	 *
	 * XML functions
	 * model_xml namespace 
	 *
	 */

	case "ancestors":
		if( is_null( arg('id') ) ){
			$err = $ERR_COMMAND; break;
		}
		$ret = model_xml::multi( implode( ',', model::ancestors( arg('id') ) ), 1, $NODE_TAG | $NODE_PRM );
		break;
	case "searchKey": // should return array of ids (json array?)
		if( !arg('key') || !arg('value') ){
			$err = $ERR_COMMAND; break;
		}
		$array = model::searchKey( arg('key'), arg('value') );
		//echo sizeof($array);
		$ret = model_xml::multi( implode(',', $array), 1, $NODE_TAG | $NODE_PRM );
		if( !$ret ) $err = $ERR_NODE_ID;
		break;
	case "single":
		if( is_null( arg('id') ) ){
			$err = $ERR_COMMAND; break;
		}
		$ret = model_xml::node( arg( "id" ), 1, $NODE_TAG | $NODE_PRM );
		if( !$ret ) $err = $ERR_NODE_ID;
		break;
	case "children":
		if( is_null( arg('id') ) ){
			$err = $ERR_COMMAND; break;
		}
		$ret = model_xml::children( arg("id"), 1 );
		#if( !$ret) $err = $ERR_NODE_ID;
		break;
	case "multi":
		if( is_null( arg('id') ) ){
			$err = $ERR_COMMAND; break;
		}
		#$ret = xml_multi(arg("id"), arg("flags"));
		#$ret = xml_multi2(arg("id"));
		$ret = model_xml::multi( arg("id"), 1, $NODE_TAG | $NODE_PRM );
		if (!$ret)
			$err = $ERR_NODE_ID;
		break;
	case "graph":
		if( is_null( arg('id') ) ){
			$err = $ERR_COMMAND; break;
		}
		$ret = model_xml::graph( arg("id") );
		if (!$ret)
			$err = $ERR_NODE_ID;
		break;

	/**
	 *
	 * OTHER
	 *
	 */
	case "stats":
		$q = "SELECT COUNT(*) AS count FROM node;";
		$result = mysql_query($q);
		$row = mysql_fetch_array($result);
		$ret .= sprintf('<node_count>%s</node_count>', $row['count']);
		$q = "SELECT COUNT(*) AS count FROM `key`;";
		$result = mysql_query($q);
		$row = mysql_fetch_array($result);
		$ret .= sprintf('<node_param_count>%s</node_param_count>', $row['count']);
		$q = "SELECT COUNT(*) AS count FROM tag;";
		$result = mysql_query($q);
		$row = mysql_fetch_array($result);
		$ret .= sprintf('<node_tag_count>%s</node_tag_count>', $row['count']);
		$q = "SELECT COUNT(*) AS count FROM link;";
		$result = mysql_query($q);
		$row = mysql_fetch_array($result);
		$ret .= sprintf('<node_link_count>%s</node_link_count>', $row['count']);
		break;
	case "types":
		$q = "SELECT type, COUNT(*) AS count FROM node GROUP BY type ORDER BY type;";
		$result = mysql_query($q);
		while ($row = mysql_fetch_array($result)) {
			$ret .= sprintf('<type count="%s">%s</type>',
				$row['count'],
				htmlspecialchars($row['type'])
			);
		}
		break;
	case "tags":
		$q = "SELECT name, COUNT(*) AS count FROM tag GROUP BY name ORDER BY name;";
		$result = mysql_query($q);
		while ($row = mysql_fetch_array($result)) {
			$ret .= sprintf('<tag count="%s">%s</tag>',
				$row['count'],
				htmlspecialchars($row['name'])
			);
		}
		break;

/*
	case "cascade":
		$ret = mysql_get(arg("id"), $depth);
		if (!$ret)
			$err = $ERR_NODE_ID;
		break;
	case "flatten":
		$flags = 15;
	$types = array("Task","task");
		$depth = 0;
		if(arg("flags")) $flags = arg("flags");
		if(arg("types")) $types = arg("types");
		if(arg("depth")) $depth = arg("depth");
		$ret = mysql_flatten(arg("id"), $flags, $types, $depth);
		if (!$ret)
			$err = $ERR_NODE_ID;
		break;
	case "spider":
		$ret = xml_spider(arg("id"), $depth);
		if (!$ret)
			$err = $ERR_NODE_ID;
		break;
*/
	default:
		header("HTTP/1.1: 400 Bad Request"); //ERR_COMMAND
		echo "Bad command";
		exit;
}

$exec_time = ceil((microtime() - $start_time) * 1000) + "ms";

if( $err == $ERR_NOERROR )
{
	$nolog = array( 'single', 'children', 'multi', 'stats', 'types', 'tags' );
	if( ! in_array( arg('cmd'), $nolog ) )
		damas_service::log_event();
}

echo soaplike_head($cmd,$err);
echo "\t\t<execution_time>$exec_time</execution_time>\n";
echo "\t</soap:Header>\n";
echo "\t<soap:Body>\n";
echo "\t\t<returnvalue>$ret</returnvalue>\n";
echo "\t</soap:Body>\n";
echo "</soap:Envelope>\n";
?>
