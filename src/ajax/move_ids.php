<?php

$table = $_GET['table'];
$ids = $_GET['ids'];
$rub = $_GET['rub'];

$under_name = ${"admin_$table"}->FIELD_UNDER;

$q = "UPDATE `$table` SET $under_name = $rub WHERE id IN ($ids)";
mQuery($q);
echo $q . ' -> ' . mQuery($q);

// Auto assoc
if ($table == 'catalog_products') {
	$idsArr = explode(',' , $ids);

	$q = "DELETE FROM catalog_products_categories_assoc WHERE product_id IN ($ids)";
	mQuery($q);

	foreach ($idsArr as $id) {
		$q = "INSERT INTO catalog_products_categories_assoc SET cat_id = $rub, product_id  = $id";
		mQuery($q);
	}
}
