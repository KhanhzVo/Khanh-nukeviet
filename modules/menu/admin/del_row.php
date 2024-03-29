<?php

/**
 * @Project NUKEVIET 4.x
 * @Author VINADES.,JSC (contact@vinades.vn)
 * @Copyright (C) 2014 VINADES.,JSC. All rights reserved
 * @License GNU/GPL version 2 or any later version
 * @Createdate 20-03-2011 20:08
 */

if( ! defined( 'NV_IS_FILE_ADMIN' ) ) die( 'Stop!!!' );

if( ! defined( 'NV_IS_AJAX' ) ) die( 'Wrong URL' );

$id = $nv_Request->get_int( 'id', 'post', 0 );
$mid = $nv_Request->get_int( 'mid', 'post', 0 );
$parentid = $nv_Request->get_int( 'parentid', 'post', 0 );

nv_menu_del_sub( $id, $parentid );

nv_del_moduleCache( $module_name );
menu_fix_order( $mid );

function nv_menu_del_sub( $id, $parentid )
{
	global $module_data, $module_name, $db, $admin_info;
	$sql = 'SELECT title, subitem FROM ' . NV_PREFIXLANG . '_' . $module_data . '_rows WHERE id=' . $id . ' AND parentid=' . $parentid;
	$row = $db->query( $sql )->fetch();

	if( empty( $row ) ) die( 'NO_' . $id );

	$sql = 'DELETE FROM ' . NV_PREFIXLANG . '_' . $module_data . '_rows WHERE id=' . $id;
	if( $db->exec( $sql ) )
	{
		$subitem = ( ! empty( $row['subitem'] ) ) ? explode( ',', $row['subitem'] ) : array();
		foreach( $subitem as $id )
		{
			// Cap nhat cho menu cha
			if( $parentid > 0 )
			{
				$sql = 'SELECT subitem FROM ' . NV_PREFIXLANG . '_' . $module_data . '_rows WHERE id=' . $parentid;
				$row = $db->query( $sql )->fetch();
				if( ! empty( $row ) )
				{
					$subitem = implode( ',', array_diff( array_filter( array_unique( explode( ',', $row['subitem'] ) ) ), array( $id ) ) );

					$stmt = $db->prepare( 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_rows SET subitem= :subitem WHERE id=' . $parentid );
					$stmt->bindParam( ':subitem', $subitem, PDO::PARAM_STR, strlen( $subitem ) );
					$stmt->execute();
				}
			}
			$sql = 'SELECT parentid FROM ' . NV_PREFIXLANG . '_' . $module_data . '_rows WHERE id=' . $id;

			list( $parentid ) = $db->query( $sql )->fetch( 3 );
			nv_menu_del_sub( $id, $parentid );
			nv_insert_logs( NV_LANG_DATA, $module_name, 'Delete menu item', 'Item ID ' . $id, $admin_info['userid'] );
		}
	}
	else
	{
		die( 'NO_' . $id );
	}
}
include NV_ROOTDIR . '/includes/header.php';
echo 'OK_' . $id . '_' . $mid . '_' . $parentid;
include NV_ROOTDIR . '/includes/footer.php';
